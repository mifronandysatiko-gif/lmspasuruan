<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\Webinar;
use App\Models\LeadershipLogbook;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ViewErrorBag;

view()->share('errors', new ViewErrorBag());

echo "=== TEST: COMPREHENSIVE SCAN OF ALL VIEWS & TRAININGS ===\n\n";

$pesertaUser = User::where('role', 'peserta')->first() ?: User::first();
$adminUser = User::where('role', 'super_admin')->first();

echo "Peserta: {$pesertaUser->name} ({$pesertaUser->email})\n";
echo "Admin: {$adminUser->name} ({$adminUser->email})\n\n";

$errors = [];

// 1. Test Peserta Dashboard
try {
    Auth::login($pesertaUser);
    $req = Request::create('/peserta/dashboard', 'GET');
    $controller = new App\Http\Controllers\PesertaController();
    $view = $controller->dashboard();
    $view->render();
    echo "  [OK] /peserta/dashboard rendered successfully.\n";
} catch (\Throwable $e) {
    echo "  [ERROR] /peserta/dashboard: " . $e->getMessage() . " on line " . $e->getLine() . " in " . $e->getFile() . "\n";
    $errors[] = "Dashboard: " . $e->getMessage();
}

// 2. Test Peserta Katalog
try {
    Auth::login($pesertaUser);
    $req = Request::create('/peserta/katalog', 'GET');
    $controller = new App\Http\Controllers\PesertaController();
    $view = $controller->katalog($req);
    $view->render();
    echo "  [OK] /peserta/katalog rendered successfully.\n";
} catch (\Throwable $e) {
    echo "  [ERROR] /peserta/katalog: " . $e->getMessage() . "\n";
    $errors[] = "Katalog: " . $e->getMessage();
}

// 3. Test ALL Courses (MOOC, BIMTEK, Teknis/Fungsional) in moocShow
$allCourses = Course::all();
echo "\nTesting " . $allCourses->count() . " Courses in moocShow:\n";

foreach ($allCourses as $course) {
    try {
        Auth::login($pesertaUser);
        $controller = new App\Http\Controllers\PesertaController();
        $resp = $controller->moocShow($course->slug);
        if ($resp instanceof \Illuminate\View\View) {
            $resp->render();
            echo "  [OK] Course '{$course->title}' [{$course->type}] (slug: {$course->slug}) rendered successfully.\n";
        } elseif ($resp instanceof \Illuminate\Http\RedirectResponse) {
            echo "  [REDIRECT] Course '{$course->title}' redirected to " . $resp->getTargetUrl() . " (Reason: expected condition)\n";
        }
    } catch (\Throwable $e) {
        echo "  [ERROR] Course '{$course->title}' (slug: {$course->slug}): " . $e->getMessage() . " on line " . $e->getLine() . " in " . $e->getFile() . "\n";
        $errors[] = "Course {$course->slug}: " . $e->getMessage();
    }
}

// 4. Test Kepemimpinan Logbook
try {
    Auth::login($pesertaUser);
    $controller = new App\Http\Controllers\PesertaController();
    $view = $controller->kepemimpinan();
    if ($view instanceof \Illuminate\View\View) {
        $view->render();
        echo "\n  [OK] /peserta/kepemimpinan rendered successfully.\n";
    }
} catch (\Throwable $e) {
    echo "\n  [ERROR] /peserta/kepemimpinan: " . $e->getMessage() . " on line " . $e->getLine() . " in " . $e->getFile() . "\n";
    $errors[] = "Kepemimpinan: " . $e->getMessage();
}

// 5. Test Peserta Sertifikat
try {
    Auth::login($pesertaUser);
    $controller = new App\Http\Controllers\PesertaController();
    $view = $controller->sertifikat();
    $view->render();
    echo "  [OK] /peserta/sertifikat rendered successfully.\n";
} catch (\Throwable $e) {
    echo "  [ERROR] /peserta/sertifikat: " . $e->getMessage() . "\n";
    $errors[] = "Sertifikat: " . $e->getMessage();
}

// 6. Test ALL Webinars
$allWebinars = Webinar::all();
echo "\nTesting " . $allWebinars->count() . " Webinars in webinarRoom:\n";
foreach ($allWebinars as $webinar) {
    try {
        Auth::login($pesertaUser);
        $controller = new App\Http\Controllers\PesertaController();
        $view = $controller->webinarRoom($webinar->slug);
        $view->render();
        echo "  [OK] Webinar '{$webinar->title}' (slug: {$webinar->slug}) rendered successfully.\n";
    } catch (\Throwable $e) {
        echo "  [ERROR] Webinar '{$webinar->slug}': " . $e->getMessage() . "\n";
        $errors[] = "Webinar {$webinar->slug}: " . $e->getMessage();
    }
}

// 7. Test Admin Results for ALL Courses
echo "\nTesting Admin Results for all courses:\n";
foreach ($allCourses as $course) {
    try {
        Auth::login($adminUser);
        $controller = new App\Http\Controllers\AdminController();
        $view = $controller->courseResults($course->id);
        $view->render();
        echo "  [OK] Admin results for '{$course->title}' (id: {$course->id}) rendered successfully.\n";
    } catch (\Throwable $e) {
        echo "  [ERROR] Admin results for '{$course->title}': " . $e->getMessage() . "\n";
        $errors[] = "Admin courseResults {$course->id}: " . $e->getMessage();
    }
}

// 8. Test Admin Kepemimpinan Review
try {
    Auth::login($adminUser);
    $controller = new App\Http\Controllers\AdminController();
    $view = $controller->kepemimpinanReview();
    $view->render();
    echo "\n  [OK] Admin /admin/kepemimpinan/review rendered successfully.\n";
} catch (\Throwable $e) {
    echo "\n  [ERROR] Admin kepemimpinan review: " . $e->getMessage() . "\n";
    $errors[] = "Admin kepemimpinan review: " . $e->getMessage();
}

// 9. Test Admin Certificate Designer
try {
    Auth::login($adminUser);
    $controller = new App\Http\Controllers\AdminController();
    $view = $controller->certificatesDesigner();
    $view->render();
    echo "  [OK] Admin /admin/certificates/designer rendered successfully.\n";
} catch (\Throwable $e) {
    echo "  [ERROR] Admin certificates designer: " . $e->getMessage() . "\n";
    $errors[] = "Admin certificates designer: " . $e->getMessage();
}

echo "\n=== RINGKASAN HASIL SCAN ===\n";
if (empty($errors)) {
    echo "SEMUA VIEW, MOOC, BIMTEK, KEPEMIMPINAN, WEBINAR, DAN ADMIN PAGES 100% BEBAS DARI ERROR!\n";
} else {
    echo "DITEMUKAN ERROR:\n";
    foreach ($errors as $err) {
        echo "- " . $err . "\n";
    }
}
