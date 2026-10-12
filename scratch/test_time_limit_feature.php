<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\CourseEnrollment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

echo "=== STARTING TIME LIMIT & DURATION FEATURE TEST ===\n\n";

$asnUser = User::where('role', 'peserta')->whereNotNull('nip')->first();
if (!$asnUser) {
    $asnUser = User::whereNotNull('nip')->first();
}

// 1. Test Course Model Time Limit Helpers
echo "[1] Testing Course Model Time Limit Helpers...\n";

$courseNone = new Course(['time_limit_type' => 'none']);
assert(!$courseNone->hasTimeLimit(), "Course with none should not have time limit");

$courseRel = new Course([
    'time_limit_type' => 'relative_days',
    'duration_days' => 14
]);
assert($courseRel->hasTimeLimit(), "Course with relative_days should have time limit");
assert(str_contains($courseRel->getTimeLimitDescription(), '14 Hari'), "getTimeLimitDescription should contain 14 Hari");

$coursePast = new Course([
    'time_limit_type' => 'fixed_date',
    'start_date' => Carbon::now()->subDays(30),
    'end_date' => Carbon::now()->subDays(5)
]);
assert($coursePast->isRegistrationClosed(), "Course with past end_date should have isRegistrationClosed = true");

$courseFuture = new Course([
    'time_limit_type' => 'fixed_date',
    'start_date' => Carbon::now()->addDays(5),
    'end_date' => Carbon::now()->addDays(30)
]);
assert($courseFuture->isNotStartedYet(), "Course with future start_date should have isNotStartedYet = true");

echo "  -> Course helpers verified successfully!\n\n";

// 2. Test CourseEnrollment Expiry & Status Change
echo "[2] Testing CourseEnrollment Expiry Enforcement...\n";

// Create or find a test course
$testCourse = Course::firstOrCreate(
    ['slug' => 'test-time-limit-course'],
    [
        'title' => 'Kursus Uji Coba Batas Waktu LMS',
        'type' => 'mooc',
        'category' => 'Pengembangan Integritas',
        'description' => 'Kursus testing batas waktu',
        'jp' => 10,
        'passing_grade' => 75,
        'status' => 'published',
        'time_limit_type' => 'relative_days',
        'duration_days' => 7,
    ]
);

// Create test enrollment with deadline in the past
$testEnrollment = CourseEnrollment::updateOrCreate(
    ['course_id' => $testCourse->id, 'user_id' => $asnUser->id],
    [
        'enrolled_at' => Carbon::now()->subDays(10),
        'deadline_at' => Carbon::now()->subDays(3),
        'status' => 'in_progress',
        'progress_percentage' => 50,
    ]
);

assert($testEnrollment->isExpired(), "Enrollment should be identified as expired");
$expiredResult = $testEnrollment->checkAndEnforceExpiry();
assert($expiredResult === true, "checkAndEnforceExpiry should return true when transitioning to failed");
assert($testEnrollment->fresh()->status === 'failed', "Enrollment status in database should be updated to failed");

// Verify getParticipantStatus shows 'failed'
$participantStatus = $testCourse->fresh()->getParticipantStatus($asnUser);
assert($participantStatus['status'] === 'failed', "getParticipantStatus should return status failed");
assert(str_contains($participantStatus['label'], 'Tidak Lulus'), "Label should contain 'Tidak Lulus'");

echo "  -> Expiry enforcement & automatic 'failed' status verified successfully!\n\n";

// 3. Test That 'passed' Enrollment is NOT Overwritten by Expiry
echo "[3] Testing That Passed Enrollment is Preserved...\n";

$testEnrollment->update([
    'status' => 'passed',
    'deadline_at' => Carbon::now()->subDays(1)
]);
$passedExpiryResult = $testEnrollment->checkAndEnforceExpiry();
assert($passedExpiryResult === false, "checkAndEnforceExpiry should not fail an already passed enrollment");
assert($testEnrollment->fresh()->status === 'passed', "Enrollment should remain passed");

echo "  -> Passed status preservation verified successfully!\n\n";

// 4. Test View Compilation
echo "[4] Testing Blade Views Compilation with Time Limit Data...\n";

Auth::login($asnUser);

