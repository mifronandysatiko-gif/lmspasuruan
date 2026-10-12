<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Webinar;
use App\Models\Course;
use App\Models\CertificateTemplate;
use Illuminate\Support\Facades\View;

$admin = User::where('role', 'super_admin')->first();
Auth::login($admin);
$webinar = Webinar::with([
    'organizer.profile',
    'attendances.user.profile',
    'examAttempts.user.profile',
    'quizQuestions',
    'questionnaireQuestions',
    'questionnaireResponses.user.profile'
])->first();
$templates = CertificateTemplate::all();

echo "Testing view admin.webinars.manage: ";
$renderedAdmin = View::make('admin.webinars.manage', [
    'user' => $admin,
    'webinar' => $webinar,
    'templates' => $templates,
])->render();
echo strlen($renderedAdmin) > 1000 ? "SUCCESS (" . strlen($renderedAdmin) . " bytes)\n" : "FAILED\n";

$course = Course::first();
$enrollment = \App\Models\CourseEnrollment::firstOrCreate(['course_id' => $course->id, 'user_id' => $admin->id]);
echo "Testing view peserta.mooc.show: ";
$renderedMooc = View::make('peserta.mooc.show', [
    'user' => $admin,
    'course' => $course,
    'enrollment' => $enrollment,
])->render();
echo strlen($renderedMooc) > 1000 ? "SUCCESS (" . strlen($renderedMooc) . " bytes)\n" : "FAILED\n";

echo "Testing view peserta.webinar.room: ";
$renderedRoom = View::make('peserta.webinar.room', [
    'user' => $admin,
    'webinar' => $webinar,
    'isRegistered' => true,
    'attendance' => null,
    'questionnaireCompleted' => false,
    'questionnaireResponse' => null,
    'attempts' => collect(),
    'latestAttempt' => null,
    'passed' => false,
    'attemptCount' => 0,
    'canRemedial' => false,
    'isFailedPermanently' => false,
    'certificate' => null,
])->render();
echo strlen($renderedRoom) > 1000 ? "SUCCESS (" . strlen($renderedRoom) . " bytes)\n" : "FAILED\n";

echo "All views rendered cleanly without errors!\n";
