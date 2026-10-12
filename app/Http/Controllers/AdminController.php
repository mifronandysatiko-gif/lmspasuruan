<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CourseLessonQuestion;
use App\Models\CourseQuestionnaireResponse;
use App\Models\LeadershipLogbook;
use App\Models\SidebarMenuSetting;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Webinar;
use App\Models\WebinarAttendance;
use App\Models\WebinarQuestion;
use App\Models\WebinarQuestionnaireResponse;
use App\Services\ExcelExportService;
use App\Services\ExcelImportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    private function currentUser()
    {
        return Auth::user();
    }

    public function dashboard()
    {
        $user = $this->currentUser();
        
        $totalUsers = User::count();
        $totalAsn = User::whereNotNull('nip')->count();
        $totalWebinars = Webinar::count();
        $totalCertificates = Certificate::count();

        $recentAudits = AuditLog::with('user')->orderBy('created_at', 'desc')->take(6)->get();
        $recentUsers = User::with('profile')->orderBy('created_at', 'desc')->take(5)->get();
        $webinars = Webinar::withCount('attendances')->orderBy('created_at', 'desc')->take(4)->get();

        return view('admin.dashboard', compact(
            'user',
            'totalUsers',
            'totalAsn',
            'totalWebinars',
            'totalCertificates',
            'recentAudits',
            'recentUsers',
            'webinars'
        ));
    }

    public function settings()
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Akses Ditolak: Konfigurasi sistem dinamis merupakan wewenang khusus Super Admin BKPSDM.');
        }

        $settings = SystemSetting::all()->groupBy('setting_group');

        $pendingJobsCount = DB::table('jobs')->count();
        $failedJobsCount = DB::table('failed_jobs')->count();
        $pendingJobs = DB::table('jobs')->orderBy('id', 'asc')->take(10)->get();

        return view('admin.settings.index', compact('user', 'settings', 'pendingJobsCount', 'failedJobsCount', 'pendingJobs'));
    }

    public function queueProcessNow(Request $request)
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Akses Ditolak.');
        }

        $pendingBefore = DB::table('jobs')->count();
        if ($pendingBefore === 0) {
            return back()->with('success', 'Tidak ada antrean email yang tertunda saat ini.');
        }

        try {
            Artisan::call('queue:work', [
                'connection' => 'database',
                '--stop-when-empty' => true,
                '--tries' => 3,
                '--timeout' => 45,
            ]);

            $pendingAfter = DB::table('jobs')->count();
            $processedCount = max(0, $pendingBefore - $pendingAfter);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'PROCESS_EMAIL_QUEUE_MANUAL',
                'auditable_type' => 'QueueWorker',
                'auditable_id' => null,
                'description' => "Super Admin ({$user->name}) memproses antrean email secara langsung melalui panel admin. {$processedCount} tugas berhasil diproses.",
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            return back()->with('success', "Proses antrean selesai: {$processedCount} email berhasil diproses dan dikirimkan.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Terjadi kesalahan saat memproses antrean: ' . $e->getMessage());
        }
    }

    public function queueRetryFailed(Request $request)
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Akses Ditolak.');
        }

        $failedCount = DB::table('failed_jobs')->count();
        if ($failedCount === 0) {
            return back()->with('success', 'Tidak ada pekerjaan gagal yang perlu dicoba ulang.');
        }

        try {
            Artisan::call('queue:retry', ['id' => ['all']]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'RETRY_FAILED_QUEUE_JOBS',
                'auditable_type' => 'QueueWorker',
                'auditable_id' => null,
                'description' => "Super Admin ({$user->name}) mencoba ulang {$failedCount} pekerjaan antrean yang sebelumnya gagal.",
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            return back()->with('success', "Sebanyak {$failedCount} pekerjaan antrean yang gagal telah dimasukkan kembali ke antrean untuk dicoba ulang.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengulang antrean: ' . $e->getMessage());
        }
    }

    public function settingsUpdate(Request $request)
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Akses Ditolak.');
        }

        $inputs = $request->except(['_token']);

        $oldValues = [];
        $newValues = [];

        foreach ($inputs as $key => $val) {
            $existing = SystemSetting::where('setting_key', $key)->first();
            if ($existing) {
                $oldValues[$key] = $existing->setting_value;
                $existing->update(['setting_value' => (string) $val]);
                $newValues[$key] = (string) $val;
            }
        }

        // Catat Audit Trail sesuai PRD
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'UPDATE_SYSTEM_SETTINGS',
            'auditable_type' => 'App\Models\SystemSetting',
            'auditable_id' => null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Konfigurasi dinamis sistem berhasil diperbarui tanpa modifikasi kode sumber.');
    }

    public function webinarsIndex()
    {
        $user = $this->currentUser();
        $webinars = Webinar::with(['organizer.profile'])->withCount('attendances')->orderBy('created_at', 'desc')->get();
        $organizers = User::whereIn('role', ['super_admin', 'penyelenggara'])->with('profile')->orderBy('name')->get();
        $templates = CertificateTemplate::all();

        return view('admin.webinars.index', compact('user', 'webinars', 'organizers', 'templates'));
    }

    public function webinarsExportAll()
    {
        return ExcelExportService::exportWebinars();
    }

    public function webinarExportParticipants(int $id)
    {
        $webinar = Webinar::findOrFail($id);
        return ExcelExportService::exportWebinarParticipants($webinar);
    }

    public function webinarStore(Request $request)
    {
        $user = $this->currentUser();

        $request->validate([
            'organizer_id' => 'nullable|exists:users,id',
            'certificate_template_id' => 'nullable|exists:certificate_templates,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'jp' => 'required|integer|min:1|max:50',
            'event_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'meeting_link' => 'nullable|string|max:500',
            'youtube_link' => 'nullable|string|max:500',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'passing_grade' => 'nullable|integer|min:10|max:100',
            'max_remedial' => 'nullable|integer|min:1|max:10',
            'remedial_cap_score' => 'nullable|integer|min:10|max:100',
            'certificate_number' => 'nullable|string|max:150',
            'certificate_start_number' => 'nullable|integer|min:1',
            'certificate_format' => 'nullable|string|max:150',
            'share_message' => 'nullable|string',
        ]);

        $slug = Str::slug($request->title);
        $originalSlug = $slug;
        if (Webinar::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-" . time();
        }

        $bannerPath = 'banners/webinar_spbe.jpg'; // default banner
        if ($request->hasFile('banner')) {
            $path = $request->file('banner')->store('banners', 'public');
            $bannerPath = $path;
        }

        // Schedule defaults
        $startTime = $request->start_time;
        $endTime = $request->end_time;
        $qStart = $request->input('questionnaire_start_time') ?: Carbon::parse($startTime)->addHours(1)->format('H:i:s');
        $qEnd = $request->input('questionnaire_end_time') ?: Carbon::parse($endTime)->addMinutes(30)->format('H:i:s');
        $eStart = $request->input('exam_start_time') ?: Carbon::parse($startTime)->addHours(2)->format('H:i:s');
        $eEnd = $request->input('exam_end_time') ?: Carbon::parse($endTime)->addHours(2)->format('H:i:s');

        $webinar = Webinar::create([
            'organizer_id' => $request->organizer_id ?: $user->id,
            'certificate_template_id' => $request->certificate_template_id ?: null,
            'title' => $request->title,
            'slug' => $slug,
            'description' => $request->description,
            'banner_path' => $bannerPath,
            'youtube_link' => $request->youtube_link,
            'meeting_link' => $request->meeting_link,
            'jp' => (int) $request->jp,
            'event_date' => $request->event_date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_live' => false,
            'questionnaire_start_time' => $qStart,
            'questionnaire_end_time' => $qEnd,
            'exam_start_time' => $eStart,
            'exam_end_time' => $eEnd,
            'passing_grade' => (int) ($request->passing_grade ?: 75),
            'max_remedial' => (int) ($request->max_remedial ?: 5),
            'remedial_cap_score' => (int) ($request->remedial_cap_score ?: 90),
            'certificate_number' => $request->certificate_number ? trim($request->certificate_number) : null,
            'certificate_start_number' => $request->certificate_start_number ? (int) $request->certificate_start_number : null,
            'certificate_format' => $request->certificate_format ? trim($request->certificate_format) : null,
            'share_message' => $request->filled('share_message') ? trim($request->share_message) : null,
        ]);

        $defaultQuestions = [
            [
                'type' => 'questionnaire',
                'input_type' => 'options',
                'question_text' => 'Bagaimana penilaian Anda terhadap materi dan pemaparan narasumber?',
                'options' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang'],
                'correct_answer' => null,
                'order_number' => 1,
            ],
            [
                'type' => 'questionnaire',
                'input_type' => 'options',
                'question_text' => 'Bagaimana kualitas jaringan dan kestabilan siaran ruang webinar?',
                'options' => ['Sangat Memuaskan', 'Cukup Memuaskan', 'Kurang Memuaskan', 'Tidak Memuaskan'],
                'correct_answer' => null,
                'order_number' => 2,
            ],
            [
                'type' => 'questionnaire',
                'input_type' => 'text',
                'question_text' => 'Tuliskan saran, masukan, atau usulan topik pengembangan kompetensi lanjutan untuk kegiatan berikutnya:',
                'options' => null,
                'correct_answer' => null,
                'order_number' => 3,
            ],
            [
                'type' => 'quiz',
                'input_type' => 'options',
                'question_text' => 'Apa tujuan utama dari implementasi materi pembelajaran yang disampaikan pada sesi ini?',
                'options' => [
                    'A' => 'Meningkatkan kompetensi, integritas, dan kualitas pelayanan publik ASN Pasuruan',
                    'B' => 'Sekadar formalitas pemenuhan jam kerja tanpa tindak lanjut',
                    'C' => 'Membatasi koordinasi antar perangkat daerah',
                    'D' => 'Menghindari tugas pokok dan fungsi harian',
                ],
                'correct_answer' => 'A',
                'order_number' => 1,
            ],
            [
                'type' => 'quiz',
                'input_type' => 'options',
                'question_text' => 'Penerapan nilai akuntabilitas dalam pelaksanaan tugas kedinasan ditunjukkan dengan:',
                'options' => [
                    'A' => 'Tanggung jawab penuh atas hasil kerja dan transparansi kepada pimpinan serta publik',
                    'B' => 'Menutup-nutupi kendala pelaksanaan kegiatan',
                    'C' => 'Mengabaikan evaluasi kinerja berkala',
                    'D' => 'Bekerja sendiri-sendiri tanpa sinergi lintas bidang',
                ],
                'correct_answer' => 'A',
                'order_number' => 2,
            ],
        ];

        foreach ($defaultQuestions as $dq) {
            WebinarQuestion::create([
                'webinar_id' => $webinar->id,
                'type' => $dq['type'],
                'input_type' => $dq['input_type'] ?? 'options',
                'question_text' => $dq['question_text'],
                'options' => $dq['options'],
                'correct_answer' => $dq['correct_answer'],
                'order_number' => $dq['order_number'],
            ]);
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'CREATE_WEBINAR',
            'auditable_type' => 'App\Models\Webinar',
            'auditable_id' => $webinar->id,
            'new_values' => $webinar->toArray(),
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.webinars.manage', $webinar->id)->with('success', "Sesi Webinar '{$webinar->title}' berhasil dibuat! Anda dapat mengelola jendela waktu dan bank instrumen soal sekarang.");
    }

    public function webinarUpdate(int $id, Request $request)
    {
        $user = $this->currentUser();
        $webinar = Webinar::findOrFail($id);

        $request->validate([
            'organizer_id' => 'nullable|exists:users,id',
            'certificate_template_id' => 'nullable|exists:certificate_templates,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'jp' => 'required|integer|min:1|max:50',
            'event_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'meeting_link' => 'nullable|string|max:500',
            'youtube_link' => 'nullable|string|max:500',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'questionnaire_start_time' => 'nullable',
            'questionnaire_end_time' => 'nullable',
            'exam_start_time' => 'nullable',
            'exam_end_time' => 'nullable',
            'passing_grade' => 'nullable|integer|min:10|max:100',
            'max_remedial' => 'nullable|integer|min:1|max:10',
            'remedial_cap_score' => 'nullable|integer|min:10|max:100',
            'certificate_number' => 'nullable|string|max:150',
            'certificate_start_number' => 'nullable|integer|min:1',
            'certificate_format' => 'nullable|string|max:150',
            'share_message' => 'nullable|string',
        ]);

        $data = [
            'organizer_id' => $request->organizer_id ?: $webinar->organizer_id,
            'certificate_template_id' => $request->certificate_template_id ?: null,
            'title' => $request->title,
            'description' => $request->description,
            'jp' => (int) $request->jp,
            'event_date' => $request->event_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'meeting_link' => $request->meeting_link,
            'youtube_link' => $request->youtube_link,
            'questionnaire_start_time' => $request->questionnaire_start_time ?: $webinar->questionnaire_start_time,
            'questionnaire_end_time' => $request->questionnaire_end_time ?: $webinar->questionnaire_end_time,
            'exam_start_time' => $request->exam_start_time ?: $webinar->exam_start_time,
            'exam_end_time' => $request->exam_end_time ?: $webinar->exam_end_time,
            'passing_grade' => (int) ($request->passing_grade ?: $webinar->passing_grade),
            'max_remedial' => (int) ($request->max_remedial ?: $webinar->max_remedial),
            'remedial_cap_score' => (int) ($request->remedial_cap_score ?: $webinar->remedial_cap_score),
            'certificate_number' => $request->certificate_number ? trim($request->certificate_number) : null,
            'certificate_start_number' => $request->certificate_start_number ? (int) $request->certificate_start_number : null,
            'certificate_format' => $request->certificate_format ? trim($request->certificate_format) : null,
            'share_message' => $request->filled('share_message') ? trim($request->share_message) : null,
        ];

        // Jika judul diubah, regenerasi slug jika bentrok
        if ($request->title !== $webinar->title) {
            $slug = Str::slug($request->title);
            if (Webinar::where('slug', $slug)->where('id', '!=', $webinar->id)->exists()) {
                $slug = "{$slug}-" . time();
            }
            $data['slug'] = $slug;
        }

        if ($request->hasFile('banner')) {
            $path = $request->file('banner')->store('banners', 'public');
            $data['banner_path'] = $path;
        }

        $oldValues = $webinar->toArray();
        $webinar->update($data);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'UPDATE_WEBINAR',
            'auditable_type' => 'App\Models\Webinar',
            'auditable_id' => $webinar->id,
            'old_values' => $oldValues,
            'new_values' => $data,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Informasi sesi webinar '{$webinar->title}' berhasil diperbarui!");
    }

    public function webinarDestroy(int $id, Request $request)
    {
        $user = $this->currentUser();
        $webinar = Webinar::findOrFail($id);
        $title = $webinar->title;
        $oldData = $webinar->toArray();

        $webinar->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'DELETE_WEBINAR',
            'auditable_type' => 'App\Models\Webinar',
            'auditable_id' => $id,
            'old_values' => $oldData,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.webinars.index')->with('success', "Sesi Webinar '{$title}' berhasil dihapus dari sistem.");
    }

    public function webinarManage(int $id)
    {
        $user = $this->currentUser();
        $webinar = Webinar::with([
            'organizer.profile',
            'registrations.user.profile',
            'attendances.user.profile',
            'examAttempts.user.profile',
            'quizQuestions',
            'questionnaireQuestions',
            'questionnaireResponses.user.profile'
        ])->findOrFail($id);
        $templates = CertificateTemplate::all();
        $organizers = User::whereIn('role', ['super_admin', 'penyelenggara'])->with('profile')->orderBy('name')->get();

        return view('admin.webinars.manage', compact('user', 'webinar', 'templates', 'organizers'));
    }

    public function webinarToggleLive(int $id, Request $request)
    {
        $user = $this->currentUser();
        $webinar = Webinar::findOrFail($id);

        $oldState = $webinar->is_live;
        $newState = !$oldState;
        $webinar->update(['is_live' => $newState]);

        // Catat Audit Trail
        AuditLog::create([
            'user_id' => $user->id,
            'action' => $newState ? 'TRIGGER_START_SESSION' : 'TRIGGER_STOP_SESSION',
            'auditable_type' => 'App\Models\Webinar',
            'auditable_id' => $webinar->id,
            'old_values' => ['is_live' => $oldState],
            'new_values' => ['is_live' => $newState],
            'ip_address' => $request->ip(),
        ]);

        $statusText = $newState ? 'Sesi Webinar TELAH DIMULAI (LIVE). Tombol presensi peserta kini aktif.' : 'Sesi Webinar dihentikan (OFFLINE). Presensi peserta ditutup.';
        return back()->with('success', $statusText);
    }

    public function webinarUpdateSchedule(int $id, Request $request)
    {
        $webinar = Webinar::findOrFail($id);
        $webinar->update($request->only([
            'questionnaire_start_time',
            'questionnaire_end_time',
            'exam_start_time',
            'exam_end_time',
            'passing_grade',
            'max_remedial',
            'remedial_cap_score',
        ]));

        return back()->with('success', 'Jendela waktu kuesioner, evaluasi, dan parameter kelulusan berhasil diperbarui.');
    }

    public function webinarUpdateMedia(int $id, Request $request)
    {
        $user = $this->currentUser();
        $webinar = Webinar::findOrFail($id);

        $request->validate([
            'youtube_link' => 'nullable|string|max:500',
            'meeting_link' => 'nullable|string|max:500',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $data = [
            'youtube_link' => $request->input('youtube_link'),
            'meeting_link' => $request->input('meeting_link'),
        ];

        if ($request->hasFile('banner')) {
            $path = $request->file('banner')->store('banners', 'public');
            $data['banner_path'] = $path;
        }

        $oldValues = [
            'youtube_link' => $webinar->youtube_link,
            'meeting_link' => $webinar->meeting_link,
            'banner_path' => $webinar->banner_path,
        ];

        $webinar->update($data);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'UPDATE_WEBINAR_MEDIA',
            'auditable_type' => 'App\Models\Webinar',
            'auditable_id' => $webinar->id,
            'old_values' => $oldValues,
            'new_values' => $data,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Media siaran YouTube Live, tautan meeting, dan background acara webinar berhasil diperbarui!');
    }

    public function webinarStoreQuestion(int $id, Request $request)
    {
        $user = $this->currentUser();
        $webinar = Webinar::findOrFail($id);

        $request->validate([
            'type' => 'required|in:quiz,questionnaire',
            'question_text' => 'required|string',
            'input_type' => 'nullable|string|in:options,text,essay',
        ]);

        $type = $request->input('type');
        $inputType = $request->input('input_type', 'options');
        $options = [];
        $correctAnswer = null;

        if ($type === 'quiz') {
            $inputType = 'options';
            $request->validate([
                'options.A' => 'required|string',
                'options.B' => 'required|string',
                'correct_answer' => 'required|in:A,B,C,D,E',
            ]);
            $rawOptions = $request->input('options', []);
            foreach (['A', 'B', 'C', 'D', 'E'] as $key) {
                if (!empty($rawOptions[$key])) {
                    $options[$key] = trim($rawOptions[$key]);
                }
            }
            $correctAnswer = strtoupper(trim($request->input('correct_answer')));
        } else {
            if ($inputType === 'text' || $inputType === 'essay') {
                $inputType = 'text';
                $options = null;
            } else {
                $inputType = 'options';
                $rawOptions = $request->input('questionnaire_options');
                if (is_array($rawOptions)) {
                    $options = array_values(array_filter(array_map('trim', $rawOptions)));
                } elseif (is_string($rawOptions)) {
                    $options = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $rawOptions))));
                }
                if (empty($options)) {
                    $options = ['Sangat Baik', 'Baik', 'Cukup', 'Kurang'];
                }
            }
        }

        $orderNumber = (int) $request->input('order_number', 0);
        if ($orderNumber <= 0) {
            $maxOrder = WebinarQuestion::where('webinar_id', $webinar->id)->where('type', $type)->max('order_number') ?? 0;
            $orderNumber = $maxOrder + 1;
        }

        $question = WebinarQuestion::create([
            'webinar_id' => $webinar->id,
            'type' => $type,
            'input_type' => $inputType,
            'question_text' => trim($request->input('question_text')),
            'options' => $options,
            'correct_answer' => $correctAnswer,
            'order_number' => $orderNumber,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'CREATE_WEBINAR_QUESTION',
            'auditable_type' => 'App\Models\WebinarQuestion',
            'auditable_id' => $question->id,
            'new_values' => [
                'webinar_id' => $webinar->id,
                'type' => $type,
                'input_type' => $inputType,
                'question_text' => $question->question_text,
                'correct_answer' => $correctAnswer,
            ],
            'ip_address' => $request->ip(),
        ]);

        $label = $type === 'quiz' ? 'Soal kuis evaluasi' : 'Pertanyaan kuesioner kepuasan';
        return back()->with('success', "{$label} berhasil ditambahkan ke bank instrumen webinar.");
    }

    public function webinarUpdateQuestion(int $id, int $questionId, Request $request)
    {
        $user = $this->currentUser();
        $webinar = Webinar::findOrFail($id);
        $question = WebinarQuestion::where('webinar_id', $webinar->id)->findOrFail($questionId);

        $request->validate([
            'question_text' => 'required|string',
            'input_type' => 'nullable|string|in:options,text,essay',
        ]);

        $type = $question->type;
        $inputType = $request->input('input_type', $question->input_type ?? 'options');
        $options = [];
        $correctAnswer = null;

        if ($type === 'quiz') {
            $inputType = 'options';
            $request->validate([
                'options.A' => 'required|string',
                'options.B' => 'required|string',
                'correct_answer' => 'required|in:A,B,C,D,E',
            ]);
            $rawOptions = $request->input('options', []);
            foreach (['A', 'B', 'C', 'D', 'E'] as $key) {
                if (!empty($rawOptions[$key])) {
                    $options[$key] = trim($rawOptions[$key]);
                }
            }
            $correctAnswer = strtoupper(trim($request->input('correct_answer')));
        } else {
            if ($inputType === 'text' || $inputType === 'essay') {
                $inputType = 'text';
                $options = null;
            } else {
                $inputType = 'options';
                $rawOptions = $request->input('questionnaire_options');
                if (is_array($rawOptions)) {
                    $options = array_values(array_filter(array_map('trim', $rawOptions)));
                } elseif (is_string($rawOptions)) {
                    $options = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $rawOptions))));
                }
                if (empty($options)) {
                    $options = ['Sangat Baik', 'Baik', 'Cukup', 'Kurang'];
                }
            }
        }

        $orderNumber = (int) $request->input('order_number', $question->order_number);

        $oldData = $question->toArray();
        $question->update([
            'input_type' => $inputType,
            'question_text' => trim($request->input('question_text')),
            'options' => $options,
            'correct_answer' => $correctAnswer,
            'order_number' => $orderNumber,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'UPDATE_WEBINAR_QUESTION',
            'auditable_type' => 'App\Models\WebinarQuestion',
            'auditable_id' => $question->id,
            'old_values' => $oldData,
            'new_values' => $question->fresh()->toArray(),
            'ip_address' => $request->ip(),
        ]);

        $label = $type === 'quiz' ? 'Soal kuis evaluasi' : 'Pertanyaan kuesioner kepuasan';
        return back()->with('success', "{$label} berhasil diperbarui.");
    }

    public function webinarDeleteQuestion(int $id, int $questionId, Request $request)
    {
        $user = $this->currentUser();
        $webinar = Webinar::findOrFail($id);
        $question = WebinarQuestion::where('webinar_id', $webinar->id)->findOrFail($questionId);

        $oldData = $question->toArray();
        $question->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'DELETE_WEBINAR_QUESTION',
            'auditable_type' => 'App\Models\WebinarQuestion',
            'auditable_id' => $questionId,
            'old_values' => $oldData,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Pertanyaan berhasil dihapus dari bank instrumen webinar.');
    }

    public function webinarImportQuestions(int $id, Request $request)
    {
        $user = $this->currentUser();
        $webinar = Webinar::findOrFail($id);

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'type' => 'required|string|in:quiz,questionnaire',
            'mode' => 'nullable|string|in:append,replace',
        ]);

        try {
            $type = $request->input('type');
            $file = $request->file('excel_file');
            $rows = ExcelImportService::parseFile($file->getRealPath(), $file->getClientOriginalName());

            if ($type === 'quiz') {
                $questions = ExcelImportService::mapQuizQuestions($rows);
            } else {
                $questions = ExcelImportService::mapQuestionnaireQuestions($rows);
            }

            if (empty($questions)) {
                return back()->with('error', 'Tidak ada butir pertanyaan yang valid dalam file yang diunggah. Pastikan format kolom sesuai dengan template.');
            }

            $mode = $request->input('mode', 'append');
            if ($mode === 'replace') {
                WebinarQuestion::where('webinar_id', $webinar->id)->where('type', $type)->delete();
            }

            $importedCount = 0;
            $currentMaxOrder = (int) (WebinarQuestion::where('webinar_id', $webinar->id)->where('type', $type)->max('order_number') ?? 0);

            foreach ($questions as $q) {
                $currentMaxOrder++;
                WebinarQuestion::create([
                    'webinar_id' => $webinar->id,
                    'type' => $type,
                    'input_type' => $q['input_type'] ?? ($type === 'quiz' ? 'options' : 'options'),
                    'order_number' => $mode === 'replace' ? $q['order_number'] : $currentMaxOrder,
                    'question_text' => $q['question_text'],
                    'options' => $q['options'],
                    'correct_answer' => $q['correct_answer'] ?? null,
                ]);
                $importedCount++;
            }

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'IMPORT_WEBINAR_QUESTIONS',
                'auditable_type' => 'App\Models\Webinar',
                'auditable_id' => $webinar->id,
                'new_values' => [
                    'type' => $type,
                    'imported_count' => $importedCount,
                    'mode' => $mode,
                    'file_name' => $file->getClientOriginalName(),
                ],
                'ip_address' => $request->ip(),
            ]);

            $label = $type === 'quiz' ? 'soal kuis evaluasi (post-test)' : 'pertanyaan kuesioner kepuasan';
            return back()->with('success', "Berhasil mengimpor {$importedCount} butir {$label} dari file Excel ke webinar '{$webinar->title}'.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses file import: ' . $e->getMessage());
        }
    }

    public function webinarDownloadQuestionTemplate(Request $request)
    {
        $type = $request->query('type', 'quiz');
        if ($type === 'questionnaire') {
            $csv = ExcelImportService::generateQuestionnaireCsvTemplate();
            $filename = 'Template_Import_Kuesioner_Webinar_BKPSDM.csv';
        } else {
            $csv = ExcelImportService::generateQuizCsvTemplate();
            $filename = 'Template_Import_Soal_Kuis_Webinar_BKPSDM.csv';
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // --- Pelatihan MOOC, Sesi Materi, & Penilaian Hasil Belajar ---

    public function coursesIndex()
    {
        $user = $this->currentUser();
        $courses = Course::withCount(['lessons', 'enrollments'])
            ->with(['organizer.profile', 'certificateTemplate'])
            ->orderBy('created_at', 'desc')
            ->get();
        $organizers = User::whereIn('role', ['super_admin', 'penyelenggara'])->with('profile')->orderBy('name')->get();
        $templates = CertificateTemplate::all();

        return view('admin.courses.index', compact('user', 'courses', 'organizers', 'templates'));
    }

    public function coursesExportAll()
    {
        return ExcelExportService::exportCourses();
    }

    public function courseExportParticipants(int $id)
    {
        $course = Course::findOrFail($id);
        return ExcelExportService::exportCourseParticipants($course);
    }

    public function courseStore(Request $request)
    {
        $user = $this->currentUser();

        $request->validate([
            'organizer_id' => 'nullable|exists:users,id',
            'certificate_template_id' => 'nullable|exists:certificate_templates,id',
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'type' => 'required|string|in:mooc,teknis,teknis_fungsional,kepemimpinan',
            'jp' => 'required|integer|min:1',
            'passing_grade' => 'required|integer|min:10|max:100',
            'max_remedial' => 'nullable|integer|min:1|max:10',
            'remedial_cap_score' => 'nullable|integer|min:10|max:100',
            'description' => 'required|string',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'time_limit_type' => 'nullable|string|in:none,relative_days,fixed_date,both',
            'duration_days' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'certificate_number' => 'nullable|string|max:150',
            'certificate_start_number' => 'nullable|integer|min:1',
            'certificate_format' => 'nullable|string|max:150',
            'certificate_lampiran_file' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:10240',
            'share_message' => 'nullable|string',
        ]);

        $bannerPath = null;
        if ($request->hasFile('banner')) {
            $bannerPath = $request->file('banner')->store('banners', 'public');
        }

        $lampiranPath = null;
        if ($request->hasFile('certificate_lampiran_file')) {
            $lampiranPath = $request->file('certificate_lampiran_file')->store('templates', 'public');
        }

        $timeLimitType = $request->input('time_limit_type', 'none') ?: 'none';
        $durationDays = in_array($timeLimitType, ['relative_days', 'both']) && $request->duration_days ? (int) $request->duration_days : null;
        
        $startDate = null;
        if (in_array($timeLimitType, ['fixed_date', 'both']) && $request->start_date) {
            $startDate = Carbon::parse($request->start_date, 'Asia/Jakarta');
        }

        $endDate = null;
        if (in_array($timeLimitType, ['fixed_date', 'both']) && $request->end_date) {
            $endDate = Carbon::parse($request->end_date, 'Asia/Jakarta');
            if (strlen(trim($request->end_date)) <= 10) {
                $endDate = $endDate->endOfDay();
            }
        }

        $course = Course::create([
            'organizer_id' => $request->organizer_id ?: $user->id,
            'certificate_template_id' => $request->certificate_template_id ?: null,
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . rand(100, 999),
            'category' => $request->category,
            'type' => $request->type,
            'jp' => (int) $request->jp,
            'passing_grade' => (int) $request->passing_grade,
            'max_remedial' => (int) ($request->max_remedial ?: 5),
            'remedial_cap_score' => (int) ($request->remedial_cap_score ?: 90),
            'description' => $request->description,
            'banner_path' => $bannerPath,
            'time_limit_type' => $timeLimitType,
            'duration_days' => $durationDays,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'certificate_number' => $request->certificate_number ? trim($request->certificate_number) : null,
            'certificate_start_number' => $request->certificate_start_number ? (int) $request->certificate_start_number : null,
            'certificate_format' => $request->certificate_format ? trim($request->certificate_format) : null,
            'certificate_lampiran_path' => $lampiranPath,
            'share_message' => $request->filled('share_message') ? trim($request->share_message) : null,
            'status' => 'published',
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'CREATE_COURSE',
            'auditable_type' => 'App\Models\Course',
            'auditable_id' => $course->id,
            'old_values' => null,
            'new_values' => $course->toArray(),
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.courses.manage', $course->id)->with('success', "Pelatihan '{$course->title}' berhasil dibuat. Silakan lanjutkan dengan mengunggah materi pelajaran tiap sesi.");
    }

    public function courseUpdate(int $id, Request $request)
    {
        $user = $this->currentUser();
        $course = Course::findOrFail($id);

        $request->validate([
            'organizer_id' => 'nullable|exists:users,id',
            'certificate_template_id' => 'nullable|exists:certificate_templates,id',
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'type' => 'required|string|in:mooc,teknis,teknis_fungsional,kepemimpinan',
            'jp' => 'required|integer|min:1',
            'passing_grade' => 'required|integer|min:10|max:100',
            'max_remedial' => 'nullable|integer|min:1|max:10',
            'remedial_cap_score' => 'nullable|integer|min:10|max:100',
            'description' => 'required|string',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'time_limit_type' => 'nullable|string|in:none,relative_days,fixed_date,both',
            'duration_days' => 'nullable|integer|min:1',
            'start_date' => 'nullable',
            'end_date' => 'nullable',
            'certificate_number' => 'nullable|string|max:150',
            'certificate_start_number' => 'nullable|integer|min:1',
            'certificate_format' => 'nullable|string|max:150',
            'certificate_lampiran_file' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:10240',
            'share_message' => 'nullable|string',
        ]);

        $timeLimitType = $request->input('time_limit_type', 'none') ?: 'none';
        $durationDays = in_array($timeLimitType, ['relative_days', 'both']) && $request->duration_days ? (int) $request->duration_days : null;
        
        $startDate = null;
        if (in_array($timeLimitType, ['fixed_date', 'both']) && $request->start_date) {
            $startDate = Carbon::parse($request->start_date, 'Asia/Jakarta');
        }

        $endDate = null;
        if (in_array($timeLimitType, ['fixed_date', 'both']) && $request->end_date) {
            $endDate = Carbon::parse($request->end_date, 'Asia/Jakarta');
            if (strlen(trim($request->end_date)) <= 10) {
                $endDate = $endDate->endOfDay();
            }
        }

        $data = [
            'organizer_id' => $request->organizer_id ?: $course->organizer_id,
            'certificate_template_id' => $request->certificate_template_id ?: null,
            'title' => $request->title,
            'category' => $request->category,
            'type' => $request->type,
            'jp' => (int) $request->jp,
            'passing_grade' => (int) $request->passing_grade,
            'max_remedial' => (int) ($request->max_remedial ?: ($course->max_remedial ?: 5)),
            'remedial_cap_score' => (int) ($request->remedial_cap_score ?: ($course->remedial_cap_score ?: 90)),
            'description' => $request->description,
            'time_limit_type' => $timeLimitType,
            'duration_days' => $durationDays,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'certificate_number' => $request->certificate_number ? trim($request->certificate_number) : null,
            'certificate_start_number' => $request->certificate_start_number ? (int) $request->certificate_start_number : null,
            'certificate_format' => $request->certificate_format ? trim($request->certificate_format) : null,
            'share_message' => $request->filled('share_message') ? trim($request->share_message) : null,
        ];

        if ($request->hasFile('banner')) {
            $data['banner_path'] = $request->file('banner')->store('banners', 'public');
        }

        if ($request->hasFile('certificate_lampiran_file')) {
            $data['certificate_lampiran_path'] = $request->file('certificate_lampiran_file')->store('templates', 'public');
        }

        // Jika judul berubah, regenerasi slug jika bentrok
        if ($request->title !== $course->title) {
            $slug = Str::slug($request->title);
            if (Course::where('slug', $slug)->where('id', '!=', $course->id)->exists()) {
                $slug = "{$slug}-" . time();
            }
            $data['slug'] = $slug;
        }

        $oldValues = $course->toArray();
        $course->update($data);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'UPDATE_COURSE',
            'auditable_type' => 'App\Models\Course',
            'auditable_id' => $course->id,
            'old_values' => $oldValues,
            'new_values' => $data,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Informasi pelatihan '{$course->title}' dan background berhasil diperbarui!");
    }

    public function courseDestroy(int $id, Request $request)
    {
        $user = $this->currentUser();
        $course = Course::findOrFail($id);
        $title = $course->title;
        $oldData = $course->toArray();

        $course->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'DELETE_COURSE',
            'auditable_type' => 'App\Models\Course',
            'auditable_id' => $id,
            'old_values' => $oldData,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.courses.index')->with('success', "Pelatihan '{$title}' berhasil dihapus dari sistem.");
    }

    public function courseManage(int $id)
    {
        $user = $this->currentUser();
        $course = Course::with(['lessons.questions', 'certificateTemplate'])->findOrFail($id);
        $organizers = User::whereIn('role', ['super_admin', 'penyelenggara'])->with('profile')->orderBy('name')->get();
        $templates = CertificateTemplate::all();

        return view('admin.courses.manage', compact('user', 'course', 'organizers', 'templates'));
    }

    public function courseStoreLesson(int $id, Request $request)
    {
        $user = $this->currentUser();
        $course = Course::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'sequence' => 'required|integer|min:1',
            'duration_minutes' => 'nullable|integer|min:1',
            'jp' => 'nullable|integer|min:1',
            'video_url' => 'nullable|string|max:500',
            'content_summary' => 'nullable|string',
            'pdf_file' => 'nullable|file|mimes:pdf|max:51200',
        ]);

        $pdfPath = null;
        if ($request->hasFile('pdf_file')) {
            $file = $request->file('pdf_file');
            $cleanName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $extension = $file->getClientOriginalExtension() ?: 'pdf';
            $fileName = 'lesson_' . $course->id . '_seq' . $request->sequence . '_' . time() . '_' . $cleanName . '.' . $extension;
            $pdfPath = $file->storeAs('lessons', $fileName, 'public');
        } else {
            $pdfPath = 'materials/materi_sesi_' . $request->sequence . '_' . Str::slug($request->title) . '.pdf';
        }

        $lesson = CourseLesson::create([
            'course_id' => $course->id,
            'title' => $request->title,
            'sequence' => $request->sequence,
            'video_url' => $request->video_url ?: 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'duration_seconds' => ((int) ($request->duration_minutes ?: 15)) * 60,
            'jp' => $request->filled('jp') ? (int) $request->jp : max(1, (int) round(((int) ($request->duration_minutes ?: 15)) / 45)),
            'pdf_path' => $pdfPath,
            'content_summary' => $request->content_summary ?: 'Materi sesi pembelajaran BKPSDM Kab. Pasuruan.',
            'has_quiz' => $request->boolean('has_quiz', true),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'UPLOAD_COURSE_LESSON',
            'auditable_type' => 'App\Models\CourseLesson',
            'auditable_id' => $lesson->id,
            'old_values' => null,
            'new_values' => $lesson->toArray(),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Materi Sesi Bab {$request->sequence}: '{$request->title}' berhasil diunggah dan siap dipelajari peserta ASN.");
    }

    public function courseLessonUpdate(int $courseId, int $lessonId, Request $request)
    {
        $user = $this->currentUser();
        $course = Course::findOrFail($courseId);
        $lesson = CourseLesson::where('course_id', $course->id)->findOrFail($lessonId);

        $request->validate([
            'title' => 'required|string|max:255',
            'sequence' => 'required|integer|min:1',
            'duration_minutes' => 'nullable|integer|min:1',
            'jp' => 'nullable|integer|min:1',
            'video_url' => 'nullable|string|max:500',
            'content_summary' => 'nullable|string',
            'pdf_file' => 'nullable|file|mimes:pdf|max:51200',
        ]);

        $data = [
            'title' => $request->title,
            'sequence' => (int) $request->sequence,
            'video_url' => $request->video_url ?: $lesson->video_url,
            'duration_seconds' => ((int) ($request->duration_minutes ?: round($lesson->duration_seconds / 60))) * 60,
            'jp' => $request->filled('jp') ? (int) $request->jp : $lesson->jp,
            'content_summary' => $request->content_summary,
            'has_quiz' => $request->boolean('has_quiz', false),
        ];

        if ($request->hasFile('pdf_file')) {
            // Hapus file lama jika tersimpan di disk public
            if ($lesson->pdf_path && Storage::disk('public')->exists($lesson->pdf_path)) {
                Storage::disk('public')->delete($lesson->pdf_path);
            }

            $file = $request->file('pdf_file');
            $cleanName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $extension = $file->getClientOriginalExtension() ?: 'pdf';
            $fileName = 'lesson_' . $course->id . '_seq' . $request->sequence . '_' . time() . '_' . $cleanName . '.' . $extension;
            $data['pdf_path'] = $file->storeAs('lessons', $fileName, 'public');
        }

        $oldValues = $lesson->toArray();
        $lesson->update($data);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'UPDATE_COURSE_LESSON',
            'auditable_type' => 'App\Models\CourseLesson',
            'auditable_id' => $lesson->id,
            'old_values' => $oldValues,
            'new_values' => $lesson->fresh()->toArray(),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Sesi Pembelajaran {$lesson->sequence}: '{$lesson->title}' berhasil diperbarui.");
    }

    public function courseLessonDestroy(int $courseId, int $lessonId, Request $request)
    {
        $user = $this->currentUser();
        $course = Course::findOrFail($courseId);
        $lesson = CourseLesson::where('course_id', $course->id)->findOrFail($lessonId);

        $oldData = $lesson->toArray();
        $sequence = $lesson->sequence;
        $title = $lesson->title;

        // Hapus file PDF jika ada di disk storage public
        if ($lesson->pdf_path && Storage::disk('public')->exists($lesson->pdf_path)) {
            Storage::disk('public')->delete($lesson->pdf_path);
        }

        // Hapus pertanyaan kuis terkait jika ada
        $lesson->questions()->delete();
        $lesson->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'DELETE_COURSE_LESSON',
            'auditable_type' => 'App\Models\CourseLesson',
            'auditable_id' => $lessonId,
            'old_values' => $oldData,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Sesi {$sequence}: '{$title}' berhasil dihapus dari pelatihan.");
    }

    public function courseLessonStoreQuestion(int $courseId, int $lessonId, Request $request)
    {
        $user = $this->currentUser();
        $course = Course::findOrFail($courseId);
        $lesson = CourseLesson::where('course_id', $course->id)->findOrFail($lessonId);

        $request->validate([
            'question_text' => 'required|string',
            'options.A' => 'required|string',
            'options.B' => 'required|string',
            'correct_answer' => 'required|in:A,B,C,D,E',
            'order_number' => 'nullable|integer',
        ]);

        $rawOptions = $request->input('options', []);
        $options = [];
        foreach (['A', 'B', 'C', 'D', 'E'] as $key) {
            if (!empty($rawOptions[$key])) {
                $options[$key] = trim($rawOptions[$key]);
            }
        }

        $orderNumber = (int) ($request->input('order_number') ?: ($lesson->questions()->count() + 1));

        $question = CourseLessonQuestion::create([
            'course_lesson_id' => $lesson->id,
            'question_text' => trim($request->input('question_text')),
            'options' => $options,
            'correct_answer' => strtoupper(trim($request->input('correct_answer'))),
            'order_number' => $orderNumber,
        ]);

        if (!$lesson->has_quiz) {
            $lesson->update(['has_quiz' => true]);
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'CREATE_COURSE_LESSON_QUESTION',
            'auditable_type' => 'App\Models\CourseLessonQuestion',
            'auditable_id' => $question->id,
            'old_values' => null,
            'new_values' => $question->toArray(),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Pertanyaan kuis berhasil ditambahkan ke Sesi {$lesson->sequence}: '{$lesson->title}'.");
    }

    public function courseLessonUpdateQuestion(int $courseId, int $lessonId, int $questionId, Request $request)
    {
        $user = $this->currentUser();
        $course = Course::findOrFail($courseId);
        $lesson = CourseLesson::where('course_id', $course->id)->findOrFail($lessonId);
        $question = CourseLessonQuestion::where('course_lesson_id', $lesson->id)->findOrFail($questionId);

        $request->validate([
            'question_text' => 'required|string',
            'options.A' => 'required|string',
            'options.B' => 'required|string',
            'correct_answer' => 'required|in:A,B,C,D,E',
        ]);

        $rawOptions = $request->input('options', []);
        $options = [];
        foreach (['A', 'B', 'C', 'D', 'E'] as $key) {
            if (!empty($rawOptions[$key])) {
                $options[$key] = trim($rawOptions[$key]);
            }
        }

        $oldData = $question->toArray();
        $question->update([
            'question_text' => trim($request->input('question_text')),
            'options' => $options,
            'correct_answer' => strtoupper(trim($request->input('correct_answer'))),
            'order_number' => (int) $request->input('order_number', $question->order_number),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'UPDATE_COURSE_LESSON_QUESTION',
            'auditable_type' => 'App\Models\CourseLessonQuestion',
            'auditable_id' => $question->id,
            'old_values' => $oldData,
            'new_values' => $question->fresh()->toArray(),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Pertanyaan kuis Sesi {$lesson->sequence} berhasil diperbarui.");
    }

    public function courseLessonDeleteQuestion(int $courseId, int $lessonId, int $questionId, Request $request)
    {
        $user = $this->currentUser();
        $course = Course::findOrFail($courseId);
        $lesson = CourseLesson::where('course_id', $course->id)->findOrFail($lessonId);
        $question = CourseLessonQuestion::where('course_lesson_id', $lesson->id)->findOrFail($questionId);

        $oldData = $question->toArray();
        $question->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'DELETE_COURSE_LESSON_QUESTION',
            'auditable_type' => 'App\Models\CourseLessonQuestion',
            'auditable_id' => $questionId,
            'old_values' => $oldData,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Pertanyaan kuis berhasil dihapus dari Sesi {$lesson->sequence}.");
    }

    public function courseLessonImportQuestions(int $courseId, int $lessonId, Request $request)
    {
        $user = $this->currentUser();
        $course = Course::findOrFail($courseId);
        $lesson = CourseLesson::where('course_id', $course->id)->findOrFail($lessonId);

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'mode' => 'nullable|string|in:append,replace',
        ]);

        try {
            $file = $request->file('excel_file');
            $rows = ExcelImportService::parseFile($file->getRealPath(), $file->getClientOriginalName());
            $questions = ExcelImportService::mapQuizQuestions($rows);

            if (empty($questions)) {
                return back()->with('error', 'Tidak ada butir soal kuis valid yang ditemukan dalam file. Pastikan menggunakan format template Excel yang sesuai.');
            }

            $mode = $request->input('mode', 'append');
            if ($mode === 'replace') {
                CourseLessonQuestion::where('course_lesson_id', $lesson->id)->delete();
            }

            $importedCount = 0;
            $currentMaxOrder = (int) (CourseLessonQuestion::where('course_lesson_id', $lesson->id)->max('order_number') ?? 0);

            foreach ($questions as $q) {
                $currentMaxOrder++;
                CourseLessonQuestion::create([
                    'course_lesson_id' => $lesson->id,
                    'order_number' => $mode === 'replace' ? $q['order_number'] : $currentMaxOrder,
                    'question_text' => $q['question_text'],
                    'options' => $q['options'],
                    'correct_answer' => $q['correct_answer'],
                ]);
                $importedCount++;
            }

            if (!$lesson->has_quiz) {
                $lesson->update(['has_quiz' => true]);
            }

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'IMPORT_COURSE_LESSON_QUESTIONS',
                'auditable_type' => 'App\Models\CourseLesson',
                'auditable_id' => $lesson->id,
                'new_values' => [
                    'imported_count' => $importedCount,
                    'mode' => $mode,
                    'file_name' => $file->getClientOriginalName(),
                ],
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', "Berhasil mengimpor {$importedCount} butir soal kuis dari Excel ke Sesi {$lesson->sequence}: '{$lesson->title}'.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses file import: ' . $e->getMessage());
        }
    }

    public function courseDownloadQuestionTemplate(Request $request)
    {
        $csv = ExcelImportService::generateQuizCsvTemplate();
        $filename = 'Template_Import_Soal_Kuis_Pelatihan_BKPSDM.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function courseResults(int $id)
    {
        $user = $this->currentUser();
        $course = Course::with(['lessons'])->findOrFail($id);
        $enrollments = CourseEnrollment::with(['user.profile'])
            ->where('course_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.courses.results', compact('user', 'course', 'enrollments'));
    }

    public function courseGradeStudent(int $id, int $enrollmentId, Request $request)
    {
        $user = $this->currentUser();
        $course = Course::findOrFail($id);
        $enrollment = CourseEnrollment::where('course_id', $course->id)->findOrFail($enrollmentId);

        $request->validate([
            'final_score' => 'required|numeric|min:0|max:100',
            'status' => 'required|string|in:passed,revision,in_progress',
            'instructor_feedback' => 'nullable|string',
        ]);

        $oldValues = $enrollment->only(['final_score', 'status', 'instructor_feedback']);

        $enrollment->update([
            'final_score' => $request->final_score,
            'status' => $request->status,
            'instructor_feedback' => $request->instructor_feedback,
            'completed_at' => $request->status === 'passed' ? Carbon::now() : null,
            'progress_percentage' => $request->status === 'passed' ? 100 : $enrollment->progress_percentage,
        ]);

        if ($request->status === 'passed') {
            Certificate::issueForCourse($course, $enrollment->user);
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'GRADE_COURSE_PARTICIPANT',
            'auditable_type' => 'App\Models\CourseEnrollment',
            'auditable_id' => $enrollment->id,
            'old_values' => $oldValues,
            'new_values' => $enrollment->only(['final_score', 'status', 'instructor_feedback']),
            'ip_address' => $request->ip(),
        ]);

        $statusMsg = $request->status === 'passed' 
            ? "Penilaian hasil belajar peserta ({$enrollment->user->name}) disetujui LULUS dengan skor: {$request->final_score}. E-Sertifikat resmi telah berhasil diterbitkan!"
            : ($request->status === 'revision'
                ? "Status tugas peserta ({$enrollment->user->name}) ditandai 'Perlu Revisi'. Catatan revisi telah dikirimkan ke peserta."
                : "Penilaian peserta ({$enrollment->user->name}) disimpan dengan status Dalam Proses.");

        return back()->with('success', $statusMsg);
    }

    public function certificatesDesigner(Request $request)
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Akses Ditolak: Desainer template sertifikat visual khusus untuk Super Admin BKPSDM.');
        }

        $templates = CertificateTemplate::with(['webinar', 'course'])->orderBy('id')->get();
        $templateId = $request->query('template_id');
        $template = $templateId ? CertificateTemplate::find($templateId) : null;
        if (!$template) {
            $template = CertificateTemplate::where('is_default', true)->first() ?: CertificateTemplate::first();
        }
        if (!$template) {
            $template = CertificateTemplate::create([
                'name' => 'Blanko Resmi E-Sertifikat Diklat BKPSDM Pasuruan',
                'is_default' => true,
                'number_format' => '893.3/{NOMOR}/BKPSDM-PAS/{TAHUN}',
                'start_number' => 1,
                'signer_name' => 'Drs. H. Ahmad Fauzi, M.Si',
                'signer_nip' => '19720415 199603 1 002',
                'signer_position' => 'Kepala Badan Kepegawaian dan Pengembangan Sumber Daya Manusia',
                'issue_city' => 'Bangil, Pasuruan',
            ]);
            $templates = CertificateTemplate::with(['webinar', 'course'])->orderBy('id')->get();
        }

        $webinars = Webinar::orderBy('title')->get();
        $courses = Course::orderBy('title')->get();

        return view('admin.certificates.designer', compact('user', 'template', 'templates', 'webinars', 'courses'));
    }

    public function certificatesSaveCoordinates(Request $request)
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Akses Ditolak.');
        }

        $template = ($request->filled('template_id')) 
            ? CertificateTemplate::find($request->template_id) 
            : (CertificateTemplate::where('is_default', true)->first() ?: CertificateTemplate::first());

        if ($template && $request->has('layout_coordinates')) {
            $template->update([
                'layout_coordinates' => json_decode($request->layout_coordinates, true),
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'UPDATE_CERTIFICATE_COORDINATES',
                'description' => "Super Admin ({$user->name}) memperbarui titik pemetaan koordinat visual template e-sertifikat: {$template->name}.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return redirect()->route('admin.certificates.designer', ['template_id' => $template?->id])
            ->with('success', 'Titik koordinat visual template e-sertifikat berhasil disimpan ke basis data.');
    }

    public function certificatesUploadBlanko(Request $request)
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Akses Ditolak: Hanya Super Admin yang dapat mengganti blanko e-sertifikat.');
        }

        $request->validate([
            'template_id' => 'nullable|exists:certificate_templates,id',
            'template_name' => 'nullable|string|max:255',
            'blanko_file' => 'required|image|mimes:png,jpg,jpeg,webp|max:10240',
        ]);

        $template = ($request->filled('template_id'))
            ? CertificateTemplate::find($request->template_id)
            : (CertificateTemplate::where('is_default', true)->first() ?: CertificateTemplate::first());

        if (!$template) {
            $template = new CertificateTemplate();
            $template->name = 'Blanko Resmi E-Sertifikat Diklat BKPSDM Pasuruan';
            $template->is_default = true;
        }

        if ($request->hasFile('blanko_file')) {
            $file = $request->file('blanko_file');
            $fileName = 'blanko_' . time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('templates', $fileName, 'public');

            $template->background_path = $path;
        }

        if ($request->filled('template_name')) {
            $template->name = $request->template_name;
        }

        $template->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'UPLOAD_CERTIFICATE_TEMPLATE',
            'description' => "Super Admin mengunggah berkas blanko sertifikat baru: {$template->background_path} untuk template {$template->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.certificates.designer', ['template_id' => $template->id])
            ->with('success', 'Blanko e-sertifikat baru berhasil diunggah dan diterapkan pada canvas desainer.');
    }

    public function certificatesUploadLampiranBlanko(Request $request)
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Akses Ditolak: Hanya Super Admin yang dapat mengganti blanko lampiran e-sertifikat.');
        }

        $request->validate([
            'template_id' => 'nullable|exists:certificate_templates,id',
            'lampiran_blanko_file' => 'required|image|mimes:png,jpg,jpeg,webp|max:10240',
        ]);

        $template = ($request->filled('template_id'))
            ? CertificateTemplate::find($request->template_id)
            : (CertificateTemplate::where('is_default', true)->first() ?: CertificateTemplate::first());

        if (!$template) {
            $template = new CertificateTemplate();
            $template->name = 'Blanko Resmi E-Sertifikat Diklat BKPSDM Pasuruan';
            $template->is_default = true;
        }

        if ($request->hasFile('lampiran_blanko_file')) {
            $file = $request->file('lampiran_blanko_file');
            $fileName = 'lampiran_blanko_' . time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('templates', $fileName, 'public');

            $template->lampiran_background_path = $path;
        }

        $template->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'UPLOAD_CERTIFICATE_LAMPIRAN_TEMPLATE',
            'description' => "Super Admin mengunggah berkas blanko lampiran sertifikat baru: {$template->lampiran_background_path} untuk template {$template->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.certificates.designer', ['template_id' => $template->id])
            ->with('success', 'Blanko lampiran e-sertifikat (halaman 2 / transkrip materi & JP) berhasil diunggah dan diterapkan.')
            ->with('active_page', 'page2');
    }

    public function certificatesSaveSpecimens(Request $request)
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Akses Ditolak.');
        }

        $request->validate([
            'template_id' => 'nullable|exists:certificate_templates,id',
            'number_format' => 'required|string|max:100',
            'start_number' => 'nullable|integer|min:1',
            'signer_name' => 'required|string|max:150',
            'signer_nip' => 'nullable|string|max:50',
            'signer_position' => 'required|string|max:200',
            'issue_city' => 'required|string|max:100',
            'signature_file' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:5120',
            'tte_barcode_file' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:5120',
        ]);

        $template = ($request->filled('template_id'))
            ? CertificateTemplate::find($request->template_id)
            : (CertificateTemplate::where('is_default', true)->first() ?: CertificateTemplate::first());

        if (!$template) {
            $template = new CertificateTemplate();
            $template->name = 'Blanko Resmi E-Sertifikat Diklat BKPSDM Pasuruan';
            $template->is_default = true;
        }

        $template->number_format = $request->number_format;
        $template->start_number = $request->filled('start_number') ? (int) $request->start_number : 1;
        $template->signer_name = $request->signer_name;
        $template->signer_nip = $request->signer_nip;
        $template->signer_position = $request->signer_position;
        $template->issue_city = $request->issue_city;

        if ($request->hasFile('signature_file')) {
            $sig = $request->file('signature_file');
            $fileName = 'ttd_' . time() . '.' . $sig->getClientOriginalExtension();
            $path = $sig->storeAs('specimens', $fileName, 'public');
            $template->signature_path = $path;
        }

        if ($request->hasFile('tte_barcode_file')) {
            $tte = $request->file('tte_barcode_file');
            $fileName = 'tte_' . time() . '.' . $tte->getClientOriginalExtension();
            $path = $tte->storeAs('specimens', $fileName, 'public');
            $template->tte_barcode_path = $path;
        }

        $template->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'UPDATE_CERTIFICATE_SPECIMENS',
            'description' => "Super Admin ({$user->name}) memperbarui format nomor surat, data pejabat, dan spesimen tanda tangan e-sertifikat template {$template->name}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.certificates.designer', ['template_id' => $template->id])
            ->with('success', 'Pengaturan format nomor surat dan spesimen tanda tangan pejabat berhasil diperbarui.');
    }

    public function certificatesStoreTemplate(Request $request)
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Akses Ditolak.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'target_type' => 'required|in:default,webinar,course',
            'webinar_id' => 'nullable|exists:webinars,id',
            'course_id' => 'nullable|exists:courses,id',
            'blanko_file' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:10240',
            'lampiran_blanko_file' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:10240',
        ]);

        $base = CertificateTemplate::where('is_default', true)->first() ?: CertificateTemplate::first();
        $isDefault = ($request->target_type === 'default');

        if ($isDefault) {
            CertificateTemplate::where('id', '>', 0)->update(['is_default' => false]);
        }

        $template = new CertificateTemplate();
        $template->name = $request->name;
        $template->is_default = $isDefault;
        $template->webinar_id = ($request->target_type === 'webinar') ? $request->webinar_id : null;
        $template->course_id = ($request->target_type === 'course') ? $request->course_id : null;
        $template->layout_coordinates = $base?->layout_coordinates;
        $template->number_format = $base?->number_format ?: '893.3/{NOMOR}/BKPSDM-PAS/{TAHUN}';
        $template->start_number = 1;
        $template->signer_name = $base?->signer_name ?: 'Drs. H. Ahmad Fauzi, M.Si';
        $template->signer_nip = $base?->signer_nip ?: '19720415 199603 1 002';
        $template->signer_position = $base?->signer_position ?: 'Kepala Badan Kepegawaian dan Pengembangan Sumber Daya Manusia';
        $template->issue_city = $base?->issue_city ?: 'Bangil, Pasuruan';
        $template->signature_path = $base?->signature_path;
        $template->stamp_path = $base?->stamp_path;
        $template->tte_barcode_path = $base?->tte_barcode_path;

        if ($request->hasFile('blanko_file')) {
            $file = $request->file('blanko_file');
            $fileName = 'blanko_' . time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $template->background_path = $file->storeAs('templates', $fileName, 'public');
        } elseif ($base) {
            $template->background_path = $base->background_path;
        }

        if ($request->hasFile('lampiran_blanko_file')) {
            $lfile = $request->file('lampiran_blanko_file');
            $lName = 'lampiran_blanko_' . time() . '_' . Str::slug(pathinfo($lfile->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $lfile->getClientOriginalExtension();
            $template->lampiran_background_path = $lfile->storeAs('templates', $lName, 'public');
        } elseif ($base) {
            $template->lampiran_background_path = $base->lampiran_background_path;
        }

        $template->save();

        if ($template->webinar_id) {
            Webinar::where('id', $template->webinar_id)->update(['certificate_template_id' => $template->id]);
        }
        if ($template->course_id) {
            Course::where('id', $template->course_id)->update(['certificate_template_id' => $template->id]);
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'CREATE_CERTIFICATE_TEMPLATE',
            'description' => "Super Admin ({$user->name}) membuat template e-sertifikat baru: '{$template->name}' dengan peruntukan {$template->target_badge}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.certificates.designer', ['template_id' => $template->id])
            ->with('success', "Template baru '{$template->name}' berhasil dibuat dan siap disesuaikan pada desainer visual.");
    }

    public function certificatesUpdateTarget(Request $request)
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Akses Ditolak.');
        }

        $request->validate([
            'template_id' => 'required|exists:certificate_templates,id',
            'name' => 'required|string|max:255',
            'target_type' => 'required|in:default,webinar,course',
            'webinar_id' => 'nullable|exists:webinars,id',
            'course_id' => 'nullable|exists:courses,id',
        ]);

        $template = CertificateTemplate::findOrFail($request->template_id);
        $isDefault = ($request->target_type === 'default');

        if ($isDefault) {
            CertificateTemplate::where('id', '!=', $template->id)->update(['is_default' => false]);
            $template->update([
                'name' => $request->name,
                'is_default' => true,
                'webinar_id' => null,
                'course_id' => null,
            ]);
        } elseif ($request->target_type === 'webinar') {
            $template->update([
                'name' => $request->name,
                'is_default' => false,
                'webinar_id' => $request->webinar_id,
                'course_id' => null,
            ]);
            if ($request->webinar_id) {
                Webinar::where('id', $request->webinar_id)->update(['certificate_template_id' => $template->id]);
            }
        } elseif ($request->target_type === 'course') {
            $template->update([
                'name' => $request->name,
                'is_default' => false,
                'webinar_id' => null,
                'course_id' => $request->course_id,
            ]);
            if ($request->course_id) {
                Course::where('id', $request->course_id)->update(['certificate_template_id' => $template->id]);
            }
        }

        return redirect()->route('admin.certificates.designer', ['template_id' => $template->id])
            ->with('success', "Pengaturan target peruntukan template '{$template->name}' berhasil diperbarui ({$template->target_badge}).");
    }

    public function certificatesDeleteTemplate(int $id)
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Akses Ditolak.');
        }

        if (CertificateTemplate::count() <= 1) {
            return back()->with('error', 'Gagal menghapus: Harus tersisa minimal 1 template sertifikat pada sistem.');
        }

        $template = CertificateTemplate::findOrFail($id);
        $wasDefault = $template->is_default;
        $name = $template->name;

        // Lepas referensi dari webinars / courses
        Webinar::where('certificate_template_id', $template->id)->update(['certificate_template_id' => null]);
        Course::where('certificate_template_id', $template->id)->update(['certificate_template_id' => null]);

        $template->delete();

        if ($wasDefault) {
            $next = CertificateTemplate::first();
            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        return redirect()->route('admin.certificates.designer')
            ->with('success', "Template sertifikat '{$name}' berhasil dihapus.");
    }

    /**
     * Menu Khusus Super Admin: Penerbitan Sertifikat Manual
     */
    public function certificatesManualIndex(Request $request)
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Akses Ditolak: Menu pembuatan sertifikat manual khusus untuk Super Admin BKPSDM.');
        }

        $webinars = Webinar::orderBy('event_date', 'desc')->get();
        $courses = Course::orderBy('created_at', 'desc')->get();
        $templates = CertificateTemplate::all();

        $search = $request->query('q');
        $type = $request->query('type', 'all');

        $query = Certificate::with(['user.profile', 'template', 'webinar', 'course']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                  ->orWhere('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('training_title', 'like', "%{$search}%")
                  ->orWhere('hash_token', 'like', "%{$search}%");
            });
        }

        if ($type === 'webinar') {
            $query->whereNotNull('webinar_id');
        } elseif ($type === 'course') {
            $query->whereNotNull('course_id');
        }

        $certificates = $query->orderBy('issued_at', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();
        $totalCertificates = Certificate::count();
        $totalWebinars = Webinar::count();
        $totalCourses = Course::count();

        return view('admin.certificates.manual', compact(
            'user',
            'webinars',
            'courses',
            'templates',
            'certificates',
            'totalCertificates',
            'totalWebinars',
            'totalCourses',
            'search',
            'type'
        ));
    }

    /**
     * Endpoint API AJAX: Pencarian Pegawai / Pengguna untuk Penerbitan Sertifikat
     */
    public function certificatesManualSearchUsers(Request $request)
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $users = User::with('profile')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('nip', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
                      ->orWhereHas('profile', function ($p) use ($q) {
                          $p->where('jabatan', 'like', "%{$q}%")
                            ->orWhere('unit_kerja', 'like', "%{$q}%");
                      });
            })
            ->take(30)
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->profile?->formatted_full_name ?? $u->name,
                    'nip' => $u->nip ?: '-',
                    'unit_kerja' => $u->profile?->unit_kerja ?? $u->unit_kerja ?? 'Pemerintah Kabupaten Pasuruan',
                    'jabatan' => $u->profile?->jabatan ?? $u->jabatan ?? '-',
                    'email' => $u->email,
                    'is_asn' => (bool) $u->nip,
                ];
            });

        return response()->json($users);
    }

    /**
     * Endpoint API AJAX: Ambil Peserta Terdaftar pada Kegiatan Tertentu
     */
    public function certificatesManualGetParticipants(Request $request)
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $type = $request->input('type');
        $id = $request->input('id');

        if ($type === 'webinar') {
            $webinar = Webinar::findOrFail($id);
            $registrations = \App\Models\WebinarRegistration::with('user.profile')
                ->where('webinar_id', $webinar->id)
                ->orderBy('registered_at', 'desc')
                ->get();

            $issuedCertificates = Certificate::where('webinar_id', $webinar->id)
                ->pluck('certificate_number', 'user_id')
                ->toArray();

            $participants = $registrations->map(function ($reg) use ($issuedCertificates) {
                $u = $reg->user;
                if (!$u) return null;
                $hasCert = array_key_exists($u->id, $issuedCertificates);
                return [
                    'id' => $u->id,
                    'name' => $u->profile?->formatted_full_name ?? $u->name,
                    'nip' => $u->nip ?: '-',
                    'unit_kerja' => $u->profile?->unit_kerja ?? $u->unit_kerja ?? '-',
                    'jabatan' => $u->profile?->jabatan ?? $u->jabatan ?? '-',
                    'has_certificate' => $hasCert,
                    'certificate_number' => $hasCert ? $issuedCertificates[$u->id] : null,
                ];
            })->filter()->values();

            return response()->json([
                'jp' => $webinar->jp,
                'template_id' => $webinar->certificate_template_id ?: ($webinar->resolveCertificateTemplate()?->id),
                'event_date' => $webinar->event_date?->format('Y-m-d') ?: date('Y-m-d'),
                'title' => $webinar->title,
                'participants' => $participants,
            ]);
        } elseif ($type === 'course') {
            $course = Course::findOrFail($id);
            $enrollments = CourseEnrollment::with('user.profile')
                ->where('course_id', $course->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $issuedCertificates = Certificate::where('course_id', $course->id)
                ->pluck('certificate_number', 'user_id')
                ->toArray();

            $participants = $enrollments->map(function ($enr) use ($issuedCertificates) {
                $u = $enr->user;
                if (!$u) return null;
                $hasCert = array_key_exists($u->id, $issuedCertificates);
                return [
                    'id' => $u->id,
                    'name' => $u->profile?->formatted_full_name ?? $u->name,
                    'nip' => $u->nip ?: '-',
                    'unit_kerja' => $u->profile?->unit_kerja ?? $u->unit_kerja ?? '-',
                    'jabatan' => $u->profile?->jabatan ?? $u->jabatan ?? '-',
                    'has_certificate' => $hasCert,
                    'certificate_number' => $hasCert ? $issuedCertificates[$u->id] : null,
                ];
            })->filter()->values();

            return response()->json([
                'jp' => $course->jp,
                'template_id' => $course->certificate_template_id ?: ($course->resolveCertificateTemplate()?->id),
                'event_date' => date('Y-m-d'),
                'title' => $course->title,
                'participants' => $participants,
            ]);
        }

        return response()->json(['error' => 'Tipe kegiatan tidak valid.'], 400);
    }

    /**
     * Proses Pembuatan / Penerbitan E-Sertifikat Manual oleh Super Admin
     */
    public function certificatesManualStore(Request $request)
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Akses Ditolak.');
        }

        $request->validate([
            'training_type' => 'required|in:webinar,course',
            'webinar_id' => 'required_if:training_type,webinar|nullable|exists:webinars,id',
            'course_id' => 'required_if:training_type,course|nullable|exists:courses,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'issued_at' => 'required|date',
            'template_id' => 'nullable|exists:certificate_templates,id',
            'custom_certificate_number' => 'nullable|string|max:100',
            'jp' => 'nullable|integer|min:1|max:500',
        ], [
            'user_ids.required' => 'Pilih minimal satu pegawai untuk diterbitkan sertifikatnya.',
            'user_ids.min' => 'Pilih minimal satu pegawai untuk diterbitkan sertifikatnya.',
            'issued_at.required' => 'Tanggal sertifikat wajib diisi secara manual.',
            'issued_at.date' => 'Format tanggal sertifikat tidak valid.',
        ]);

        if ($request->training_type === 'webinar') {
            $training = Webinar::with(['organizer.profile'])->findOrFail($request->webinar_id);
            $webinarId = $training->id;
            $courseId = null;
        } else {
            $training = Course::with(['organizer.profile'])->findOrFail($request->course_id);
            $webinarId = null;
            $courseId = $training->id;
        }

        $template = $request->filled('template_id')
            ? CertificateTemplate::find($request->template_id)
            : $training->resolveCertificateTemplate();

        $templateId = $template?->id ?: 1;
        $jp = $request->filled('jp') ? (int) $request->jp : $training->jp;
        $issuedAt = Carbon::parse($request->issued_at);
        $organizerUnit = $training->organizer?->unit_kerja 
            ?? $training->organizer?->profile?->unit_kerja 
            ?? 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia Kabupaten Pasuruan';

        $createdCount = 0;
        $updatedCount = 0;

        foreach ($request->user_ids as $userId) {
            $targetUser = User::with('profile')->find($userId);
            if (!$targetUser) continue;

            $existing = Certificate::where('user_id', $targetUser->id)
                ->when($webinarId, fn($q) => $q->where('webinar_id', $webinarId))
                ->when($courseId, fn($q) => $q->where('course_id', $courseId))
                ->first();

            if ($existing) {
                $updateData = [
                    'issued_at' => $issuedAt,
                    'template_id' => $templateId,
                    'jp' => $jp,
                    'training_title' => $training->title,
                    'organizer_unit_kerja' => $organizerUnit,
                ];
                if ($request->filled('custom_certificate_number') && count($request->user_ids) === 1) {
                    $updateData['certificate_number'] = trim($request->custom_certificate_number);
                }
                $existing->update($updateData);
                $updatedCount++;
            } else {
                $certNum = (count($request->user_ids) === 1 && $request->filled('custom_certificate_number'))
                    ? trim($request->custom_certificate_number)
                    : Certificate::generateCertificateNumber($training);

                $hashToken = Certificate::generateVerificationCode($issuedAt->year);
                $pdfPath = $webinarId
                    ? "certificates/webinar_{$webinarId}_user_{$targetUser->id}.pdf"
                    : "certificates/course_{$courseId}_user_{$targetUser->id}.pdf";

                Certificate::create([
                    'certificate_number' => $certNum,
                    'hash_token' => $hashToken,
                    'user_id' => $targetUser->id,
                    'nip' => $targetUser->nip,
                    'nik' => $targetUser->profile?->nik,
                    'nama_lengkap' => $targetUser->profile?->formatted_full_name ?? $targetUser->name,
                    'jabatan' => $targetUser->profile?->jabatan ?? $targetUser->jabatan,
                    'unit_kerja' => $targetUser->profile?->unit_kerja ?? $targetUser->unit_kerja,
                    'webinar_id' => $webinarId,
                    'course_id' => $courseId,
                    'template_id' => $templateId,
                    'training_title' => $training->title,
                    'organizer_unit_kerja' => $organizerUnit,
                    'jp' => $jp,
                    'pdf_path' => $pdfPath,
                    'issued_at' => $issuedAt, // Tanggal manual dari Super Admin!
                ]);
                $createdCount++;
            }
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'MANUAL_ISSUE_CERTIFICATES',
            'description' => "Super Admin ({$user->name}) menerbitkan sertifikat manual untuk kegiatan '{$training->title}' sebanyak {$createdCount} sertifikat baru dan memperbarui {$updatedCount} sertifikat dengan tanggal manual: {$issuedAt->isoFormat('D MMMM Y')}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $msg = "Berhasil memproses sertifikat: {$createdCount} sertifikat baru berhasil diterbitkan";
        if ($updatedCount > 0) {
            $msg .= ", {$updatedCount} sertifikat yang sudah ada diperbarui";
        }
        $msg .= " dengan tanggal sertifikat: " . $issuedAt->isoFormat('D MMMM Y') . ".";

        return redirect()->route('admin.certificates.manual')->with('success', $msg);
    }

    /**
     * Hapus / Batalkan E-Sertifikat Tertentu
     */
    public function certificatesManualDestroy(int $id)
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Akses Ditolak.');
        }

        $cert = Certificate::findOrFail($id);
        $num = $cert->certificate_number;
        $name = $cert->nama_lengkap;
        $title = $cert->training_title;

        $cert->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'DELETE_CERTIFICATE',
            'description' => "Super Admin ({$user->name}) menghapus/membatalkan e-sertifikat ({$num}) atas nama {$name} untuk kegiatan '{$title}'.",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->route('admin.certificates.manual')
            ->with('success', "E-Sertifikat ({$num}) atas nama {$name} berhasil dibatalkan dan dihapus dari sistem.");
    }

    /**
     * Cetak / Unduh Dokumen PDF E-Sertifikat
     */
    public function certificatesPrint(int $id)
    {
        $user = $this->currentUser();
        $cert = Certificate::with(['template', 'webinar.organizer.profile', 'course.organizer.profile', 'course.lessons', 'user.profile'])
            ->findOrFail($id);

        $defaultTemplate = CertificateTemplate::first();

        return view('admin.certificates.print', compact('cert', 'defaultTemplate'));
    }

    public function certificatesExportAll()
    {
        return ExcelExportService::exportCertificates();
    }

    public function kepemimpinanReview()
    {
        $user = $this->currentUser();
        
        $logbooks = LeadershipLogbook::with(['user.profile', 'coach.profile', 'mentor.profile'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.kepemimpinan.review', compact('user', 'logbooks'));
    }

    public function kepemimpinanUpdateStatus(int $id, Request $request)
    {
        $user = $this->currentUser();
        $logbook = LeadershipLogbook::with(['user'])->findOrFail($id);

        $status = $request->input('status'); // 'approved' or 'revision'
        $feedback = $request->input('feedback');

        if ($user->role === 'widyaiswara' || $user->role === 'super_admin') {
            $logbook->coach_feedback = $feedback;
        }
        if ($user->role === 'mentor' || $user->role === 'super_admin') {
            $logbook->mentor_feedback = $feedback;
        }

        $logbook->status = $status;
        if ($status === 'approved') {
            $logbook->approved_at = Carbon::now();
        }
        $logbook->save();

        // Cek apakah seluruh logbook/milestone peserta ini telah disetujui untuk menerbitkan E-Sertifikat Kepemimpinan
        if ($status === 'approved') {
            $allLogbooks = LeadershipLogbook::where('user_id', $logbook->user_id)->get();
            $allApproved = $allLogbooks->isNotEmpty() && $allLogbooks->every(fn($item) => $item->status === 'approved');

            if ($allApproved) {
                $leadershipEnrollments = CourseEnrollment::with('course')
                    ->where('user_id', $logbook->user_id)
                    ->whereHas('course', function ($q) {
                        $q->where('type', 'kepemimpinan');
                    })
                    ->get();

                foreach ($leadershipEnrollments as $enr) {
                    $enr->update([
                        'status' => 'passed',
                        'final_score' => $enr->final_score ?: 95.0,
                        'completed_at' => Carbon::now(),
                        'progress_percentage' => 100,
                    ]);
                    if ($enr->course) {
                        Certificate::issueForCourse($enr->course, $logbook->user);
                    }
                }
            }
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'REVIEW_LEADERSHIP_LOGBOOK',
            'auditable_type' => 'App\Models\LeadershipLogbook',
            'auditable_id' => $logbook->id,
            'description' => "Penilaian milestone logbook oleh {$user->name} ({$user->role}) untuk peserta {$logbook->user->name} dengan status {$status}.",
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        $msg = $status === 'approved' 
            ? 'Persetujuan milestone aksi perubahan berhasil disimpan.' 
            : 'Catatan rekomendasi revisi milestone berhasil dikirim ke peserta.';
        return back()->with('success', $msg);
    }

    /**
     * Halaman Kelola Visibilitas Menu Sidebar untuk Super Admin
     */
    public function sidebarMenuIndex(Request $request)
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Akses dibatasi hanya untuk Super Admin.');
        }

        // Pastikan master menu tersinkron
        SidebarMenuSetting::syncDefaultMenus();

        $roles = [
            'peserta' => 'Peserta (ASN & Umum)',
            'super_admin' => 'Super Admin BKPSDM',
            'penyelenggara' => 'Penyelenggara Diklat',
            'widyaiswara' => 'Widyaiswara (Coach)',
            'mentor' => 'Mentor (Atasan Langsung)',
            'common' => 'Tautan Publik & Umum',
        ];

        $menusByRole = SidebarMenuSetting::orderBy('order_index')
            ->get()
            ->groupBy('role');

        $activeRole = $request->input('role', 'peserta');
        if (!array_key_exists($activeRole, $roles)) {
            $activeRole = 'peserta';
        }

        return view('admin.sidebar_menu.index', compact('user', 'roles', 'menusByRole', 'activeRole'));
    }

    /**
     * Simpan Perubahan Visibilitas Menu Sidebar
     */
    public function sidebarMenuUpdate(Request $request)
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Akses dibatasi hanya untuk Super Admin.');
        }

        $role = $request->input('role', 'peserta');
        $visibleKeys = $request->input('menu_visibility', []); // array: [menu_key => '1']

        $roleMenus = SidebarMenuSetting::where('role', $role)->get();
        $updatedCount = 0;

        foreach ($roleMenus as $menu) {
            $newVisible = isset($visibleKeys[$menu->menu_key]) && $visibleKeys[$menu->menu_key] == '1';
            if ($menu->is_visible !== $newVisible) {
                $menu->update(['is_visible' => $newVisible]);
                $updatedCount++;
            }
        }

        SidebarMenuSetting::clearCache();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'UPDATE_SIDEBAR_MENU_VISIBILITY',
            'auditable_type' => 'App\Models\SidebarMenuSetting',
            'auditable_id' => null,
            'description' => "Super Admin {$user->name} memperbarui visibilitas menu sidebar untuk role '{$role}' ({$updatedCount} menu diubah).",
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.sidebar_menu.index', ['role' => $role])
            ->with('success', "Visibilitas menu sidebar untuk role '{$role}' berhasil diperbarui.");
    }

    /**
     * Kembalikan Seluruh Pengaturan Menu Sidebar ke Default
     */
    public function sidebarMenuReset(Request $request)
    {
        $user = $this->currentUser();
        if ($user->role !== 'super_admin') {
            return redirect()->route('admin.dashboard')->with('error', 'Akses dibatasi hanya untuk Super Admin.');
        }

        SidebarMenuSetting::resetToDefault();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'RESET_SIDEBAR_MENU_VISIBILITY',
            'auditable_type' => 'App\Models\SidebarMenuSetting',
            'auditable_id' => null,
            'description' => "Super Admin {$user->name} mengembalikan seluruh konfigurasi menu sidebar ke setelan default.",
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return redirect()->route('admin.sidebar_menu.index')
            ->with('success', 'Seluruh konfigurasi menu sidebar telah berhasil dikembalikan ke pengaturan awal (default).');
    }

    /**
     * ==========================================
     * FITUR DASHBOARD HASIL KUESIONER & EVALUASI
     * ==========================================
     */

    /**
     * Halaman Utama Dashboard Hasil Kuesioner (Webinar & Pelatihan)
     */
    public function questionnairesIndex(Request $request)
    {
        $user = $this->currentUser();
        $tab = $request->get('tab', 'all'); // all, webinar, course
        $search = $request->get('search');

        // 1. Agregat Metrik Keseluruhan
        $totalWebinarResponses = WebinarQuestionnaireResponse::count();
        $totalCourseResponses = CourseQuestionnaireResponse::count();
        $totalResponses = $totalWebinarResponses + $totalCourseResponses;

        $avgCourseRating = CourseQuestionnaireResponse::avg('rating_materi') 
            ? round((CourseQuestionnaireResponse::avg('rating_materi') + CourseQuestionnaireResponse::avg('rating_sistem') + CourseQuestionnaireResponse::avg('rating_manfaat') + CourseQuestionnaireResponse::avg('rating_instruktur')) / 4, 1)
            : 4.8;

        $totalFeedbackText = CourseQuestionnaireResponse::whereNotNull('feedback_text')->where('feedback_text', '!=', '')->count();

        // 2. Data Webinar dengan Kuesioner
        $webinarsQuery = Webinar::withCount(['attendances', 'questionnaireResponses'])
            ->with(['questions' => function ($q) {
                $q->where('type', 'questionnaire');
            }]);

        if ($search) {
            $webinarsQuery->where('title', 'like', "%{$search}%");
        }

        $webinars = $webinarsQuery->orderBy('event_date', 'desc')->get();

        // 3. Data Pelatihan (MOOC / Bimtek) dengan Kuesioner
        $coursesQuery = Course::withCount(['enrollments', 'questionnaireResponses'])
            ->with(['questionnaireResponses']);

        if ($search) {
            $coursesQuery->where('title', 'like', "%{$search}%");
        }

        $courses = $coursesQuery->orderBy('created_at', 'desc')->get()->map(function ($course) {
            $responses = $course->questionnaireResponses;
            if ($responses->count() > 0) {
                $materi = $responses->avg('rating_materi');
                $sistem = $responses->avg('rating_sistem');
                $manfaat = $responses->avg('rating_manfaat');
                $instruktur = $responses->avg('rating_instruktur');
                $course->avg_score = round(($materi + $sistem + $manfaat + $instruktur) / 4, 1);
            } else {
                $course->avg_score = 0;
            }
            return $course;
        });

        return view('admin.questionnaires.index', compact(
            'user',
            'tab',
            'search',
            'totalResponses',
            'totalWebinarResponses',
            'totalCourseResponses',
            'avgCourseRating',
            'totalFeedbackText',
            'webinars',
            'courses'
        ));
    }

    /**
     * Detail Analisis Hasil Kuesioner Sesi Webinar Tertentu
     */
    public function questionnaireWebinarDetail(int $id)
    {
        $user = $this->currentUser();
        $webinar = Webinar::with([
            'questions' => function ($q) {
                $q->where('type', 'questionnaire')->orderBy('order_number');
            },
            'questionnaireResponses.user.profile'
        ])->withCount('attendances')->findOrFail($id);

        $questions = $webinar->questions;
        $responses = $webinar->questionnaireResponses;

        // Hitung distribusi jawaban per pertanyaan
        $questionStats = [];
        foreach ($questions as $q) {
            $optionsDistribution = [];
            $options = is_array($q->options) ? $q->options : [];
            foreach ($options as $opt) {
                $optionsDistribution[$opt] = 0;
            }

            $totalAnswered = 0;
            $textAnswers = [];

            foreach ($responses as $resp) {
                $ansArray = is_array($resp->answers) ? $resp->answers : [];
                $ansVal = $ansArray[$q->id] ?? ($ansArray[(string)$q->id] ?? null);

                if ($ansVal !== null) {
                    $totalAnswered++;
                    if (is_array($ansVal)) {
                        foreach ($ansVal as $v) {
                            $optionsDistribution[$v] = ($optionsDistribution[$v] ?? 0) + 1;
                        }
                    } elseif (isset($optionsDistribution[$ansVal])) {
                        $optionsDistribution[$ansVal]++;
                    } else {
                        $textAnswers[] = [
                            'user' => $resp->user,
                            'answer' => (string)$ansVal,
                            'time' => $resp->submitted_at ?? $resp->created_at,
                        ];
                    }
                }
            }

            $questionStats[$q->id] = [
                'question' => $q,
                'total_answered' => $totalAnswered,
                'distribution' => $optionsDistribution,
                'text_answers' => $textAnswers,
            ];
        }

        return view('admin.questionnaires.webinar', compact(
            'user',
            'webinar',
            'questions',
            'responses',
            'questionStats'
        ));
    }

    /**
     * Detail Analisis Hasil Kuesioner Kursus/Pelatihan Tertentu
     */
    public function questionnaireCourseDetail(int $id)
    {
        $user = $this->currentUser();
        $course = Course::with([
            'questionnaireResponses.user.profile'
        ])->withCount('enrollments')->findOrFail($id);

        $responses = $course->questionnaireResponses;
        $totalResp = $responses->count();

        $avgMateri = $totalResp > 0 ? round($responses->avg('rating_materi'), 1) : 0;
        $avgSistem = $totalResp > 0 ? round($responses->avg('rating_sistem'), 1) : 0;
        $avgManfaat = $totalResp > 0 ? round($responses->avg('rating_manfaat'), 1) : 0;
        $avgInstruktur = $totalResp > 0 ? round($responses->avg('rating_instruktur'), 1) : 0;
        $overallAvg = $totalResp > 0 ? round(($avgMateri + $avgSistem + $avgManfaat + $avgInstruktur) / 4, 1) : 0;

        // Distribusi Bintang 1 - 5
        $ratingDistribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach ($responses as $resp) {
            $roundedScore = max(1, min(5, (int) round($resp->average_rating)));
            $ratingDistribution[$roundedScore]++;
        }

        $feedbacks = $responses->filter(function ($r) {
            return !empty($r->feedback_text);
        });

        return view('admin.questionnaires.course', compact(
            'user',
            'course',
            'responses',
            'totalResp',
            'avgMateri',
            'avgSistem',
            'avgManfaat',
            'avgInstruktur',
            'overallAvg',
            'ratingDistribution',
            'feedbacks'
        ));
    }

    /**
     * Export Excel Kuesioner Webinar Tertentu
     */
    public function questionnaireWebinarExport(int $id)
    {
        $webinar = Webinar::findOrFail($id);
        return ExcelExportService::exportQuestionnaireWebinar($webinar);
    }

    /**
     * Export Excel Kuesioner Pelatihan Tertentu
     */
    public function questionnaireCourseExport(int $id)
    {
        $course = Course::findOrFail($id);
        return ExcelExportService::exportQuestionnaireCourse($course);
    }

    /**
     * Export Excel Rekap Seluruh Kuesioner
     */
    public function questionnairesExportAll()
    {
        return ExcelExportService::exportAllQuestionnaires();
    }
}

