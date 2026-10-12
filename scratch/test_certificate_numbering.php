<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\Webinar;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;

echo "=== TESTING CERTIFICATE NUMBERING SYSTEM (FIXED VS AUTO) ===\n\n";

DB::beginTransaction();

try {
    $user1 = User::firstOrCreate(['email' => 'peserta.test1@pasuruankab.go.id'], [
        'name' => 'Peserta Uji Satu',
        'password' => bcrypt('password'),
        'role' => 'peserta',
    ]);
    $user2 = User::firstOrCreate(['email' => 'peserta.test2@pasuruankab.go.id'], [
        'name' => 'Peserta Uji Dua',
        'password' => bcrypt('password'),
        'role' => 'peserta',
    ]);

    // ----------------------------------------------------
    // TEST 1: Pelatihan Kursus dengan Nomor Surat Tetap
    // ----------------------------------------------------
    echo "[TEST 1] Pelatihan Kursus dengan Nomor Surat Tetap...\n";
    $fixedCourse = Course::create([
        'title' => 'Bimtek Transformasi Digital SPBE 2026',
        'slug' => 'bimtek-spbe-fixed-' . time(),
        'type' => 'teknis',
        'category' => 'Teknologi Informasi',
        'description' => 'Uji Bimtek dengan nomor surat tetap.',
        'jp' => 12,
        'passing_grade' => 75,
        'status' => 'published',
        'certificate_number' => '893.3/045/BKPSDM-PAS/2026',
    ]);

    $cert1 = Certificate::issueForCourse($fixedCourse, $user1);
    $cert2 = Certificate::issueForCourse($fixedCourse, $user2);

    if ($cert1->certificate_number !== '893.3/045/BKPSDM-PAS/2026') {
        throw new Exception("Nomor sertifikat peserta 1 tidak sesuai: {$cert1->certificate_number}");
    }
    if ($cert2->certificate_number !== '893.3/045/BKPSDM-PAS/2026') {
        throw new Exception("Nomor sertifikat peserta 2 tidak sesuai: {$cert2->certificate_number}");
    }
    if ($cert1->certificate_number !== $cert2->certificate_number) {
        throw new Exception("Nomor sertifikat peserta 1 dan 2 harus sama persis!");
    }
    if ($cert1->hash_token === $cert2->hash_token) {
        throw new Exception("Hash token verifikasi harus tetap unik dan berbeda!");
    }
    echo "  -> PASS: Peserta 1 & 2 menerima nomor surat sama ('{$cert1->certificate_number}') dengan hash unik berbeda!\n";

    // ----------------------------------------------------
    // TEST 2: Pelatihan Kursus dengan Nomor Surat Otomatis (Dikosongkan)
    // ----------------------------------------------------
    echo "\n[TEST 2] Pelatihan Kursus dengan Nomor Surat Kosong (Auto-Numbering)...\n";
    $autoCourse = Course::create([
        'title' => 'MOOC Mandiri Literasi Kepegawaian',
        'slug' => 'mooc-auto-' . time(),
        'type' => 'mooc',
        'category' => 'Umum',
        'description' => 'Uji MOOC dengan nomor surat otomatis.',
        'jp' => 8,
        'passing_grade' => 70,
        'status' => 'published',
        'certificate_number' => null, // Kosong -> Otomatis
    ]);

    $certAuto1 = Certificate::issueForCourse($autoCourse, $user1);
    $certAuto2 = Certificate::issueForCourse($autoCourse, $user2);

    if ($certAuto1->certificate_number === $certAuto2->certificate_number) {
        throw new Exception("Nomor sertifikat otomatis harus berbeda untuk tiap peserta!");
    }
    echo "  -> PASS: Peserta 1 menerima: {$certAuto1->certificate_number}\n";
    echo "  -> PASS: Peserta 2 menerima: {$certAuto2->certificate_number}\n";
    echo "  -> PASS: Nomor sertifikat otomatis berbeda dan berurutan secara dinamis!\n";

    // ----------------------------------------------------
    // TEST 3: Webinar dengan Nomor Surat Tetap vs Otomatis
    // ----------------------------------------------------
    echo "\n[TEST 3] Webinar dengan Nomor Surat Tetap & Otomatis...\n";
    $fixedWebinar = Webinar::create([
        'organizer_id' => $user1->id,
        'title' => 'Webinar Nasional Smart City Pasuruan',
        'slug' => 'webinar-fixed-' . time(),
        'description' => 'Uji webinar fixed no surat.',
        'jp' => 4,
        'event_date' => date('Y-m-d'),
        'start_time' => '08:00:00',
        'end_time' => '12:00:00',
        'certificate_number' => '893.3/WEB-77/BKPSDM-PAS/2026',
    ]);

    $certWb1 = Certificate::issueForWebinar($fixedWebinar, $user1);
    $certWb2 = Certificate::issueForWebinar($fixedWebinar, $user2);

    if ($certWb1->certificate_number !== '893.3/WEB-77/BKPSDM-PAS/2026') {
        throw new Exception("Nomor sertifikat webinar tidak sesuai: {$certWb1->certificate_number}");
    }
    if ($certWb1->certificate_number !== $certWb2->certificate_number) {
        throw new Exception("Nomor sertifikat webinar harus sama untuk semua peserta!");
    }
    echo "  -> PASS: Webinar nomor tetap berhasil diterapkan ke semua peserta ('{$certWb1->certificate_number}')!\n";

    // ----------------------------------------------------
    // TEST 4: Validasi Publik & Eager Loading
    // ----------------------------------------------------
    echo "\n[TEST 4] Validasi Publik & Relasi Sertifikat...\n";
    $resolved = Certificate::with(['user.profile', 'template', 'webinar', 'course'])
        ->where('hash_token', $cert1->hash_token)
        ->first();

    if (!$resolved) {
        throw new Exception("Sertifikat tidak dapat ditemukan via hash_token!");
    }
    if (!$resolved->course || $resolved->course->id !== $fixedCourse->id) {
        throw new Exception("Relasi course pada sertifikat gagal di-load!");
    }
    echo "  -> PASS: Relasi Course ('{$resolved->course->title}') berhasil di-load via sertifikat!\n";
    echo "  -> PASS: Validation URL: {$resolved->validation_url}\n";

    // ----------------------------------------------------
    // TEST 5: Blade Views Rendering
    // ----------------------------------------------------
    echo "\n[TEST 5] Kompilasi & Render Blade Views...\n";
    
    Auth::login($user1);
    $courses = Course::withCount(['lessons', 'enrollments'])->get();
    $course = $fixedCourse->load(['lessons.questions', 'enrollments.user']);
    $webinars = Webinar::with(['organizer.profile'])->withCount('attendances')->get();
    $webinar = $fixedWebinar->load(['organizer.profile', 'quizQuestions', 'questionnaireQuestions']);
    $organizers = User::whereIn('role', ['admin', 'super_admin', 'widyaiswara'])->get();
    $certificates = Certificate::with(['template', 'webinar', 'course'])->where('user_id', $user1->id)->get();
    $user = $user1;

    $viewsToTest = [
        'admin.courses.index' => ['user' => $user, 'courses' => $courses],
        'admin.courses.manage' => ['user' => $user, 'course' => $course],
        'admin.webinars.index' => ['user' => $user, 'webinars' => $webinars, 'organizers' => $organizers],
        'admin.webinars.manage' => ['user' => $user, 'webinar' => $webinar, 'organizers' => $organizers],
        'peserta.certificates.index' => ['user' => $user, 'certificates' => $certificates],
        'public.validation' => ['certificate' => $cert1, 'token' => $cert1->hash_token, 'status' => 'valid'],
    ];

    foreach ($viewsToTest as $viewName => $data) {
        $html = View::make($viewName, $data)->render();
        if (strlen($html) < 500) {
            throw new Exception("View {$viewName} menghasilkan output terlalu kecil.");
        }
        echo "  [PASS] {$viewName} (" . strlen($html) . " bytes)\n";
    }

    echo "\n=== ALL TESTS PASSED SUCCESSFULLY (100%) ===\n";

} catch (Exception $e) {
    echo "\n[ERROR]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
} finally {
    DB::rollBack();
    echo "\n(Database changes safely rolled back)\n";
}
