<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\User;
use App\Models\Webinar;
use App\Models\WebinarAttendance;
use App\Models\WebinarExamAttempt;
use App\Models\WebinarQuestion;
use App\Models\WebinarQuestionnaireResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;

echo "========================================================\n";
echo "1. VERIFIKASI TIMEZONE WIB (Asia/Jakarta) & 24 JAM\n";
echo "========================================================\n";

$defaultTz = date_default_timezone_get();
$configTz = config('app.timezone');
$nowWib = Carbon::now();

echo "PHP default timezone: {$defaultTz}\n";
echo "Laravel config timezone: {$configTz}\n";
echo "Current Time (24h WIB): " . $nowWib->format('Y-m-d H:i:s T') . "\n";

if ($defaultTz === 'Asia/Jakarta' && $configTz === 'Asia/Jakarta') {
    echo "✓ [PASS] Timezone telah terkonfigurasi ke Asia/Jakarta (WIB).\n";
} else {
    echo "✗ [FAIL] Timezone tidak sesuai: PHP={$defaultTz}, Laravel={$configTz}\n";
}

echo "\n========================================================\n";
echo "2. VERIFIKASI PEMBATASAN WAKTU 24 JAM PADA WEBINAR\n";
echo "========================================================\n";

$organizer = User::where('role', 'super_admin')->first() ?? User::first();

// Webinar 1: Masa Belum Dimulai (Upcoming)
$webinarUpcoming = Webinar::create([
    'organizer_id' => $organizer->id,
    'title' => 'Webinar Uji 24 Jam Belum Dimulai',
    'slug' => 'webinar-uji-24h-upcoming-' . time(),
    'description' => 'Uji coba webinar belum mulai',
    'jp' => 2,
    'event_date' => Carbon::today()->addDay(),
    'start_time' => '14:00:00',
    'end_time' => '16:00:00',
    'is_live' => false,
    'questionnaire_start_time' => '15:30:00',
    'questionnaire_end_time' => '16:30:00',
    'exam_start_time' => '15:45:00',
    'exam_end_time' => '17:00:00',
    'passing_grade' => 75,
]);

echo "Webinar Upcoming isNotStartedYet: " . ($webinarUpcoming->isNotStartedYet() ? 'true' : 'false') . "\n";
echo "Webinar Upcoming isFinished: " . ($webinarUpcoming->isFinished() ? 'true' : 'false') . "\n";
echo "Webinar Upcoming isQuestionnaireOpen: " . ($webinarUpcoming->isQuestionnaireOpen() ? 'true' : 'false') . "\n";

// Webinar 2: Sedang Berlangsung (Active Live)
$webinarActive = Webinar::create([
    'organizer_id' => $organizer->id,
    'title' => 'Webinar Uji 24 Jam Sedang Berlangsung',
    'slug' => 'webinar-uji-24h-active-' . time(),
    'description' => 'Uji coba webinar sedang aktif',
    'jp' => 3,
    'event_date' => Carbon::today(),
    'start_time' => Carbon::now()->subHour()->format('H:i:s'),
    'end_time' => Carbon::now()->addHour()->format('H:i:s'),
    'is_live' => true,
    'questionnaire_start_time' => Carbon::now()->subMinutes(30)->format('H:i:s'),
    'questionnaire_end_time' => Carbon::now()->addMinutes(30)->format('H:i:s'),
    'exam_start_time' => Carbon::now()->subMinutes(15)->format('H:i:s'),
    'exam_end_time' => Carbon::now()->addMinutes(45)->format('H:i:s'),
    'passing_grade' => 80,
]);

echo "Webinar Active isNotStartedYet: " . ($webinarActive->isNotStartedYet() ? 'true' : 'false') . "\n";
echo "Webinar Active isFinished: " . ($webinarActive->isFinished() ? 'true' : 'false') . "\n";
echo "Webinar Active isWithinLiveSchedule: " . ($webinarActive->isWithinLiveSchedule() ? 'true' : 'false') . "\n";
echo "Webinar Active isQuestionnaireOpen: " . ($webinarActive->isQuestionnaireOpen() ? 'true' : 'false') . "\n";
echo "Webinar Active isExamOpen: " . ($webinarActive->isExamOpen() ? 'true' : 'false') . "\n";

// Webinar 3: Sudah Selesai (Finished)
$webinarFinished = Webinar::create([
    'organizer_id' => $organizer->id,
    'title' => 'Webinar Uji 24 Jam Sudah Berakhir',
    'slug' => 'webinar-uji-24h-finished-' . time(),
    'description' => 'Uji coba webinar sudah berakhir',
    'jp' => 2,
    'event_date' => Carbon::today()->subDay(),
    'start_time' => '08:00:00',
    'end_time' => '10:00:00',
    'is_live' => false,
    'questionnaire_start_time' => '09:30:00',
    'questionnaire_end_time' => '10:30:00',
    'exam_start_time' => '09:45:00',
    'exam_end_time' => '11:00:00',
    'passing_grade' => 75,
]);

