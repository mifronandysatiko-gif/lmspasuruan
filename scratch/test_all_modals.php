<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\Webinar;
use App\Models\LiteracyDocument;
use App\Models\CertificateTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

echo "=== TESTING ALL MODALS VIEW RENDERING ===\n\n";

$adminUser = User::where('role', 'super_admin')->first() ?? User::first();
Auth::login($adminUser);

$webinar = Webinar::first();
$course = Course::first();
$template = CertificateTemplate::first();

$views = [
    'admin.courses.index' => [
        'user' => $adminUser,
        'courses' => Course::withCount(['lessons', 'enrollments'])->get(),
    ],
    'admin.courses.manage' => [
        'user' => $adminUser,
        'course' => $course,
    ],
    'admin.webinars.index' => [
        'user' => $adminUser,
        'webinars' => Webinar::withCount(['attendances'])->get(),
        'organizers' => User::whereIn('role', ['penyelenggara', 'super_admin'])->get(),
    ],
    'admin.webinars.manage' => [
        'user' => $adminUser,
        'webinar' => $webinar,
        'organizers' => User::whereIn('role', ['penyelenggara', 'super_admin'])->get(),
    ],
    'admin.users.index' => [
        'user' => $adminUser,
        'users' => User::paginate(10),
        'stats' => [
            'total_users' => 10,
            'total_asn' => 8,
            'total_umum' => 2,
            'total_widyaiswara' => 1,
            'total_mentor' => 1,
            'total_penyelenggara' => 1,
            'total_super_admin' => 1,
            'active_users' => 10,
        ],
        'search' => '',
        'roleFilter' => 'all',
        'opdFilter' => 'all',
        'opdList' => collect([]),
        'status' => 'all',
        'errors' => new \Illuminate\Support\ViewErrorBag(),
    ],
    'admin.courses.results' => [
        'user' => $adminUser,
        'course' => $course,
        'enrollments' => collect([]),
    ],
    'admin.kepemimpinan.review' => [
        'user' => $adminUser,
        'logbooks' => collect([]),
    ],
    'admin.literasi.index' => [
        'user' => $adminUser,
        'documents' => LiteracyDocument::paginate(10),
        'stats' => [
            'total' => 10,
            'jurnal' => 4,
            'karya_ilmiah' => 3,
            'policy_brief' => 2,
            'modul' => 1,
            'published' => 8,
            'draft' => 2,
            'total_downloads' => 150,
            'total_views' => 300,
        ],
        'category' => 'all',
        'search' => '',
        'errors' => new \Illuminate\Support\ViewErrorBag(),
    ],
    'admin.certificates.designer' => [
        'user' => $adminUser,
        'template' => $template,
        'errors' => new \Illuminate\Support\ViewErrorBag(),
    ],
    'peserta.certificates.index' => [
        'user' => $adminUser,
        'certificates' => collect([]),
    ],
    'peserta.mooc.show' => [
        'user' => $adminUser,
        'course' => $course,
        'enrollment' => \App\Models\CourseEnrollment::first() ?? new \App\Models\CourseEnrollment(),
    ],
];

foreach ($views as $viewName => $data) {
    try {
        $html = View::make($viewName, $data)->render();
        echo "  [PASS] {$viewName} (" . strlen($html) . " bytes)\n";
    } catch (\Throwable $e) {
        echo "  [FAIL] {$viewName}: " . $e->getMessage() . "\n";
        echo "    at " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}

echo "\n=== ALL VIEW RENDERING CHECKS COMPLETED ===\n";