$viewsToTest = [
    'admin.courses.index' => [
        'user' => $asnUser,
        'courses' => Course::withCount(['lessons', 'enrollments'])->get(),
    ],
    'admin.courses.manage' => [
        'user' => $asnUser,
        'course' => $testCourse->fresh()->load('lessons.questions'),
    ],
    'peserta.katalog' => [
        'user' => $asnUser,
        'courses' => Course::where('status', 'published')->get(),
        'webinars' => collect([]),
        'category' => 'all',
        'search' => '',
    ],
    'peserta.mooc.show' => [
        'user' => $asnUser,
        'course' => Course::with('lessons.questions')->first(),
        'enrollment' => CourseEnrollment::where('user_id', $asnUser->id)->first(),
    ],
    'peserta.kepemimpinan.logbook' => [
        'user' => $asnUser,
        'logbooks' => collect([]),
        'coach' => User::where('role', 'widyaiswara')->first(),
        'mentor' => User::where('role', 'mentor')->first(),
        'leadershipCourse' => Course::where('type', 'kepemimpinan')->first(),
        'enrollment' => CourseEnrollment::where('user_id', $asnUser->id)->first(),
    ],
    'peserta.dashboard' => [
        'user' => $asnUser,
        'totalJp' => 20,
        'certificates' => collect([]),
        'enrolledCourses' => CourseEnrollment::with('course')->where('user_id', $asnUser->id)->get(),
        'upcomingWebinars' => collect([]),
        'attendedWebinarsCount' => 2,
        'recentCertificates' => collect([]),
        'marqueeText' => 'Pengumuman Uji Coba',
    ],
];

foreach ($viewsToTest as $viewName => $data) {
    try {
        $html = View::make($viewName, $data)->render();
        echo "  [OK] View '$viewName' rendered successfully (" . strlen($html) . " bytes)\n";
    } catch (\Throwable $e) {
        echo "  [FAIL] View '$viewName' failed with error: " . $e->getMessage() . "\n";
        echo "    in " . $e->getFile() . ":" . $e->getLine() . "\n";
        exit(1);
    }
}

// Clean up test course
$testEnrollment->delete();
$testCourse->delete();

// 5. Test Controller Guards for Expired / Closed Courses
echo "\n[5] Testing Controller Guards for Expired / Closed Courses...\n";

// A. Test moocShow when registration closed for non-enrolled user
$closedCourse = Course::create([
    'title' => 'Closed Registration Course',
    'slug' => 'closed-reg-course-' . time(),
    'type' => 'mooc',
    'category' => 'Testing',
    'description' => 'Test course',
    'jp' => 2,
    'passing_grade' => 70,
    'status' => 'published',
    'time_limit_type' => 'fixed_date',
    'start_date' => Carbon::now()->subDays(20),
    'end_date' => Carbon::now()->subDays(2),
]);

$controller = new \App\Http\Controllers\PesertaController();
$response = $controller->moocShow($closedCourse->slug);
assert($response->isRedirect(route('peserta.katalog')), "Should redirect to katalog when registration closed");

// B. Test moocSubmitAssignment when enrollment is failed
$failedCourse = Course::create([
    'title' => 'Failed Enrollment Course',
    'slug' => 'failed-enroll-course-' . time(),
    'type' => 'mooc',
    'category' => 'Testing',
    'description' => 'Test course',
    'jp' => 2,
    'passing_grade' => 70,
    'status' => 'published',
    'time_limit_type' => 'relative_days',
    'duration_days' => 5,
]);
$failedEnrollment = CourseEnrollment::create([
    'course_id' => $failedCourse->id,
    'user_id' => $asnUser->id,
    'enrolled_at' => Carbon::now()->subDays(10),
    'deadline_at' => Carbon::now()->subDays(5),
    'status' => 'failed',
]);

$assignReq = new \Illuminate\Http\Request(['assignment_notes' => 'Test']);
$assignResp = $controller->moocSubmitAssignment($failedCourse->slug, $assignReq);
assert(session('error') !== null, "Should return error session flash on failed assignment submit");

$quizReq = new \Illuminate\Http\Request(['answers' => []]);
$quizResp = $controller->moocSubmitQuiz($failedCourse->slug, $quizReq);
assert(session('error') !== null, "Should return error session flash on failed quiz submit");

echo "  -> Controller guards verified successfully!\n";

// Cleanup
$failedEnrollment->delete();
$failedCourse->delete();
$closedCourse->delete();

echo "\n=== ALL TIME LIMIT FEATURE TESTS PASSED SUCCESSFULLY! ===\n";
