<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Webinar;
use App\Models\WebinarAttendance;
use App\Models\WebinarExamAttempt;
use App\Models\WebinarQuestionnaireResponse;
use App\Models\WebinarRegistration;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportService
{
    /**
     * Helper stream download CSV dengan UTF-8 BOM untuk kompatibilitas penuh Microsoft Excel.
     */
    public static function streamDownload(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            
            // UTF-8 BOM agar Microsoft Excel otomatis membaca karakter Indonesia & Unicode dengan benar
            fwrite($handle, "\xEF\xBB\xBF");
            
            // Tulis baris Header (delimiter semicolon ';' standar regional Excel Indonesia/Eropa)
            fputcsv($handle, $headers, ';');
            
            // Tulis seluruh baris data
            foreach ($rows as $row) {
                $sanitizedRow = array_map(function ($val) {
                    if ($val === null) {
                        return '';
                    }
                    if (is_bool($val)) {
                        return $val ? 'YA' : 'TIDAK';
                    }
                    $strVal = trim((string) $val);

                    // Khusus NIP (18 digit), NIK (16 digit), atau angka berawalan 0:
                    // Gunakan formula text ="1990..." agar Excel tidak mengubah ke notasi ilmiah 1.99E+17
                    if (preg_match('/^0\d+$|^\d{12,}$/', $strVal)) {
                        return '="' . $strVal . '"';
                    }

                    return $strVal;
                }, $row);
                
                fputcsv($handle, $sanitizedRow, ';');
            }
            
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * 1. Export Rekap Semua Webinar & Metrik Peserta ke Excel
     */
    public static function exportWebinars(): StreamedResponse
    {
        $webinars = Webinar::with([
            'organizer.profile',
            'registrations',
            'attendances',
            'questionnaireResponses',
            'examAttempts',
            'certificates',
        ])->orderBy('event_date', 'desc')->get();

        $filename = 'Rekap_Webinar_BKPSDM_Pasuruan_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'No',
            'Judul Webinar / Bimtek',
            'Kategori / Bidang',
            'Tanggal Pelaksanaan',
            'Waktu Mulai',
            'Waktu Selesai',
            'Bobot Jam Pelajaran (JP)',
            'Penyelenggara / OPD Pengampu',
            'Nomor Surat Sertifikat',
            'Passing Grade Kelulusan',
            'Status Siaran (Live)',
            'Total Pendaftar',
            'Total Presensi Hadir',
            'Total Mengisi Kuesioner',
            'Total Ikut Ujian Post-Test',
            'Total Peserta Lulus',
            'Total E-Sertifikat Terbit',
            'Link Akses Ruang Webinar',
            'Tautan Zoom / YouTube',
        ];

        $rows = [];
        $no = 1;

        foreach ($webinars as $wb) {
            $eventDateStr = $wb->event_date ? $wb->event_date->isoFormat('D MMMM Y') : '-';
            $startTimeStr = $wb->start_time ? substr($wb->start_time, 0, 5) . ' WIB' : '-';
            $endTimeStr = $wb->end_time ? substr($wb->end_time, 0, 5) . ' WIB' : '-';

            $totalReg = $wb->registrations->count();
            $totalAtt = $wb->attendances->count();
            $totalQues = $wb->questionnaireResponses->groupBy('user_id')->count();
            $totalExam = $wb->examAttempts->groupBy('user_id')->count();
            $totalPassed = $wb->examAttempts->where('is_passed', true)->groupBy('user_id')->count();
            $totalCert = $wb->certificates->count();

            $organizerName = $wb->organizer ? $wb->organizer->name : 'BKPSDM Kabupaten Pasuruan';
            $liveStatus = $wb->is_live ? 'LIVE (SEDANG AKTIF)' : 'SELESAI / OFFLINE';

            $rows[] = [
                $no++,
                $wb->title,
                $wb->category ?: 'Pengembangan Kompetensi ASN',
                $eventDateStr,
                $startTimeStr,
                $endTimeStr,
                $wb->jp . ' JP (' . ($wb->jp * 45) . ' Menit)',
                $organizerName,
                $wb->certificate_number ?: 'Otomatis (Unik)',
                $wb->passing_grade,
                $liveStatus,
                $totalReg,
                $totalAtt,
                $totalQues,
                $totalExam,
                $totalPassed,
                $totalCert,
                route('peserta.webinar.room', $wb->slug),
                $wb->meeting_link ?: $wb->youtube_link ?: '-',
            ];
        }

        return self::streamDownload($filename, $headers, $rows);
    }

    /**
     * 2. Export Rekap Lengkap Peserta per Sesi Webinar ke Excel
     */
    public static function exportWebinarParticipants(Webinar $webinar): StreamedResponse
    {
        $webinar->load([
            'registrations.user.profile',
            'attendances.user.profile',
            'questionnaireResponses',
            'examAttempts.user',
            'certificates',
        ]);

        $filename = 'Peserta_Webinar_' . \Illuminate\Support\Str::slug($webinar->title) . '_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'No',
            'NIP (ASN)',
            'NIK',
            'Nama Lengkap Peserta',
            'Jabatan',
            'Instansi / Perangkat Daerah (Unit Kerja)',
            'Kategori Kepesertaan',
            'Alamat Email',
            'Nomor WhatsApp / HP',
            'Waktu Pendaftaran',
            'Status Kehadiran',
            'Waktu Presensi Hadir',
            'Kuesioner Kepuasan',
            'Nilai Post-Test (Evaluasi)',
            'Status Kelulusan',
            'Nomor E-Sertifikat Resmi',
            'Tanggal Terbit Sertifikat',
            'Tautan Validasi Sertifikat Online',
        ];

        // Petakan semua pendaftar dan peserta yang hadir
        $attendancesByUserId = $webinar->attendances->keyBy('user_id');
        $questionnairesByUserId = $webinar->questionnaireResponses->groupBy('user_id');
        $examAttemptsByUserId = $webinar->examAttempts->groupBy('user_id');
        $certificatesByUserId = $webinar->certificates->keyBy('user_id');

        // Kumpulkan semua user_id yang terdaftar atau hadir
        $userIds = $webinar->registrations->pluck('user_id')
            ->merge($webinar->attendances->pluck('user_id'))
            ->unique();

        $users = \App\Models\User::with('profile')->whereIn('id', $userIds)->get()->keyBy('id');
        $registrationsByUserId = $webinar->registrations->keyBy('user_id');

        $rows = [];
        $no = 1;

        foreach ($userIds as $uid) {
            $user = $users->get($uid);
            if (!$user) continue;

            $reg = $registrationsByUserId->get($uid);
            $att = $attendancesByUserId->get($uid);
            $ques = $questionnairesByUserId->get($uid);
            $attempts = $examAttemptsByUserId->get($uid);
            $cert = $certificatesByUserId->get($uid);

            $nip = $reg?->nip ?: $att?->nip ?: $cert?->nip ?: $user->nip ?: '-';
            $nik = $reg?->nik ?: $att?->nik ?: $cert?->nik ?: $user->profile?->nik ?: $user->nik ?: '-';
            $nama = $reg?->nama_lengkap ?: $att?->nama_lengkap ?: $cert?->nama_lengkap ?: $user->profile?->formatted_full_name ?: $user->name;
            $jabatan = $reg?->jabatan ?: $att?->jabatan ?: $cert?->jabatan ?: $user->profile?->jabatan ?: $user->jabatan ?: '-';
            $unitKerja = $reg?->unit_kerja ?: $att?->unit_kerja ?: $cert?->unit_kerja ?: $user->profile?->unit_kerja ?: $user->unit_kerja ?: '-';

            $kategori = $user->isPesertaAsn() ? 'ASN Pemkab Pasuruan' : ($user->nip ? 'ASN Luar Pasuruan' : 'Peserta Umum');
            $regTime = $reg?->registered_at ? $reg->registered_at->format('d/m/Y H:i') : ($reg?->created_at ? $reg->created_at->format('d/m/Y H:i') : '-');

            $statusHadir = $att ? 'HADIR' : 'BELUM HADIR';
            $waktuHadir = $att?->attended_at ? $att->attended_at->format('d/m/Y H:i:s') : ($att?->created_at ? $att->created_at->format('d/m/Y H:i:s') : '-');
            $statusKuesioner = ($ques && $ques->count() > 0) ? 'SUDAH MENGISI' : 'BELUM';

            // Ambil attempt nilai terbaik
            $bestAttempt = $attempts ? $attempts->sortByDesc('score')->first() : null;
            $nilaiPostTest = $bestAttempt ? $bestAttempt->score : '-';

            $statusLulus = 'BELUM MENGERJAKAN';
            if ($bestAttempt) {
                $statusLulus = $bestAttempt->is_passed ? 'LULUS' : 'TIDAK LULUS';
            } elseif ($att && !$webinar->questions()->where('type', 'quiz')->exists()) {
                $statusLulus = 'LULUS (HADIR)';
            }

            $certNum = $cert ? $cert->certificate_number : '-';
            $certDate = $cert?->issued_at ? $cert->issued_at->format('d/m/Y H:i') : '-';
            $certUrl = $cert ? route('validation.show', $cert->hash_token) : '-';

            $rows[] = [
                $no++,
                $nip,
                $nik,
                $nama,
                $jabatan,
                $unitKerja,
                $kategori,
                $user->email,
                $user->profile?->phone_number ?: '-',
                $regTime,
                $statusHadir,
                $waktuHadir,
                $statusKuesioner,
                $nilaiPostTest,
                $statusLulus,
                $certNum,
                $certDate,
                $certUrl,
            ];
        }

        return self::streamDownload($filename, $headers, $rows);
    }

    /**
     * 3. Export Rekap Semua Pelatihan MOOC & Modul Kedinasan ke Excel
     */
    public static function exportCourses(): StreamedResponse
    {
        $courses = Course::with([
            'lessons',
            'enrollments',
            'certificates',
        ])->orderBy('created_at', 'desc')->get();

        $filename = 'Rekap_Pelatihan_MOOC_BKPSDM_Pasuruan_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'No',
            'Judul Program Pelatihan',
            'Tipe Program Diklat',
            'Kategori / Rumpun',
            'Bobot Jam Pelajaran (JP)',
            'Passing Grade Kelulusan',
            'Skema Jangka Waktu',
            'Batas Waktu Pengerjaan',
            'Tanggal Pembukaan',
            'Batas Akhir Pelatihan',
            'Nomor Surat Sertifikat',
            'Total Sesi Materi Modul',
            'Total Peserta ASN Terdaftar',
            'Peserta Dalam Proses (In Progress)',
            'Peserta Selesai / Lulus',
            'Peserta Tidak Lulus (Kadaluarsa)',
            'Total E-Sertifikat Terbit',
            'Link Akses Ruang Pelatihan',
        ];

        $rows = [];
        $no = 1;

        foreach ($courses as $c) {
            $typeStr = strtoupper(str_replace('_', ' ', $c->type));
            $timeLimitStr = $c->getTimeLimitDescription();
            $startDateStr = $c->start_date ? $c->start_date->format('d/m/Y H:i') : '-';
            $endDateStr = $c->end_date ? $c->end_date->format('d/m/Y H:i') : '-';

            $totalEnr = $c->enrollments->count();
            $inProgress = $c->enrollments->where('status', 'in_progress')->count();
            $passed = $c->enrollments->where('status', 'passed')->count();
            $failed = $c->enrollments->where('status', 'failed')->count();
            $totalCert = $c->certificates->count();

            $rows[] = [
                $no++,
                $c->title,
                $typeStr,
                $c->category,
                $c->jp . ' JP (' . ($c->jp * 45) . ' Menit)',
                $c->passing_grade,
                $timeLimitStr,
                $c->duration_days ? $c->duration_days . ' Hari Kalender' : '-',
                $startDateStr,
                $endDateStr,
                $c->certificate_number ?: 'Otomatis (Unik)',
                $c->lessons->count(),
                $totalEnr,
                $inProgress,
                $passed,
                $failed,
                $totalCert,
                route('peserta.mooc.show', $c->slug),
            ];
        }

        return self::streamDownload($filename, $headers, $rows);
    }

    /**
     * 4. Export Rekap Lengkap Hasil Belajar Peserta per Pelatihan ke Excel
     */
    public static function exportCourseParticipants(Course $course): StreamedResponse
    {
        $course->load([
            'enrollments.user.profile',
            'certificates',
            'lessons',
        ]);

        $filename = 'Hasil_Belajar_Pelatihan_' . \Illuminate\Support\Str::slug($course->title) . '_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'No',
            'NIP (ASN)',
            'NIK',
            'Nama Lengkap Peserta ASN',
            'Jabatan',
            'Instansi / Unit Kerja',
            'Waktu Mulai Belajar (Enrolled At)',
            'Batas Waktu Pengerjaan (Deadline)',
            'Sisa Waktu Pengerjaan',
            'Progres Belajar (%)',
            'Laporan / Portofolio Tugas Akhir',
            'Nilai Akhir Evaluasi',
            'Catatan Pengampu / Widyaiswara',
            'Status Kelulusan',
            'Nomor E-Sertifikat Resmi',
            'Tanggal Terbit Sertifikat',
            'Tautan Validasi Sertifikat Online',
        ];

        $certificatesByUserId = $course->certificates->keyBy('user_id');

        $rows = [];
        $no = 1;

        foreach ($course->enrollments as $enr) {
            $user = $enr->user;
            if (!$user) continue;

            $cert = $certificatesByUserId->get($enr->user_id);

            $nip = $enr->nip ?: $cert?->nip ?: $user->nip ?: '-';
            $nik = $enr->nik ?: $cert?->nik ?: $user->profile?->nik ?: $user->nik ?: '-';
            $nama = $enr->nama_lengkap ?: $cert?->nama_lengkap ?: $user->profile?->formatted_full_name ?: $user->name;
            $jabatan = $enr->jabatan ?: $cert?->jabatan ?: $user->profile?->jabatan ?: $user->jabatan ?: '-';
            $unitKerja = $enr->unit_kerja ?: $cert?->unit_kerja ?: $user->profile?->unit_kerja ?: $user->unit_kerja ?: '-';

            $enrolledTimeStr = $enr->enrolled_at ? $enr->enrolled_at->format('d/m/Y H:i') : ($enr->created_at ? $enr->created_at->format('d/m/Y H:i') : '-');
            $deadlineTimeStr = $enr->deadline_at ? $enr->deadline_at->format('d/m/Y H:i') : '-';
            $sisaWaktuStr = $enr->remainingTimeDescription();

            $statusTugas = $enr->assignment_file ? 'SUDAH UNGGAH (' . ($enr->assignment_submitted_at ? $enr->assignment_submitted_at->format('d/m/Y') : '') . ')' : 'BELUM UNGGAH';
            $nilaiAkhir = $enr->final_score !== null ? $enr->final_score : '-';
            $catatanPengampu = $enr->instructor_feedback ?: '-';

            $statusLulus = match ($enr->status) {
                'passed' => 'LULUS (MEMENUHI SYARAT)',
                'in_progress' => 'SEDANG BELAJAR (IN PROGRESS)',
                'revision' => 'MEMERLUKAN REVISI LAPORAN',
                'failed' => 'TIDAK LULUS (KADALUARSA)',
                default => strtoupper($enr->status),
            };

            $certNum = $cert ? $cert->certificate_number : '-';
            $certDate = $cert?->issued_at ? $cert->issued_at->format('d/m/Y H:i') : '-';
            $certUrl = $cert ? route('validation.show', $cert->hash_token) : '-';

            $rows[] = [
                $no++,
                $nip,
                $nik,
                $nama,
                $jabatan,
                $unitKerja,
                $enrolledTimeStr,
                $deadlineTimeStr,
                $sisaWaktuStr,
                $enr->progress_percentage . '%',
                $statusTugas,
                $nilaiAkhir,
                $catatanPengampu,
                $statusLulus,
                $certNum,
                $certDate,
                $certUrl,
            ];
        }

        return self::streamDownload($filename, $headers, $rows);
    }

    /**
     * 5. Export Rekap Seluruh E-Sertifikat Resmi Terbit ke Excel
     */
    public static function exportCertificates(): StreamedResponse
    {
        $certificates = Certificate::with([
            'user.profile',
            'webinar',
            'course',
        ])->orderBy('issued_at', 'desc')->get();

        $filename = 'Rekap_Semua_E-Sertifikat_BKPSDM_Pasuruan_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'No',
            'Nomor Sertifikat Resmi',
            'Jenis Program Pengembangan Kompetensi',
            'Judul Kegiatan / Pelatihan Kedinasan',
            'Bobot Jam Pelajaran (JP)',
            'NIP Penerima (ASN)',
            'NIK Penerima',
            'Nama Lengkap Penerima Sertifikat',
            'Jabatan',
            'Instansi / Perangkat Daerah (Unit Kerja)',
            'Tanggal & Waktu Pengesahan (Terbit)',
            'Kode Token Verifikasi Keaslian',
            'Tautan Validasi Sertifikat Online Publik',
        ];

        $rows = [];
        $no = 1;

        foreach ($certificates as $cert) {
            $user = $cert->user;

            $jenisProgram = 'Diklat Kedinasan';
            if ($cert->webinar_id) {
                $jenisProgram = 'Webinar / Bimtek Interaktif';
            } elseif ($cert->course_id) {
                $jenisProgram = 'Pelatihan Mandiri (MOOC)';
            }

            $judulKegiatan = $cert->training_title ?: ($cert->webinar?->title ?? $cert->course?->title ?? '-');
            $nip = $cert->nip ?: $user?->nip ?: '-';
            $nik = $cert->nik ?: $user?->profile?->nik ?: $user?->nik ?: '-';
            $nama = $cert->nama_lengkap ?: $user?->profile?->formatted_full_name ?: $user?->name ?: '-';
            $jabatan = $cert->jabatan ?: $user?->profile?->jabatan ?: $user?->jabatan ?: '-';
            $unitKerja = $cert->unit_kerja ?: $user?->profile?->unit_kerja ?: $user?->unit_kerja ?: '-';
            $issuedDateStr = $cert->issued_at ? $cert->issued_at->format('d/m/Y H:i:s') : '-';
            $validationUrl = route('validation.show', $cert->hash_token);

            $rows[] = [
                $no++,
                $cert->certificate_number,
                $jenisProgram,
                $judulKegiatan,
                $cert->jp . ' JP',
                $nip,
                $nik,
                $nama,
                $jabatan,
                $unitKerja,
                $issuedDateStr,
                $cert->hash_token,
                $validationUrl,
            ];
        }

        return self::streamDownload($filename, $headers, $rows);
    }

    /**
     * 6. Export Rekap Kuesioner Sesi Webinar Spesifik ke Excel
     */
    public static function exportQuestionnaireWebinar(Webinar $webinar): StreamedResponse
    {
        $webinar->load([
            'questions' => function ($q) {
                $q->where('type', 'questionnaire')->orderBy('order_number');
            },
            'questionnaireResponses.user.profile',
        ]);

        $questions = $webinar->questions;
        $responses = $webinar->questionnaireResponses;

        $filename = 'Hasil_Kuesioner_Webinar_' . \Illuminate\Support\Str::slug($webinar->title) . '_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'No',
            'NIP (ASN)',
            'NIK',
            'Nama Lengkap Responden ASN',
            'Jabatan',
            'Instansi / Unit Kerja',
            'Waktu Submit Kuesioner',
        ];

        foreach ($questions as $idx => $q) {
            $headers[] = 'Pertanyaan ' . ($idx + 1) . ': ' . strip_tags($q->question_text);
        }

        $rows = [];
        $no = 1;

        foreach ($responses as $resp) {
            $user = $resp->user;
            $nip = $resp->nip ?: $user?->nip ?: '-';
            $nik = $resp->nik ?: $user?->profile?->nik ?: $user?->nik ?: '-';
            $nama = $resp->nama_lengkap ?: $user?->profile?->formatted_full_name ?: $user?->name ?: '-';
            $jabatan = $resp->jabatan ?: $user?->profile?->jabatan ?: $user?->jabatan ?: '-';
            $unitKerja = $resp->unit_kerja ?: $user?->profile?->unit_kerja ?: $user?->unit_kerja ?: '-';
            $submitTimeStr = $resp->submitted_at ? $resp->submitted_at->format('d/m/Y H:i:s') : ($resp->created_at ? $resp->created_at->format('d/m/Y H:i:s') : '-');

            $row = [
                $no++,
                $nip,
                $nik,
                $nama,
                $jabatan,
                $unitKerja,
                $submitTimeStr,
            ];

            $ansArray = is_array($resp->answers) ? $resp->answers : [];
            foreach ($questions as $q) {
                $ansVal = $ansArray[$q->id] ?? ($ansArray[(string)$q->id] ?? '-');
                $row[] = is_array($ansVal) ? implode(', ', $ansVal) : (string)$ansVal;
            }

            $rows[] = $row;
        }

        return self::streamDownload($filename, $headers, $rows);
    }

    /**
     * 7. Export Rekap Kuesioner Pelatihan MOOC / Diklat Spesifik ke Excel
     */
    public static function exportQuestionnaireCourse(Course $course): StreamedResponse
    {
        $course->load(['questionnaireResponses.user.profile']);
        $responses = $course->questionnaireResponses;

        $filename = 'Hasil_Kuesioner_Pelatihan_' . \Illuminate\Support\Str::slug($course->title) . '_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'No',
            'NIP (ASN)',
            'NIK',
            'Nama Lengkap Responden ASN',
            'Jabatan',
            'Instansi / Unit Kerja',
            'Waktu Pengisian',
            'Rating Kesesuaian Materi (1-5)',
            'Rating Kemudahan Platform LMS (1-5)',
            'Rating Manfaat terhadap Tugas ASN (1-5)',
            'Rating Kualitas Modul & Penyajian (1-5)',
            'Rata-rata Skor Kepuasan (1-5)',
            'Kritik, Saran & Masukan Evaluasi',
        ];

        $rows = [];
        $no = 1;

        foreach ($responses as $resp) {
            $user = $resp->user;
            $nip = $resp->nip ?: $user?->nip ?: '-';
            $nik = $resp->nik ?: $user?->profile?->nik ?: $user?->nik ?: '-';
            $nama = $resp->nama_lengkap ?: $user?->profile?->formatted_full_name ?: $user?->name ?: '-';
            $jabatan = $resp->jabatan ?: $user?->profile?->jabatan ?: $user?->jabatan ?: '-';
            $unitKerja = $resp->unit_kerja ?: $user?->profile?->unit_kerja ?: $user?->unit_kerja ?: '-';
            $submitTimeStr = $resp->submitted_at ? $resp->submitted_at->format('d/m/Y H:i:s') : ($resp->created_at ? $resp->created_at->format('d/m/Y H:i:s') : '-');

            $rows[] = [
                $no++,
                $nip,
                $nik,
                $nama,
                $jabatan,
                $unitKerja,
                $submitTimeStr,
                $resp->rating_materi . ' / 5',
                $resp->rating_sistem . ' / 5',
                $resp->rating_manfaat . ' / 5',
                $resp->rating_instruktur . ' / 5',
                $resp->average_rating . ' / 5',
                $resp->feedback_text ?: '-',
            ];
        }

        return self::streamDownload($filename, $headers, $rows);
    }

    /**
     * 8. Export Rekap Seluruh Kuesioner (Webinar & Pelatihan) ke Excel
     */
    public static function exportAllQuestionnaires(): StreamedResponse
    {
        $webinarResponses = WebinarQuestionnaireResponse::with(['webinar', 'user.profile'])->orderBy('submitted_at', 'desc')->get();
        $courseResponses = CourseQuestionnaireResponse::with(['course', 'user.profile'])->orderBy('submitted_at', 'desc')->get();

        $filename = 'Rekap_Semua_Kuesioner_LMS_Pasuruan_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'No',
            'Tipe Kegiatan',
            'Judul Kegiatan / Program Diklat',
            'Kategori / Rumpun',
            'NIP (ASN)',
            'NIK',
            'Nama Lengkap Responden',
            'Jabatan',
            'Instansi / Unit Kerja',
            'Waktu Submit',
            'Ringkasan Penilaian / Jawaban',
            'Kritik / Saran / Masukan Tertulis',
        ];

        $rows = [];
        $no = 1;

        // 1. Masukkan data Webinar
        foreach ($webinarResponses as $wResp) {
            $user = $wResp->user;
            $webinar = $wResp->webinar;

            $nip = $wResp->nip ?: $user?->nip ?: '-';
            $nik = $wResp->nik ?: $user?->profile?->nik ?: $user?->nik ?: '-';
            $nama = $wResp->nama_lengkap ?: $user?->profile?->formatted_full_name ?: $user?->name ?: '-';
            $jabatan = $wResp->jabatan ?: $user?->profile?->jabatan ?: $user?->jabatan ?: '-';
            $unitKerja = $wResp->unit_kerja ?: $user?->profile?->unit_kerja ?: $user?->unit_kerja ?: '-';
            $submitTimeStr = $wResp->submitted_at ? $wResp->submitted_at->format('d/m/Y H:i') : ($wResp->created_at ? $wResp->created_at->format('d/m/Y H:i') : '-');

            $ansArray = is_array($wResp->answers) ? $wResp->answers : [];
            $summaryAnswers = [];
            $textFeedback = '-';

            foreach ($ansArray as $k => $v) {
                if (is_string($v) && strlen($v) > 25) {
                    $textFeedback = $v;
                } else {
                    $summaryAnswers[] = is_array($v) ? implode(', ', $v) : (string)$v;
                }
            }

            $rows[] = [
                $no++,
                'Webinar / Bimtek',
                $webinar?->title ?? 'Webinar Selesai',
                $webinar?->category ?? 'Pengembangan Kompetensi',
                $nip,
                $nik,
                $nama,
                $jabatan,
                $unitKerja,
                $submitTimeStr,
                implode(' | ', $summaryAnswers) ?: 'Respon Tercatat',
                $textFeedback,
            ];
        }

        // 2. Masukkan data Pelatihan
        foreach ($courseResponses as $cResp) {
            $user = $cResp->user;
            $course = $cResp->course;

            $nip = $cResp->nip ?: $user?->nip ?: '-';
            $nik = $cResp->nik ?: $user?->profile?->nik ?: $user?->nik ?: '-';
            $nama = $cResp->nama_lengkap ?: $user?->profile?->formatted_full_name ?: $user?->name ?: '-';
            $jabatan = $cResp->jabatan ?: $user?->profile?->jabatan ?: $user?->jabatan ?: '-';
            $unitKerja = $cResp->unit_kerja ?: $user?->profile?->unit_kerja ?: $user?->unit_kerja ?: '-';
            $submitTimeStr = $cResp->submitted_at ? $cResp->submitted_at->format('d/m/Y H:i') : ($cResp->created_at ? $cResp->created_at->format('d/m/Y H:i') : '-');

            $rows[] = [
                $no++,
                'Pelatihan (MOOC)',
                $course?->title ?? 'Kursus Diklat',
                $course?->category ?? 'Kompetensi ASN',
                $nip,
                $nik,
                $nama,
                $jabatan,
                $unitKerja,
                $submitTimeStr,
                "Skor Rata-rata: {$cResp->average_rating}/5 (Materi: {$cResp->rating_materi}, Sistem: {$cResp->rating_sistem}, Manfaat: {$cResp->rating_manfaat}, Modul: {$cResp->rating_instruktur})",
                $cResp->feedback_text ?: '-',
            ];
        }

        return self::streamDownload($filename, $headers, $rows);
    }
}
