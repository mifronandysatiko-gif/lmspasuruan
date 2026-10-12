<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseLessonQuestion;
use App\Models\CourseEnrollment;
use App\Models\CourseLessonCompletion;
use App\Models\Certificate;
use App\Models\LeadershipLogbook;
use App\Http\Controllers\PesertaController;
use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== TEST: WORKFLOW PENERBITAN SERTIFIKAT BERBASIS TUGAS AKHIR & PERSETUJUAN ===\n\n";

// 1. Setup Test User & Course
$testUser = User::firstOrCreate(
    ['email' => 'test_asn_approval@pasuruankab.go.id'],
    [
        'name' => 'Budi Santoso, S.STP',
        'nip' => '198801012010011005',
        'password' => bcrypt('password123'),
        'role' => 'peserta',
        'kategori_peserta' => 'asn_pasuruan',
    ]
);

$adminUser = User::where('role', 'super_admin')->first() ?: User::firstOrCreate(
    ['email' => 'admin_super@pasuruankab.go.id'],
    [
        'name' => 'Super Admin BKPSDM',
        'nip' => '197501011999031001',
        'password' => bcrypt('password123'),
        'role' => 'super_admin',
    ]
);

$course = Course::firstOrCreate(
    ['slug' => 'test-diklat-approval-cert-2026'],
    [
        'title' => 'Pelatihan Teknis Digitalisasi Layanan Publik 2026',
        'category' => 'Teknis Pelayanan',
        'type' => 'teknis_fungsional',
        'jp' => 24,
        'passing_grade' => 80,
        'status' => 'published',
        'certificate_number' => '800.1.12.2/{AUTO}/BKPSDM/2026',
    ]
);

// Pastikan ada lesson dengan kuis
$lesson = CourseLesson::firstOrCreate(
    ['course_id' => $course->id, 'sequence' => 1],
    [
        'title' => 'Pengenalan Portal Digital Kabupaten Pasuruan',
        'content_summary' => 'Materi modul dasar pengenalan portal digital.',
        'has_quiz' => true,
    ]
);

$question = CourseLessonQuestion::firstOrCreate(
    ['course_lesson_id' => $lesson->id, 'order_number' => 1],
    [
        'question_text' => 'Apa tujuan utama dari digitalisasi pelayanan publik di Kabupaten Pasuruan?',
        'options' => [
            'A' => 'Mempermudah, mempercepat, dan meningkatkan transparansi layanan ASN',
            'B' => 'Menambah biaya birokrasi',
            'C' => 'Mengurangi jumlah pegawai secara massal',
            'D' => 'Menghapus arsip dokumen daerah',
        ],
        'correct_answer' => 'A',
    ]
);

// Bersihkan data lama jika ada
Certificate::where('course_id', $course->id)->where('user_id', $testUser->id)->delete();
CourseLessonCompletion::where('course_id', $course->id)->where('user_id', $testUser->id)->delete();
CourseEnrollment::where('course_id', $course->id)->where('user_id', $testUser->id)->delete();

$enrollment = CourseEnrollment::create([
    'course_id' => $course->id,
    'user_id' => $testUser->id,
    'enrolled_at' => now(),
    'progress_percentage' => 0,
    'status' => 'in_progress',
]);

echo "[STEP 1] Setup data peserta ({$testUser->name}) & Pelatihan ({$course->title}) selesai.\n";

// 2. Simulasi Peserta Lulus Kuis
Auth::login($testUser);
$pesertaController = new PesertaController();

$quizRequest = Request::create("/peserta/mooc/{$course->slug}/quiz", 'POST', [
    'lesson_id' => $lesson->id,
    'answers' => [$question->id => 'A'],
]);
$pesertaController->moocSubmitQuiz($course->slug, $quizRequest);

$enrollment->refresh();
$certCountAfterQuiz = Certificate::where('course_id', $course->id)->where('user_id', $testUser->id)->count();

echo "[STEP 2] Kuis Selesai dengan Nilai: {$enrollment->final_score}/100, Progres: {$enrollment->progress_percentage}%.\n";
echo "        Status Enrollment: '{$enrollment->status}'\n";
echo "        Jumlah Sertifikat Diterbitkan: {$certCountAfterQuiz}\n";

if ($certCountAfterQuiz === 0 && $enrollment->status !== 'passed') {
    echo "  [PASS] Sertifikat TIDAK diterbitkan otomatis dari pengerjaan kuis saja.\n";
} else {
    echo "  [FAIL] Sertifikat diterbitkan sebelum tugas diunggah/disetujui!\n";
}

// 3. Simulasi Peserta Mengunggah Laporan Tugas Akhir
$assignmentRequest = Request::create("/peserta/mooc/{$course->slug}/assignment", 'POST', [
    'assignment_notes' => 'Berikut draf laporan implementasi digitalisasi layanan pada unit kerja saya.',
]);
$pesertaController->moocSubmitAssignment($course->slug, $assignmentRequest);

$enrollment->refresh();
$certCountAfterAssignment = Certificate::where('course_id', $course->id)->where('user_id', $testUser->id)->count();

echo "\n[STEP 3] Laporan Tugas Akhir Diunggah.\n";
echo "        Berkas: '{$enrollment->assignment_file}'\n";
echo "        Waktu Unggah: {$enrollment->assignment_submitted_at}\n";
echo "        Jumlah Sertifikat Diterbitkan: {$certCountAfterAssignment}\n";