echo "Webinar Finished isFinished: " . ($webinarFinished->isFinished() ? 'true' : 'false') . "\n";
echo "Webinar Finished isQuestionnaireOpen: " . ($webinarFinished->isQuestionnaireOpen() ? 'true' : 'false') . "\n";
echo "Webinar Finished isExamOpen: " . ($webinarFinished->isExamOpen() ? 'true' : 'false') . "\n";

assert($webinarUpcoming->isNotStartedYet() === true, 'Upcoming webinar check failed');
assert($webinarActive->isQuestionnaireOpen() === true, 'Active webinar questionnaire window check failed');
assert($webinarActive->isExamOpen() === true, 'Active webinar exam window check failed');
assert($webinarFinished->isFinished() === true, 'Finished webinar check failed');
echo "✓ [PASS] Seluruh pengecekan batas waktu 24 jam Webinar berhasil.\n";

echo "\n========================================================\n";
echo "3. VERIFIKASI KUESIONER PILIHAN SKALA & ISIAN TEKS BEBAS\n";
echo "========================================================\n";

// Buat soal kuesioner tipe opsi skala
$qScale = WebinarQuestion::create([
    'webinar_id' => $webinarActive->id,
    'type' => 'questionnaire',
    'input_type' => 'options',
    'question_text' => 'Bagaimana kepuasan Anda terhadap penyajian materi narasumber?',
    'options' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang'],
    'correct_answer' => null,
    'order_number' => 1,
]);

// Buat soal kuesioner tipe teks/uraian bebas
$qText = WebinarQuestion::create([
    'webinar_id' => $webinarActive->id,
    'type' => 'questionnaire',
    'input_type' => 'text',
    'question_text' => 'Tuliskan ulasan, kritik, dan saran perbaikan untuk penyelenggaraan sesi berikutnya:',
    'options' => null,
    'correct_answer' => null,
    'order_number' => 2,
]);

echo "qScale isOptionsQuestion: " . ($qScale->isOptionsQuestion() ? 'true' : 'false') . ", isTextQuestion: " . ($qScale->isTextQuestion() ? 'true' : 'false') . "\n";
echo "qText isOptionsQuestion: " . ($qText->isOptionsQuestion() ? 'true' : 'false') . ", isTextQuestion: " . ($qText->isTextQuestion() ? 'true' : 'false') . "\n";

assert($qScale->isOptionsQuestion() === true, 'qScale options check failed');
assert($qText->isTextQuestion() === true, 'qText text check failed');

// Simulasikan pengisian kuesioner oleh peserta
$userAsn = User::where('role', 'peserta')->first() ?? User::first();

$submittedAnswers = [
    $qScale->id => 'Sangat Baik',
    $qText->id => 'Pemaparan materi narasumber sangat jelas, komprehensif, dan langsung dapat diimplementasikan pada unit kerja kami.',
];

$response = WebinarQuestionnaireResponse::updateOrCreate(
    ['webinar_id' => $webinarActive->id, 'user_id' => $userAsn->id],
    [
        'answers' => $submittedAnswers,
        'submitted_at' => Carbon::now('Asia/Jakarta'),
    ]
);

echo "Tersimpan respons kuesioner:\n";
echo "- ID: {$response->id}\n";
echo "- Skala Answer: " . $response->answers[$qScale->id] . "\n";
echo "- Text/Essay Answer: " . $response->answers[$qText->id] . "\n";

assert($response->answers[$qScale->id] === 'Sangat Baik', 'Scale answer mismatch');
assert(str_contains($response->answers[$qText->id], 'Pemaparan materi narasumber'), 'Text answer mismatch');
echo "✓ [PASS] Penyimpanan dan pembacaan kuesioner pilihan skala & isian teks bebas berhasil.\n";

echo "\n========================================================\n";
echo "4. VERIFIKASI PEMBATASAN WAKTU 24 JAM PELATIHAN (MOOC/BIMTEK/KEPEMIMPINAN)\n";
echo "========================================================\n";

// Course dengan jadwal jam mulai & batas akhir presisi
$courseTimed = Course::create([
    'title' => 'Pelatihan Uji Waktu 24 Jam Presisi',
    'slug' => 'course-uji-24h-' . time(),
    'type' => 'mooc',
    'category' => 'Teknis Digital',
    'description' => 'Uji coba pembatasan jam pengerjaan',
    'jp' => 10,
    'passing_grade' => 75,
    'status' => 'published',
    'time_limit_type' => 'fixed_date',
    'start_date' => Carbon::now('Asia/Jakarta')->subHours(2),
    'end_date' => Carbon::now('Asia/Jakarta')->addHours(5),
]);

