<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CourseLessonCompletion;
use App\Models\CourseQuestionnaireResponse;
use App\Models\LeadershipLogbook;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Webinar;
use App\Models\WebinarAttendance;
use App\Models\WebinarExamAttempt;
use App\Models\WebinarQuestion;
use App\Models\WebinarQuestionnaireResponse;
use App\Models\WebinarRegistration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PesertaController extends Controller
{
    private function currentUser()
    {
        return Auth::user();
    }

    public function dashboard()
    {
        $user = $this->currentUser();
        
        $certificates = Certificate::where('user_id', $user->id)->get();
        $totalJp = $certificates->sum('jp');

        // Ambil daftar webinar yang sedang LIVE atau yang SUDAH TERDAFTAR oleh peserta ini
        $activeWebinars = Webinar::with(['organizer.profile'])
            ->where(function ($query) use ($user) {
                $query->where('is_live', true)
                      ->orWhereHas('registrations', function ($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            })
            ->orderByRaw('is_live DESC, event_date DESC, start_time DESC')
            ->get();

        $liveWebinarsCount = Webinar::where('is_live', true)->count();
        $registeredWebinarsCount = WebinarRegistration::where('user_id', $user->id)->count();
        $attendedWebinarsCount = WebinarAttendance::where('user_id', $user->id)->count();

        $recentCertificates = Certificate::where('user_id', $user->id)
            ->orderBy('issued_at', 'desc')
            ->take(2)
            ->get();

        $marqueeText = SystemSetting::get('marquee_announcement');

        return view('peserta.dashboard', compact(
            'user',
            'totalJp',
            'certificates',
            'activeWebinars',
            'liveWebinarsCount',
            'registeredWebinarsCount',
            'attendedWebinarsCount',
            'recentCertificates',
            'marqueeText'
        ));
    }

    public function webinarsIndex(Request $request)
    {
        $user = $this->currentUser();
        $search = $request->query('q');
        $status = $request->query('status', 'all');

        $webinarQuery = Webinar::with(['organizer.profile']);

        if ($search) {
            $webinarQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $today = now()->toDateString();
        if ($status === 'live') {
            $webinarQuery->where('is_live', true);
        } elseif ($status === 'upcoming') {
            $webinarQuery->where('event_date', '>=', $today);
        } elseif ($status === 'completed') {
            $webinarQuery->where('event_date', '<', $today);
        }

        // Live webinar prioritas teratas, kemudian urut tanggal terdekat
        $webinars = $webinarQuery->orderByRaw('is_live DESC, event_date ASC, start_time ASC')->get();

        return view('peserta.webinar.index', compact('user', 'webinars', 'search', 'status'));
    }

    public function katalog(Request $request)
    {
        $user = $this->currentUser();
        $category = $request->query('category', 'all');
        $search = $request->query('q');

        // Jika peserta mengakses kategori webinar dari URL lama/bookmark, arahkan langsung ke halaman webinar tersendiri
        if ($category === 'webinar') {
            return redirect()->route('peserta.webinar.index');
        }

        $query = Course::where('status', 'published');

        if ($category !== 'all') {
            $query->where('type', $category);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $courses = $query->orderBy('title', 'asc')->get();

        return view('peserta.katalog', compact('user', 'courses', 'category', 'search'));
    }

    public function moocShow(string $slug)
    {
        $user = $this->currentUser();

        if ($user->isPesertaUmum()) {
            return redirect()->route('peserta.dashboard')->with('warning', 'Akses Dibatasi: Modul MOOC dan Pelatihan Kedinasan khusus diperuntukkan bagi Aparatur Sipil Negara (ASN) Pemerintah Kabupaten Pasuruan. Akun Peserta Umum hanya dapat mengikuti agenda Webinar & Bimbingan Teknis Terbuka.');
        }

        $course = Course::with(['lessons.questions'])->where('slug', $slug)->firstOrFail();
        
        $enrollment = CourseEnrollment::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();

        // 1. Jika belum terdaftar: periksa apakah masa pendaftaran dibuka
        if (!$enrollment) {
            if ($course->isRegistrationClosed()) {
                $endText = $course->end_date ? ($course->end_date->format('H:i') !== '00:00' ? $course->end_date->format('d M Y, H:i \W\I\B') : $course->end_date->isoFormat('D MMMM Y')) : '-';
                return redirect()->route('peserta.katalog')->with('warning', "Pendaftaran untuk program pelatihan '{$course->title}' telah ditutup karena telah melewati batas waktu pelaksanaan ({$endText}).");
            }

            if ($course->isNotStartedYet()) {
                $startText = $course->start_date ? ($course->start_date->format('H:i') !== '00:00' ? $course->start_date->format('d M Y, H:i \W\I\B') : $course->start_date->isoFormat('D MMMM Y')) : '-';
                return redirect()->route('peserta.katalog')->with('warning', "Program pelatihan '{$course->title}' belum dibuka. Pembelajaran baru dapat diakses mulai {$startText}.");
            }

            // Hitung tenggat waktu pengerjaan (deadline)
            $now = Carbon::now('Asia/Jakarta');
            $deadline = null;
            if ($course->duration_days) {
                $deadline = $now->copy()->addDays($course->duration_days);
            }
            if ($course->end_date) {
                if (!$deadline || $course->end_date->lessThan($deadline)) {
                    $deadline = $course->end_date;
                }
            }

            $prof = $user->profile;
            $enrollment = CourseEnrollment::create([
                'course_id' => $course->id,
                'user_id' => $user->id,
                'nip' => $user->nip,
                'nik' => $prof?->nik,
                'nama_lengkap' => $prof?->formatted_full_name ?? $user->name,
                'jabatan' => $prof?->jabatan,
                'unit_kerja' => $prof?->unit_kerja,
                'enrolled_at' => $now,
                'deadline_at' => $deadline,
                'progress_percentage' => 0,
                'status' => 'in_progress',
            ]);
        } else {
            // Jika deadline_at belum terisi tetapi course memiliki batas waktu
            if (!$enrollment->deadline_at) {
                $enrolledTime = $enrollment->enrolled_at ?: $enrollment->created_at ?: Carbon::now();
                if ($course->duration_days) {
                    $deadline = $enrolledTime->copy()->addDays($course->duration_days);
                    if ($course->end_date && $course->end_date->lessThan($deadline)) {
                        $deadline = $course->end_date;
                    }
                    $enrollment->update(['deadline_at' => $deadline, 'enrolled_at' => $enrolledTime]);
                } elseif ($course->end_date) {
                    $enrollment->update(['deadline_at' => $course->end_date, 'enrolled_at' => $enrolledTime]);
                }
            }
        }

        // Periksa dan tegakkan batas waktu jika kedaluwarsa
        $enrollment->checkAndEnforceExpiry();

        $lessonCompletions = CourseLessonCompletion::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->get()
            ->keyBy('course_lesson_id');

        $totalLessons = $course->lessons->count();
        $completedLessonsCount = $lessonCompletions->where('is_passed', true)->count();

        // Update progress persentase
        if ($totalLessons > 0) {
            $progress = round(($completedLessonsCount / $totalLessons) * 100);
            if ($enrollment->progress_percentage !== $progress) {
                $enrollment->update(['progress_percentage' => $progress]);
            }
        }

        $userQuestionnaire = CourseQuestionnaireResponse::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();

        return view('peserta.mooc.show', compact('user', 'course', 'enrollment', 'lessonCompletions', 'totalLessons', 'completedLessonsCount', 'userQuestionnaire'));
    }

    public function moocCompleteLesson(string $slug, int $lessonId, Request $request)
    {
        $user = $this->currentUser();
        if ($user->isPesertaUmum()) {
            return redirect()->route('peserta.dashboard')->with('error', 'Akses dibatasi untuk peserta umum.');
        }

        $course = Course::with(['lessons.questions'])->where('slug', $slug)->firstOrFail();
        $prof = $user->profile;
        $enrollment = CourseEnrollment::firstOrCreate(
            ['course_id' => $course->id, 'user_id' => $user->id],
            [
                'nip' => $user->nip,
                'nik' => $prof?->nik,
                'nama_lengkap' => $prof?->formatted_full_name ?? $user->name,
                'jabatan' => $prof?->jabatan,
                'unit_kerja' => $prof?->unit_kerja,
            ]
        );

        if ($enrollment->checkAndEnforceExpiry() || $enrollment->status === 'failed') {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Masa pengerjaan pelatihan telah berakhir.'], 403);
            }
            return back()->with('error', 'Masa pengerjaan pelatihan telah berakhir.');
        }

        $lesson = CourseLesson::where('course_id', $course->id)->findOrFail($lessonId);
        $type = $request->input('type', 'all'); // 'video' | 'pdf' | 'all'

        $completion = CourseLessonCompletion::firstOrNew([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_lesson_id' => $lesson->id,
        ]);

        if (!$completion->exists) {
            $completion->nip = $user->nip;
            $completion->nik = $prof?->nik;
            $completion->nama_lengkap = $prof?->formatted_full_name ?? $user->name;
            $completion->jabatan = $prof?->jabatan;
            $completion->unit_kerja = $prof?->unit_kerja;
        }

        if ($type === 'video') {
            $completion->is_video_completed = true;
        } elseif ($type === 'pdf') {
            $completion->is_pdf_read = true;
        } else {
            $completion->is_video_completed = true;
            $completion->is_pdf_read = true;
        }

        // Jika sesi tidak ada kuis atau belum dibuat butir soal, otomatis lulus sesi jika video & PDF tuntas
        if (!$lesson->has_quiz || $lesson->questions->isEmpty()) {
            if ($completion->is_video_completed && $completion->is_pdf_read) {
                $completion->is_passed = true;
                $completion->completed_at = Carbon::now();
            }
        }

        $completion->save();

        // Hitung ulang progres
        $totalLessons = $course->lessons()->count();
        $completedLessonsCount = CourseLessonCompletion::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('is_passed', true)
            ->count();

        $progress = $totalLessons > 0 ? round(($completedLessonsCount / $totalLessons) * 100) : 0;
        $enrollment->update(['progress_percentage' => $progress]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_video_completed' => $completion->is_video_completed,
                'is_pdf_read' => $completion->is_pdf_read,
                'is_passed' => $completion->is_passed,
                'progress_percentage' => $progress,
                'message' => 'Kemajuan belajar sesi berhasil disimpan.',
            ]);
        }

        return back()->with('success', "Kemajuan Sesi {$lesson->sequence}: '{$lesson->title}' berhasil disimpan.");
    }

    public function moocSubmitAssignment(string $slug, Request $request)
    {
        $user = $this->currentUser();
        if ($user->isPesertaUmum()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Akses dibatasi untuk peserta umum.'], 403);
            }
            return redirect()->route('peserta.dashboard')->with('error', 'Akses dibatasi untuk peserta umum.');
        }

        $course = Course::where('slug', $slug)->firstOrFail();
        $enrollment = CourseEnrollment::firstOrCreate(['course_id' => $course->id, 'user_id' => $user->id]);
        
        if ($enrollment->checkAndEnforceExpiry() || $enrollment->status === 'failed') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Masa pengerjaan pelatihan telah berakhir. Pengiriman tugas ditolak karena status Anda telah dinyatakan Tidak Lulus.'], 422);
            }
            return back()->with('error', 'Masa pengerjaan pelatihan telah berakhir. Pengiriman tugas ditolak karena status Anda telah dinyatakan Tidak Lulus.');
        }

        $request->validate([
            'assignment_file' => 'nullable|file|mimes:pdf,docx,doc,zip,rar|max:25600',
            'assignment_notes' => 'nullable|string|max:1000',
        ]);

        $fileName = $enrollment->assignment_file;
        if ($request->hasFile('assignment_file')) {
            $file = $request->file('assignment_file');
            $cleanName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $fileName = 'tugas_' . $user->id . '_' . time() . '_' . $cleanName . '.' . $file->getClientOriginalExtension();
            $file->storeAs('assignments', $fileName, 'public');
        } elseif (!$fileName) {
            $fileName = 'Laporan_Hasil_Belajar_' . Str::slug($user->name) . '_' . time() . '.pdf';
        }

        $notes = $request->input('assignment_notes', 'Laporan tugas dan hasil pembelajaran modul telah diunggah oleh peserta.');
        $enrollment->update([
            'assignment_file' => $fileName,
            'assignment_notes' => $notes,
            'assignment_submitted_at' => Carbon::now(),
            'status' => $enrollment->status === 'passed' ? 'passed' : 'in_progress',
        ]);

        $successMsg = 'Laporan Tugas Akhir / Berkas Hasil Belajar berhasil diunggah. Saat ini berkas Anda menunggu telaah dan persetujuan kelulusan dari Penyelenggara / Super Admin / Widyaiswara sebelum E-Sertifikat resmi diterbitkan.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'assignment_file' => $fileName,
                'assignment_notes' => $notes,
                'file_url' => asset('storage/assignments/' . $fileName),
            ]);
        }

        return back()->with('success', $successMsg);
    }

    public function moocSubmitQuiz(string $slug, Request $request)
    {
        $user = $this->currentUser();
        if ($user->isPesertaUmum()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Akses dibatasi untuk peserta umum.'], 403);
            }
            return redirect()->route('peserta.dashboard')->with('error', 'Akses dibatasi untuk peserta umum.');
        }

        $course = Course::with(['lessons.questions'])->where('slug', $slug)->firstOrFail();
        $enrollment = CourseEnrollment::firstOrCreate(['course_id' => $course->id, 'user_id' => $user->id]);

        if ($enrollment->checkAndEnforceExpiry() || $enrollment->status === 'failed') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Masa pengerjaan pelatihan telah berakhir. Pengiriman kuis evaluasi ditolak karena status Anda telah dinyatakan Tidak Lulus.'], 422);
            }
            return back()->with('error', 'Masa pengerjaan pelatihan telah berakhir. Pengiriman kuis evaluasi ditolak karena status Anda telah dinyatakan Tidak Lulus.');
        }

        $lessonId = $request->input('lesson_id');
        if ($lessonId) {
            $lesson = CourseLesson::with('questions')->where('course_id', $course->id)->findOrFail($lessonId);
        } else {
            $lesson = $course->lessons->first();
        }

        $answers = $request->input('answers', []);
        $totalQuestions = 0;
        $correctCount = 0;

        if ($lesson && $lesson->questions->isNotEmpty()) {
            foreach ($lesson->questions as $q) {
                $totalQuestions++;
                $selected = strtoupper(trim($answers[$q->id] ?? ''));
                if ($selected === strtoupper(trim($q->correct_answer))) {
                    $correctCount++;
                }
            }
        }

        $existingCompletion = CourseLessonCompletion::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('course_lesson_id', $lesson->id)
            ->first();

        $maxRemedial = $course->max_remedial ?: 5;
        $remedialCap = $course->remedial_cap_score ?: 90;
        $attemptCount = $existingCompletion ? ($existingCompletion->attempt_count + 1) : 1;

        if ($existingCompletion && !$existingCompletion->is_passed && $existingCompletion->attempt_count >= $maxRemedial) {
            $limitMsg = "Anda telah mencapai batas maksimal {$maxRemedial} kali percobaan kuis untuk sesi ini. Silakan hubungi Penyelenggara atau Tim Pembina BKPSDM jika memerlukan peninjauan.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $limitMsg], 422);
            }
            return back()->with('error', $limitMsg);
        }

        if ($totalQuestions > 0) {
            $rawScore = round(($correctCount / $totalQuestions) * 100);
        } else {
            $rawScore = 100;
        }

        // Terapkan Plafon Remedial untuk percobaan ke-2 dst:
        $finalScore = ($attemptCount > 1) ? min($rawScore, (float) $remedialCap) : $rawScore;
        $isPassed = $finalScore >= $course->passing_grade;

        // Simpan status kelulusan sesi ini ke database secara permanen
        CourseLessonCompletion::updateOrCreate([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_lesson_id' => $lesson->id,
        ], [
            'is_video_completed' => true,
            'is_pdf_read' => true,
            'raw_score' => $rawScore,
            'quiz_score' => $finalScore,
            'attempt_count' => $attemptCount,
            'is_passed' => $isPassed,
            'completed_at' => $isPassed ? Carbon::now() : null,
        ]);

        // Hitung ulang progres total kursus
        $totalLessons = $course->lessons()->count();
        $completedLessonsCount = CourseLessonCompletion::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('is_passed', true)
            ->count();

        $progress = $totalLessons > 0 ? round(($completedLessonsCount / $totalLessons) * 100) : 0;
        $allLessonsCompleted = ($completedLessonsCount >= $totalLessons && $totalLessons > 0);

        $enrollmentData = [
            'final_score' => $finalScore,
            'progress_percentage' => $progress,
        ];

        $enrollment->update($enrollmentData);

        $feedbackMsg = "";
        if ($attemptCount > 1) {
            $feedbackMsg = "(Percobaan ke-{$attemptCount} / Remedial. Skor: {$rawScore}";
            if ($rawScore > $remedialCap) {
                $feedbackMsg .= " dibatasi plafon maksimal {$remedialCap}";
            }
            $feedbackMsg .= ")";
        }

        if ($allLessonsCompleted && $isPassed) {
            $msg = "Selamat! Anda berhasil lulus Kuis Sesi {$lesson->sequence} dengan nilai {$finalScore}/100 (Passing Grade: {$course->passing_grade}) {$feedbackMsg} dan telah menyelesaikan seluruh materi sesi. Silakan unggah Laporan Tugas Akhir / Hasil Belajar pada formulir di bawah ini untuk ditelaah dan disetujui oleh Penyelenggara/Widyaiswara sebelum E-Sertifikat resmi diterbitkan.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'is_passed' => true,
                    'raw_score' => $rawScore,
                    'quiz_score' => $finalScore,
                    'attempt_count' => $attemptCount,
                    'progress_percentage' => $progress,
                    'all_lessons_completed' => true,
                    'lesson_id' => $lesson->id,
                    'message' => $msg,
                ]);
            }
            return back()->with('success', $msg);
        } elseif ($isPassed) {
            $msg = "Selamat! Anda berhasil lulus Kuis Sesi {$lesson->sequence}: '{$lesson->title}' dengan nilai {$finalScore}/100 (Passing Grade: {$course->passing_grade}) {$feedbackMsg}. Sesi ini telah ditandai tuntas!";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'is_passed' => true,
                    'raw_score' => $rawScore,
                    'quiz_score' => $finalScore,
                    'attempt_count' => $attemptCount,
                    'progress_percentage' => $progress,
                    'all_lessons_completed' => false,
                    'lesson_id' => $lesson->id,
                    'message' => $msg,
                ]);
            }
            return back()->with('success', $msg);
        } else {
            $remaining = max(0, $maxRemedial - $attemptCount);
            $msg = "Hasil Kuis Sesi {$lesson->sequence}: Skor Anda {$finalScore}/100 belum mencapai standar passing grade (&ge; {$course->passing_grade}) {$feedbackMsg}. Sisa kesempatan remedial: {$remaining}x lagi. Silakan pelajari kembali materi dan ulangi kuis.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'is_passed' => false,
                    'raw_score' => $rawScore,
                    'quiz_score' => $finalScore,
                    'attempt_count' => $attemptCount,
                    'progress_percentage' => $progress,
                    'all_lessons_completed' => false,
                    'lesson_id' => $lesson->id,
                    'message' => $msg,
                    'remedial_remaining' => $remaining,
                ]);
            }
            return back()->with('warning', $msg);
        }
    }

    public function moocQuestionnaireSubmit(string $slug, Request $request)
    {
        $user = $this->currentUser();
        if ($user->isPesertaUmum()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Akses dibatasi untuk peserta umum.'], 403);
            }
            return redirect()->route('peserta.dashboard')->with('error', 'Akses dibatasi untuk peserta umum.');
        }

        $course = Course::where('slug', $slug)->firstOrFail();
        $prof = $user->profile;

        $request->validate([
            'rating_materi' => 'required|integer|min:1|max:5',
            'rating_sistem' => 'required|integer|min:1|max:5',
            'rating_manfaat' => 'required|integer|min:1|max:5',
            'rating_instruktur' => 'required|integer|min:1|max:5',
            'feedback_text' => 'nullable|string|max:1000',
        ]);

        $qResp = CourseQuestionnaireResponse::updateOrCreate(
            ['course_id' => $course->id, 'user_id' => $user->id],
            [
                'nip' => $user->nip,
                'nik' => $prof?->nik,
                'nama_lengkap' => $prof?->formatted_full_name ?? $user->name,
                'jabatan' => $prof?->jabatan,
                'unit_kerja' => $prof?->unit_kerja,
                'rating_materi' => $request->rating_materi,
                'rating_sistem' => $request->rating_sistem,
                'rating_manfaat' => $request->rating_manfaat,
                'rating_instruktur' => $request->rating_instruktur,
                'feedback_text' => $request->feedback_text,
                'submitted_at' => Carbon::now('Asia/Jakarta'),
            ]
        );

        $msg = 'Terima kasih! Kuesioner evaluasi kepuasan pelatihan Anda telah berhasil disimpan.';

        if ($request->expectsJson() || $request->ajax()) {
            $avg = round(((int)$request->rating_materi + (int)$request->rating_sistem + (int)$request->rating_manfaat + (int)$request->rating_instruktur) / 4, 1);
            return response()->json([
                'success' => true,
                'message' => $msg,
                'ratings' => [
                    'rating_materi' => (int) $request->rating_materi,
                    'rating_sistem' => (int) $request->rating_sistem,
                    'rating_manfaat' => (int) $request->rating_manfaat,
                    'rating_instruktur' => (int) $request->rating_instruktur,
                    'feedback_text' => $request->feedback_text,
                    'average_rating' => $avg,
                ]
            ]);
        }

        return back()->with('success', $msg);
    }

    public function webinarRoom(string $slug)
    {
        $user = $this->currentUser();
        $webinar = Webinar::with([
            'organizer.profile',
            'quizQuestions',
            'questionnaireQuestions',
        ])->where('slug', $slug)->firstOrFail();

        // 1. Gating Check: Pendaftaran (Tercatat resmi ke basis data)
        $registration = WebinarRegistration::firstOrCreate(
            ['webinar_id' => $webinar->id, 'user_id' => $user->id],
            ['registered_at' => Carbon::now()]
        );
        $isRegistered = true;

        // 2. Gating Check: Presensi
        $attendance = WebinarAttendance::where('webinar_id', $webinar->id)
            ->where('user_id', $user->id)
            ->first();

        // 3. Gating Check: Kuesioner (cek via basis data respon atau session)
        $questionnaireResponse = WebinarQuestionnaireResponse::where('webinar_id', $webinar->id)
            ->where('user_id', $user->id)
            ->first();
        $questionnaireCompleted = $questionnaireResponse !== null || session("questionnaire_{$webinar->id}_{$user->id}", false);

        // 4. Gating Check: Evaluasi & Remedial Attempts
        $attempts = WebinarExamAttempt::where('webinar_id', $webinar->id)
            ->where('user_id', $user->id)
            ->orderBy('attempt_number', 'asc')
            ->get();

        $latestAttempt = $attempts->last();
        $passed = $latestAttempt ? $latestAttempt->is_passed : false;
        $attemptCount = $attempts->count();
        $canRemedial = (!$passed && $attemptCount > 0 && $attemptCount < $webinar->max_remedial);
        $isFailedPermanently = (!$passed && $attemptCount >= $webinar->max_remedial);

        // 5. Gating Check: E-Sertifikat
        $certificate = Certificate::where('webinar_id', $webinar->id)
            ->where('user_id', $user->id)
            ->first();

        // Jika semua syarat terpenuhi tapi sertifikat belum ter-generate, auto generate sertifikat
        if ($attendance && $passed && !$certificate) {
            $certificate = Certificate::issueForWebinar($webinar, $user);
        }

        return view('peserta.webinar.room', compact(
            'user',
            'webinar',
            'isRegistered',
            'registration',
            'attendance',
            'questionnaireCompleted',
            'questionnaireResponse',
            'attempts',
            'latestAttempt',
            'passed',
            'attemptCount',
            'canRemedial',
            'isFailedPermanently',
            'certificate'
        ));
    }

    public function webinarRegister(int $id, Request $request)
    {
        $user = $this->currentUser();
        $webinar = Webinar::findOrFail($id);
        $prof = $user->profile;

        WebinarRegistration::firstOrCreate(
            ['webinar_id' => $webinar->id, 'user_id' => $user->id],
            [
                'nip' => $user->nip,
                'nik' => $prof?->nik,
                'nama_lengkap' => $prof?->formatted_full_name ?? $user->name,
                'jabatan' => $prof?->jabatan,
                'unit_kerja' => $prof?->unit_kerja,
                'registered_at' => Carbon::now(),
            ]
        );

        return back()->with('success', "Anda berhasil terdaftar dalam sesi '{$webinar->title}'. Status Anda kini 'Sudah Terdaftar'.");
    }

    public function webinarAttend(int $id, Request $request)
    {
        $user = $this->currentUser();
        $webinar = Webinar::findOrFail($id);
        $prof = $user->profile;

        if (!$webinar->is_live && !$webinar->isWithinLiveSchedule()) {
            $scheduleText = $webinar->start_datetime ? $webinar->start_datetime->format('d M Y, H:i \W\I\B') : 'jadwal yang ditentukan';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => "Sesi belum dimulai oleh penyelenggara (Jadwal Mulai: {$scheduleText}). Presensi hanya dapat diisi saat status siaran LIVE aktif."
                ], 422);
            }
            return back()->with('error', "Sesi belum dimulai oleh penyelenggara (Jadwal Mulai: {$scheduleText}). Presensi hanya dapat diisi saat status siaran LIVE aktif.");
        }

        $attendance = WebinarAttendance::firstOrCreate([
            'webinar_id' => $webinar->id,
            'user_id' => $user->id,
        ], [
            'nip' => $user->nip,
            'nik' => $prof?->nik,
            'nama_lengkap' => $prof?->formatted_full_name ?? $user->name,
            'jabatan' => $prof?->jabatan,
            'unit_kerja' => $prof?->unit_kerja,
            'attended_at' => Carbon::now('Asia/Jakarta'),
        ]);

        $attendedAtFormatted = Carbon::parse($attendance->attended_at)->isoFormat('D MMMM Y, HH:mm:ss') . ' WIB';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'attended_at' => $attendance->attended_at,
                'attended_at_formatted' => $attendedAtFormatted,
                'message' => 'Presensi kehadiran Anda berhasil dicatat secara resmi pada sistem BKPSDM.'
            ]);
        }

        return back()->with('success', 'Presensi kehadiran Anda berhasil dicatat secara resmi pada sistem BKPSDM.');
    }

    public function webinarQuestionnaire(int $id, Request $request)
    {
        $user = $this->currentUser();
        $webinar = Webinar::findOrFail($id);
        $prof = $user->profile;

        if (!$webinar->isQuestionnaireOpen()) {
            $qStart = substr($webinar->questionnaire_start_time, 0, 5);
            $qEnd = substr($webinar->questionnaire_end_time, 0, 5);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => "Jendela pengisian kuesioner belum dibuka atau telah berakhir (Jadwal: {$qStart} - {$qEnd} WIB)."
                ], 422);
            }
            return back()->with('warning', "Jendela pengisian kuesioner belum dibuka atau telah berakhir (Jadwal: {$qStart} - {$qEnd} WIB).");
        }

        $answers = $request->input('answers', []);

        WebinarQuestionnaireResponse::updateOrCreate(
            ['webinar_id' => $webinar->id, 'user_id' => $user->id],
            [
                'nip' => $user->nip,
                'nik' => $prof?->nik,
                'nama_lengkap' => $prof?->formatted_full_name ?? $user->name,
                'jabatan' => $prof?->jabatan,
                'unit_kerja' => $prof?->unit_kerja,
                'answers' => $answers,
                'submitted_at' => Carbon::now('Asia/Jakarta'),
            ]
        );

        session(["questionnaire_{$webinar->id}_{$user->id}" => true]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Kuesioner kepuasan penyelenggaraan berhasil dikirimkan. Tahap Evaluasi / Post-Test kini telah terbuka!'
            ]);
        }

        return back()->with('success', 'Kuesioner kepuasan penyelenggaraan berhasil dikirimkan. Tahap Evaluasi / Post-Test kini telah terbuka!');
    }

    public function webinarExamSubmit(int $id, Request $request)
    {
        $user = $this->currentUser();
        $webinar = Webinar::findOrFail($id);
        $prof = $user->profile;

        if (!$webinar->isExamOpen()) {
            $eStart = substr($webinar->exam_start_time, 0, 5);
            $eEnd = substr($webinar->exam_end_time, 0, 5);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => "Jendela pengerjaan evaluasi / post-test belum dibuka atau telah berakhir (Jadwal: {$eStart} - {$eEnd} WIB)."
                ], 422);
            }
            return back()->with('warning', "Jendela pengerjaan evaluasi / post-test belum dibuka atau telah berakhir (Jadwal: {$eStart} - {$eEnd} WIB).");
        }

        $existingAttemptsCount = WebinarExamAttempt::where('webinar_id', $webinar->id)
            ->where('user_id', $user->id)
            ->count();

        $attemptNumber = $existingAttemptsCount + 1;

        if ($attemptNumber > $webinar->max_remedial) {
            $maxMsg = "Anda telah mencapai batas maksimal {$webinar->max_remedial} kali pengerjaan.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $maxMsg], 422);
            }
            return back()->with('error', $maxMsg);
        }

        // Ambil semua soal kuis dari database
        $quizQuestions = WebinarQuestion::where('webinar_id', $webinar->id)
            ->where('type', 'quiz')
            ->orderBy('order_number')
            ->get();

        $totalQuestions = $quizQuestions->count();
        $submittedAnswers = $request->input('answers', []);
        $correctCount = 0;

        if ($totalQuestions > 0) {
            foreach ($quizQuestions as $question) {
                $userAns = strtoupper(trim($submittedAnswers[$question->id] ?? ''));
                $correctAns = strtoupper(trim($question->correct_answer ?? ''));
                if ($userAns !== '' && $userAns === $correctAns) {
                    $correctCount++;
                }
            }
            $rawScore = round(($correctCount / $totalQuestions) * 100, 2);
        } else {
            // Fallback jika belum ada soal kuis yang dikonfigurasi di database
            $rawScore = (float) $request->input('simulated_score', 85.00);
        }

        // Terapkan algoritma batas plafon remedial sesuai PRD Bab 4.3:
        // Attempt 1: Skor mentah murni
        // Attempt 2..max: min(Skor mentah, remedial_cap_score)
        $remedialCap = $webinar->remedial_cap_score ?? 90;
        $finalScore = ($attemptNumber > 1) ? min($rawScore, (float) $remedialCap) : $rawScore;
        $isPassed = $finalScore >= $webinar->passing_grade;

        $attempt = WebinarExamAttempt::create([
            'webinar_id' => $webinar->id,
            'user_id' => $user->id,
            'nip' => $user->nip,
            'nik' => $prof?->nik,
            'nama_lengkap' => $prof?->formatted_full_name ?? $user->name,
            'jabatan' => $prof?->jabatan,
            'unit_kerja' => $prof?->unit_kerja,
            'attempt_number' => $attemptNumber,
            'raw_score' => $rawScore,
            'final_score' => $finalScore,
            'is_passed' => $isPassed,
            'answers' => $submittedAnswers,
            'created_at' => Carbon::now(),
        ]);

        $certificate = null;
        if ($isPassed) {
            $attendance = WebinarAttendance::where('webinar_id', $webinar->id)
                ->where('user_id', $user->id)
                ->first();
            if ($attendance) {
                $certificate = Certificate::where('webinar_id', $webinar->id)
                    ->where('user_id', $user->id)
                    ->first();
                if (!$certificate) {
                    $certificate = Certificate::issueForWebinar($webinar, $user);
                }
            }
        }

        $message = "Evaluasi percobaan ke-{$attemptNumber} selesai. ";
        if ($totalQuestions > 0) {
            $message .= "Jawaban Benar: {$correctCount}/{$totalQuestions} Soal. ";
        }
        $message .= "Skor: {$rawScore}, Skor Akhir: {$finalScore}. ";
        if ($attemptNumber > 1 && $rawScore > $remedialCap) {
            $message .= "(Sesuai regulasi, nilai remedial dibatasi plafon maksimal {$remedialCap}). ";
        }
        $message .= $isPassed ? "Selamat, Anda dinyatakan LULUS!" : "Nilai belum mencapai Passing Grade ({$webinar->passing_grade}). Silakan pelajari kembali materi dan ikuti remedial.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'attempt' => [
                    'attempt_number' => $attemptNumber,
                    'raw_score' => $rawScore,
                    'final_score' => $finalScore,
                    'is_passed' => $isPassed,
                    'time' => Carbon::now()->format('H:i') . ' WIB',
                ],
                'is_passed' => $isPassed,
                'can_remedial' => (!$isPassed && $attemptNumber < $webinar->max_remedial),
                'is_failed_permanently' => (!$isPassed && $attemptNumber >= $webinar->max_remedial),
                'attempts_remaining' => max(0, $webinar->max_remedial - $attemptNumber),
                'certificate' => $certificate ? [
                    'certificate_number' => $certificate->certificate_number,
                    'training_title' => $certificate->training_title,
                    'hash_token' => $certificate->hash_token,
                    'validation_url' => route('validation.show', $certificate->hash_token),
                    'download_url' => route('peserta.certificates'),
                ] : null,
            ]);
        }

        return back()->with($isPassed ? 'success' : 'warning', $message);
    }

    public function kepemimpinan()
    {
        $user = $this->currentUser();

        if ($user->isPesertaUmum()) {
            return redirect()->route('peserta.dashboard')->with('warning', 'Akses Dibatasi: Pelatihan Kepemimpinan (PKP/PKA/PKN) dan Laboratorium Aksi Perubahan khusus diperuntukkan bagi Aparatur Sipil Negara (ASN) Pemerintah Kabupaten Pasuruan.');
        }

        $leadershipCourse = Course::where('type', 'kepemimpinan')->first();
        $enrollment = null;
        if ($leadershipCourse) {
            $enrollment = CourseEnrollment::where('course_id', $leadershipCourse->id)
                ->where('user_id', $user->id)
                ->first();

            if (!$enrollment) {
                $now = Carbon::now();
                $deadline = null;
                if ($leadershipCourse->duration_days) {
                    $deadline = $now->copy()->addDays($leadershipCourse->duration_days);
                }
                if ($leadershipCourse->end_date) {
                    if (!$deadline || $leadershipCourse->end_date->lessThan($deadline)) {
                        $deadline = $leadershipCourse->end_date;
                    }
                }

                $prof = $user->profile;
                $enrollment = CourseEnrollment::create([
                    'course_id' => $leadershipCourse->id,
                    'user_id' => $user->id,
                    'nip' => $user->nip,
                    'nik' => $prof?->nik,
                    'nama_lengkap' => $prof?->formatted_full_name ?? $user->name,
                    'jabatan' => $prof?->jabatan,
                    'unit_kerja' => $prof?->unit_kerja,
                    'enrolled_at' => $now,
                    'deadline_at' => $deadline,
                    'status' => 'in_progress',
                    'progress_percentage' => 0,
                ]);
            } else {
                if (!$enrollment->deadline_at && $leadershipCourse->hasTimeLimit()) {
                    $enrolledTime = $enrollment->enrolled_at ?: $enrollment->created_at ?: Carbon::now();
                    $deadline = null;
                    if ($leadershipCourse->duration_days) {
                        $deadline = $enrolledTime->copy()->addDays($leadershipCourse->duration_days);
                    }
                    if ($leadershipCourse->end_date) {
                        if (!$deadline || $leadershipCourse->end_date->lessThan($deadline)) {
                            $deadline = $leadershipCourse->end_date;
                        }
                    }
                    $enrollment->update(['deadline_at' => $deadline, 'enrolled_at' => $enrolledTime]);
                }
                $enrollment->checkAndEnforceExpiry();
            }
        }
        
        $logbooks = LeadershipLogbook::with(['coach.profile', 'mentor.profile'])
            ->where('user_id', $user->id)
            ->orderBy('week_number', 'asc')
            ->get();

        $coach = User::where('role', 'widyaiswara')->first();
        $mentor = User::where('role', 'mentor')->first();

        return view('peserta.kepemimpinan.logbook', compact('user', 'logbooks', 'coach', 'mentor', 'leadershipCourse', 'enrollment'));
    }

    public function kepemimpinanStore(Request $request)
    {
        $user = $this->currentUser();
        $prof = $user->profile;

        $leadershipCourse = Course::where('type', 'kepemimpinan')->first();
        if ($leadershipCourse) {
            $enrollment = CourseEnrollment::where('course_id', $leadershipCourse->id)
                ->where('user_id', $user->id)
                ->first();
            if ($enrollment) {
                if ($enrollment->checkAndEnforceExpiry() || $enrollment->status === 'failed') {
                    return back()->with('error', 'Masa pengerjaan Pelatihan Kepemimpinan telah berakhir. Pengajuan milestone logbook ditolak karena status Anda telah dinyatakan Tidak Lulus.');
                }
            }
        }
        
        $request->validate([
            'week_number' => 'required|integer|min:1|max:20',
            'milestone_title' => 'required|string|max:255',
            'activity_date' => 'required|date',
            'activity_description' => 'required|string',
            'evidence_file' => 'nullable|file|mimes:pdf,docx,doc,jpg,jpeg,png,zip|max:25600',
        ]);

        $coach = User::where('role', 'widyaiswara')->first();
        $mentor = User::where('role', 'mentor')->first();

        $evidenceFileName = 'Dokumen_Dukungan_Minggu_' . $request->week_number . '.pdf';
        if ($request->hasFile('evidence_file')) {
            $file = $request->file('evidence_file');
            $cleanName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $evidenceFileName = 'milestone_' . $user->id . '_w' . $request->week_number . '_' . time() . '_' . $cleanName . '.' . $file->getClientOriginalExtension();
            $file->storeAs('evidence', $evidenceFileName, 'public');
        }

        LeadershipLogbook::create([
            'user_id' => $user->id,
            'nip' => $user->nip,
            'nik' => $prof?->nik,
            'nama_lengkap' => $prof?->formatted_full_name ?? $user->name,
            'jabatan' => $prof?->jabatan,
            'unit_kerja' => $prof?->unit_kerja,
            'coach_id' => $coach?->id,
            'mentor_id' => $mentor?->id,
            'week_number' => $request->week_number,
            'milestone_title' => $request->milestone_title,
            'activity_date' => $request->activity_date,
            'activity_description' => $request->activity_description,
            'evidence_file_name' => $evidenceFileName,
            'status' => 'submitted',
        ]);

        return back()->with('success', 'Logbook mingguan aksi perubahan berhasil diunggah. Menunggu telaah dan persetujuan dari Coach (Widyaiswara) dan Mentor (Atasan Langsung).');
    }

    public function sertifikat()
    {
        $user = $this->currentUser();
        $certificates = Certificate::with(['template', 'webinar.organizer.profile', 'course.organizer.profile', 'course.lessons'])
            ->where('user_id', $user->id)
            ->orderBy('issued_at', 'desc')
            ->get();

        $defaultTemplate = CertificateTemplate::first();

        return view('peserta.certificates.index', compact('user', 'certificates', 'defaultTemplate'));
    }

    /**
     * Cetak / Unduh Dokumen PDF E-Sertifikat Resmi untuk Peserta
     */
    public function sertifikatPrint(int $id)
    {
        $user = $this->currentUser();
        $cert = Certificate::with(['template', 'webinar.organizer.profile', 'course.organizer.profile', 'course.lessons', 'user.profile'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $defaultTemplate = CertificateTemplate::first();

        return view('admin.certificates.print', compact('cert', 'defaultTemplate'));
    }
}
