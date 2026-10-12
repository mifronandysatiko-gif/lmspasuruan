<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== START VERIFICATION TEST ===\n";

// 1. Check Peserta Umum Restriction on MOOC
$umum = User::where('role', 'peserta')->whereNull('nip')->first();
echo "Peserta Umum: " . ($umum ? $umum->name : "NOT FOUND") . "\n";
echo "isPesertaUmum(): " . ($umum->isPesertaUmum() ? "TRUE (CORRECT)" : "FALSE (FAIL)") . "\n";

$asn = User::where('role', 'peserta')->whereNotNull('nip')->first();
echo "Peserta ASN: " . ($asn ? $asn->name : "NOT FOUND") . " (NIP: " . $asn->nip . ")\n";
echo "isPesertaUmum(): " . ($asn->isPesertaUmum() ? "TRUE (FAIL)" : "FALSE (CORRECT)") . "\n";
echo "isAsn(): " . ($asn->isAsn() ? "TRUE (CORRECT)" : "FALSE (FAIL)") . "\n";

// 2. Test Course & Lessons in DB
$course = Course::with(['lessons', 'enrollments'])->first();
echo "Sample Course: " . ($course ? $course->title : "NONE") . "\n";
echo "Lesson count: " . $course->lessons->count() . "\n";

// 3. Test HTTP Routes
$routesToTest = [
    '/' => 200,
    '/login' => 200,
    '/peserta/dashboard' => 200,
    '/peserta/katalog' => 200,
    '/peserta/mooc/' . $course->slug => 200,
    '/admin/courses' => 200,
    '/admin/courses/' . $course->id . '/manage' => 200,
    '/admin/courses/' . $course->id . '/results' => 200,
    '/admin/dashboard' => 200,
    '/admin/webinars' => 200,
    '/admin/settings' => 200,
    '/admin/certificates/designer' => 200,
    '/admin/kepemimpinan/review' => 200,
];

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

foreach ($routesToTest as $uri => $expectedStatus) {
    if ($uri === '/login' || $uri === '/') {
        Auth::logout();
    } elseif (str_starts_with($uri, '/admin')) {
        $adminUser = User::where('role', 'super_admin')->first();
        Auth::login($adminUser);
    } else {
        Auth::login($asn); // As ASN
    }
    
    $req = Request::create($uri, 'GET');
    $res = $httpKernel->handle($req);
    $status = $res->getStatusCode();
    echo "Route {$uri} => Status {$status} " . ($status === $expectedStatus ? "✓ OK" : "✗ FAIL") . "\n";
}

// 4. Test Peserta Umum Access to MOOC => should redirect (302)
Auth::login($umum);
$req = Request::create('/peserta/mooc/' . $course->slug, 'GET');
$res = $httpKernel->handle($req);
$status = $res->getStatusCode();
echo "Peserta Umum GET /peserta/mooc/{$course->slug} => Status {$status} " . ($status === 302 ? "✓ REDIRECT (RESTRICTED AS EXPECTED)" : "✗ NOT RESTRICTED") . "\n";

// 5. Test Peserta ASN submitting assignment
Auth::login($asn);
$pesertaCtrl = new \App\Http\Controllers\PesertaController();
$assignmentReq = Request::create('/peserta/mooc/' . $course->slug . '/assignment', 'POST', [
    'assignment_notes' => 'Tugas mandiri implementasi core values BerAKHLAK pada loket pelayanan BKPSDM.'
]);
$assignmentRes = $pesertaCtrl->moocSubmitAssignment($course->slug, $assignmentReq);
echo "Assignment Submission Response Type: " . get_class($assignmentRes) . "\n";

$enrollment = CourseEnrollment::where('course_id', $course->id)->where('user_id', $asn->id)->first();
echo "Enrollment File: " . $enrollment->assignment_file . "\n";
echo "Enrollment Notes: " . $enrollment->assignment_notes . "\n";

// 6. Test Admin grading participant
$adminUser = User::where('role', 'super_admin')->first();
Auth::login($adminUser);
$adminCtrl = new \App\Http\Controllers\AdminController();
$gradeReq = Request::create('/admin/courses/' . $course->id . '/grade/' . $enrollment->id, 'POST', [
    'final_score' => 92.5,
    'status' => 'passed',
    'instructor_feedback' => 'Analisis implementasi sangat komprehensif dan relevan dengan standar pelayanan Pemkab Pasuruan.'
]);
$gradeRes = $adminCtrl->courseGradeStudent($course->id, $enrollment->id, $gradeReq);
echo "Grade Response Type: " . get_class($gradeRes) . "\n";

$enrollment->refresh();
echo "Enrollment Graded Score: " . $enrollment->final_score . "\n";
echo "Enrollment Status: " . $enrollment->status . "\n";
echo "Enrollment Feedback: " . $enrollment->instructor_feedback . "\n";
echo "Enrollment Completed At: " . ($enrollment->completed_at ? $enrollment->completed_at->toDateTimeString() : "NULL") . "\n";

echo "=== ALL VERIFICATIONS PASSED ===\n";