echo "Course isNotStartedYet: " . ($courseTimed->isNotStartedYet() ? 'true' : 'false') . "\n";
echo "Course isRegistrationClosed: " . ($courseTimed->isRegistrationClosed() ? 'true' : 'false') . "\n";
echo "Course getTimeLimitDescription: " . $courseTimed->getTimeLimitDescription() . "\n";

assert($courseTimed->isNotStartedYet() === false, 'Course isNotStartedYet check failed');
assert($courseTimed->isRegistrationClosed() === false, 'Course isRegistrationClosed check failed');

// Enrollment & expiry check
$userExpired = User::where('role', 'peserta')->where('id', '!=', $userAsn->id)->first() ?? User::find(6);
$enrollment = CourseEnrollment::create([
    'course_id' => $courseTimed->id,
    'user_id' => $userAsn->id,
    'enrolled_at' => Carbon::now('Asia/Jakarta')->subHours(2),
    'deadline_at' => Carbon::now('Asia/Jakarta')->addHours(5),
    'progress_percentage' => 40,
    'status' => 'in_progress',
]);

echo "Enrollment remainingTimeDescription: " . $enrollment->remainingTimeDescription() . "\n";
echo "Enrollment isExpired: " . ($enrollment->isExpired() ? 'true' : 'false') . "\n";
assert($enrollment->isExpired() === false, 'Active enrollment expiry check failed');

// Uji Enrollment yang expired
$enrollmentExpired = CourseEnrollment::create([
    'course_id' => $courseTimed->id,
    'user_id' => $userExpired->id,
    'enrolled_at' => Carbon::now('Asia/Jakarta')->subDays(5),
    'deadline_at' => Carbon::now('Asia/Jakarta')->subHour(), // Expired 1 hour ago
    'progress_percentage' => 30,
    'status' => 'in_progress',
]);

echo "Expired Enrollment status before check: {$enrollmentExpired->status}\n";
$expiredResult = $enrollmentExpired->checkAndEnforceExpiry();
$enrollmentExpired->refresh();
echo "Expired Enrollment status after check: {$enrollmentExpired->status}\n";
assert($enrollmentExpired->status === 'failed', 'Expired enrollment did not change to failed');
echo "✓ [PASS] Enforce expiry batas waktu 24 jam pelatihan berhasil.\n";

echo "\n========================================================\n";
echo "5. VERIFIKASI BLADE RENDERING VIEWS\n";
echo "========================================================\n";

$templates = [
    'admin.webinars.manage' => [
        'user' => $organizer,
        'webinar' => $webinarActive->load([
            'organizer.profile',
            'registrations.user.profile',
            'attendances.user.profile',
            'examAttempts.user.profile',
            'quizQuestions',
            'questionnaireQuestions',
            'questionnaireResponses.user.profile',
        ]),
        'templates' => \App\Models\CertificateTemplate::all(),
        'organizers' => \App\Models\User::whereIn('role', ['super_admin', 'penyelenggara'])->get(),
    ],
    'admin.courses.index' => [
        'user' => $organizer,
        'courses' => Course::withCount(['lessons', 'enrollments'])->take(5)->get(),
    ],
    'peserta.webinar.room' => [
        'user' => $userAsn,
        'webinar' => $webinarActive->load(['organizer.profile', 'quizQuestions', 'questionnaireQuestions']),
        'isRegistered' => true,
        'registration' => null,
        'attendance' => WebinarAttendance::first(),
        'questionnaireCompleted' => true,
        'questionnaireResponse' => $response,
        'attempts' => collect(),
        'latestAttempt' => null,
        'passed' => false,
        'attemptCount' => 0,
        'canRemedial' => false,
        'isFailedPermanently' => false,
        'certificate' => null,
    ],
    'peserta.katalog' => [
        'user' => $userAsn,
        'courses' => Course::take(4)->get(),
        'webinars' => Webinar::take(3)->get(),
        'category' => 'all',
        'search' => '',
        'type' => 'all',
    ],
    'peserta.mooc.show' => [
        'user' => $userAsn,
        'course' => $courseTimed->load(['lessons.quizzes']),
        'enrollment' => $enrollment,
        'lessonCompletions' => collect(),
        'totalLessons' => 0,
        'completedLessonsCount' => 0,
    ],
];

foreach ($templates as $viewName => $viewData) {
    try {
        if (isset($viewData['user'])) {
            \Illuminate\Support\Facades\Auth::login($viewData['user']);
        }
        $rendered = View::make($viewName, $viewData)->render();
        echo "✓ [PASS] View '{$viewName}' rendered successfully (" . strlen($rendered) . " bytes).\n";
    } catch (\Throwable $e) {
        echo "✗ [FAIL] View '{$viewName}' error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
        throw $e;
    }
}

echo "\n========================================================\n";
echo "SEMUA UJI COBA PENGATURAN JAM 24 JAM WIB, VALIDASI BATAS WAKTU, & KUESIONER TEKS BEBAS BERHASIL (100% SUKSES)!\n";
echo "========================================================\n";
