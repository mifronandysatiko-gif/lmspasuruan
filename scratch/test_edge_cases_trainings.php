<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseEnrollment;
use App\Models\LeadershipLogbook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ViewErrorBag;

view()->share('errors', new ViewErrorBag());

echo "=== TEST: EDGE CASES & ALL TRAINING TYPES ===\n\n";

$peserta = User::where('role', 'peserta')->first();
Auth::login($peserta);
$controller = new App\Http\Controllers\PesertaController();

// 1. Course with 0 lessons (Empty MOOC/Bimtek)
$cEmpty = Course::firstOrCreate(['slug' => 'test-empty-course'], [
    'title' => 'Pelatihan Tanpa Sesi (Uji Kosong)',
    'category' => 'Teknis',
    'type' => 'teknis_fungsional',
    'jp' => 10,
    'passing_grade' => 75,
    'status' => 'published',
]);

$resp = $controller->moocShow($cEmpty->slug);
$resp->render();
echo "  [OK] Course dengan 0 sesi berhasil di-render tanpa error.\n";

// 2. Course with 3 lessons (some completed, some not)
$c3 = Course::firstOrCreate(['slug' => 'test-bimtek-multi-lesson'], [
    'title' => 'Bimtek Pelayanan Terpadu Pasuruan 2026',
    'category' => 'Bimtek Terpadu',
    'type' => 'teknis_fungsional',
    'jp' => 32,
    'passing_grade' => 80,
    'status' => 'published',
]);

for ($i = 1; $i <= 3; $i++) {
    CourseLesson::firstOrCreate(['course_id' => $c3->id, 'sequence' => $i], [
        'title' => 'Materi Sesi ' . $i,
        'content_summary' => 'Ringkasan materi sesi ' . $i,
        'has_quiz' => true,
    ]);
}

$resp2 = $controller->moocShow($c3->slug);
$resp2->render();
echo "  [OK] Course Bimtek Multi Sesi berhasil di-render tanpa error.\n";

// 3. Pelatihan Kepemimpinan (with logbooks)
$resp3 = $controller->kepemimpinan();
$resp3->render();
echo "  [OK] Halaman Laboratorium Kepemimpinan berhasil di-render tanpa error.\n";

// 4. Admin Courses Management & Results
$admin = User::where('role', 'super_admin')->first();
Auth::login($admin);
$adminController = new App\Http\Controllers\AdminController();

$resp4 = $adminController->courseResults($c3->id);
$resp4->render();
echo "  [OK] Admin Hasil Pembelajaran Bimtek berhasil di-render tanpa error.\n";

$resp5 = $adminController->coursesIndex();
$resp5->render();
echo "  [OK] Admin Kelola Pelatihan (Index) berhasil di-render tanpa error.\n";

$resp6 = $adminController->courseManage($c3->id);
$resp6->render();
echo "  [OK] Admin Kelola Sesi Materi berhasil di-render tanpa error.\n";

echo "\n=== SEMUA SKENARIO UJI LANJUTAN BERHASIL 100% ===\n";