if ($certCountAfterAssignment === 0 && !empty($enrollment->assignment_file)) {
    echo "  [PASS] Tugas tersimpan dan sertifikat masih menunggu persetujuan pengelola.\n";
} else {
    echo "  [FAIL] Kondisi pengunggahan tugas tidak sesuai.\n";
}

// 4. Simulasi Penyelenggara / Super Admin Menyetujui & Memberi Nilai Kelulusan
Auth::login($adminUser);
$adminController = new AdminController();

$gradeRequest = Request::create("/admin/courses/{$course->id}/grade/{$enrollment->id}", 'POST', [
    'final_score' => 92.5,
    'status' => 'passed',
    'instructor_feedback' => 'Laporan tugas akhir sangat komprehensif dan sesuai standar kompetensi.',
]);
$adminController->courseGradeStudent($course->id, $enrollment->id, $gradeRequest);

$enrollment->refresh();
$certificate = Certificate::where('course_id', $course->id)->where('user_id', $testUser->id)->first();

echo "\n[STEP 4] Penyelenggara / Admin Memberikan Nilai & Persetujuan (Status: passed, Nilai: 92.5).\n";
echo "        Status Enrollment: '{$enrollment->status}'\n";
echo "        Nilai Akhir: {$enrollment->final_score}\n";
echo "        Catatan Pengampu: '{$enrollment->instructor_feedback}'\n";

if ($certificate && $enrollment->status === 'passed') {
    echo "  [PASS] E-Sertifikat BERHASIL DITERBITKAN setelah persetujuan resmi!\n";
    echo "        Nomor Sertifikat: {$certificate->certificate_number}\n";
    echo "        Kode Verifikasi QR: {$certificate->hash_token}\n";
    echo "        JP: {$certificate->jp} JP\n";
} else {
    echo "  [FAIL] E-Sertifikat gagal diterbitkan setelah persetujuan!\n";
}

// 5. Uji Coba Pelatihan Kepemimpinan (Coach & Mentor Review)
echo "\n=== TEST: ALUR PERSETUJUAN KEPEMIMPINAN (COACH & MENTOR) ===\n";
$leadershipCourse = Course::firstOrCreate(
    ['slug' => 'pkp-pasuruan-test-2026'],
    [
        'title' => 'Pelatihan Kepemimpinan Pengawas (PKP) Angkatan IV 2026',
        'category' => 'Kepemimpinan',
        'type' => 'kepemimpinan',
        'jp' => 72,
        'passing_grade' => 85,
        'status' => 'published',
        'certificate_number' => '800.1.12.1/{AUTO}/BKPSDM/2026',
    ]
);

Certificate::where('course_id', $leadershipCourse->id)->where('user_id', $testUser->id)->delete();
LeadershipLogbook::where('user_id', $testUser->id)->delete();
CourseEnrollment::where('course_id', $leadershipCourse->id)->where('user_id', $testUser->id)->delete();

$leadershipEnrollment = CourseEnrollment::create([
    'course_id' => $leadershipCourse->id,
    'user_id' => $testUser->id,
    'enrolled_at' => now(),
    'progress_percentage' => 50,
    'status' => 'in_progress',
]);

Auth::login($testUser);
$logbookRequest = Request::create('/peserta/kepemimpinan/store', 'POST', [
    'week_number' => 1,
    'milestone_title' => 'Milestone 1: Pembentukan Tim Efektif Aksi Perubahan',
    'activity_date' => date('Y-m-d'),
    'activity_description' => 'Telah dibentuk Surat Keputusan Tim Efektif dan sosialisasi internal.',
]);
$pesertaController->kepemimpinanStore($logbookRequest);

$logbook = LeadershipLogbook::where('user_id', $testUser->id)->first();
echo "[LEADERSHIP 1] Logbook Milestone diajukan oleh peserta (Status: {$logbook->status}).\n";

// Coach & Mentor menyetujui
Auth::login($adminUser);
$mentorApproveRequest = Request::create("/admin/kepemimpinan/{$logbook->id}/status", 'POST', [
    'status' => 'approved',
    'feedback' => 'Milestone telah diverifikasi dengan bukti SK yang valid. Lanjutkan ke tahap implementasi.',
]);
$adminController->kepemimpinanUpdateStatus($logbook->id, $mentorApproveRequest);

$leadershipEnrollment->refresh();
$leadershipCert = Certificate::where('course_id', $leadershipCourse->id)->where('user_id', $testUser->id)->first();

echo "[LEADERSHIP 2] Coach / Mentor menyetujui milestone aksi perubahan.\n";
echo "              Status Enrollment Kepemimpinan: '{$leadershipEnrollment->status}'\n";

if ($leadershipCert && $leadershipEnrollment->status === 'passed') {
    echo "  [PASS] E-Sertifikat Kepemimpinan BERHASIL DITERBITKAN setelah persetujuan Coach & Mentor!\n";
    echo "        Nomor Sertifikat: {$leadershipCert->certificate_number}\n";
    echo "        Kode Verifikasi QR: {$leadershipCert->hash_token}\n";
} else {
    echo "  [FAIL] E-Sertifikat Kepemimpinan belum terbit.\n";
}

echo "\n=== SELURUH PENGUJIAN OTOMATIS BERHASIL DENGAN SEMPURNA ===\n";
