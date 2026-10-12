<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Webinar;
use App\Models\WebinarQuestion;
use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

echo "=== TESTING WEBINAR CREATION & VIEW RENDERING ===\n";

$admin = User::where('role', 'super_admin')->first();
$penyelenggara = User::where('role', 'penyelenggara')->first();

Auth::login($penyelenggara);
$ctrl = new AdminController();

// 1. Test Webinar Creation via webinarStore
$req = Request::create('/admin/webinars', 'POST', [
    'title' => 'Webinar Uji Coba Pembuatan Sesi Baru 2026',
    'description' => 'Deskripsi uji coba pembuatan webinar oleh penyelenggara diklat.',
    'event_date' => date('Y-m-d'),
    'jp' => 3,
    'start_time' => '09:00',
    'end_time' => '11:30',
    'meeting_link' => 'https://zoom.us/j/1234567890',
    'questionnaire_start_time' => '10:00',
    'questionnaire_end_time' => '11:45',
    'exam_start_time' => '10:30',
    'exam_end_time' => '13:00',
    'passing_grade' => 80,
    'max_remedial' => 5,
    'remedial_cap_score' => 90,
]);

$response = $ctrl->webinarStore($req);
echo "1. Controller webinarStore execution: ";

$newWebinar = Webinar::where('title', 'Webinar Uji Coba Pembuatan Sesi Baru 2026')->first();
if ($newWebinar) {
    echo "SUCCESS (Created ID: {$newWebinar->id}, Slug: {$newWebinar->slug})\n";
} else {
    echo "FAILED\n";
    exit(1);
}

// 2. Check default questions seeded
$qCount = WebinarQuestion::where('webinar_id', $newWebinar->id)->count();
echo "2. Default questions initialized: ";
if ($qCount >= 4) {
    echo "SUCCESS ({$qCount} questions initialized)\n";
} else {
    echo "FAILED ({$qCount} questions)\n";
    exit(1);
}

// 3. Test View Rendering admin.webinars.index
echo "3. Render view admin.webinars.index: ";
$webinars = Webinar::with(['organizer.profile'])->withCount('attendances')->orderBy('created_at', 'desc')->get();
$rendered = View::make('admin.webinars.index', [
    'user' => $penyelenggara,
    'webinars' => $webinars,
])->render();

if (str_contains($rendered, 'Buat Sesi Webinar Baru') && str_contains($rendered, 'createModalOpen')) {
    echo "SUCCESS (" . strlen($rendered) . " bytes)\n";
} else {
    echo "FAILED\n";
    exit(1);
}

// Clean up test webinar
$newWebinar->delete();
echo "4. Cleanup test webinar: SUCCESS\n";

echo "=== ALL CHECKS PASSED SUCCESSFULLY! ===\n";
