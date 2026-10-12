<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Webinar;
use App\Models\WebinarQuestion;
use App\Models\WebinarQuestionnaireResponse;
use App\Models\WebinarExamAttempt;
use Illuminate\Http\Request;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PesertaController;
use Illuminate\Support\Facades\Auth;

echo "=== STARTING VERIFICATION: WEBINAR QUESTIONS & SCORING ===\n\n";

// 1. Get Test Users & Webinar
$admin = User::where('role', 'super_admin')->first();
$penyelenggara = User::where('role', 'penyelenggara')->first();
$peserta = User::where('role', 'peserta')->first();
$webinar = Webinar::first();

if (!$webinar) {
    echo "FAILED: No webinar found in database.\n";
    exit(1);
}

echo "Testing with:\n";
echo "- Admin: {$admin->name} ({$admin->role})\n";
echo "- Penyelenggara: {$penyelenggara->name} ({$penyelenggara->role})\n";
echo "- Peserta: {$peserta->name}\n";
echo "- Webinar: #{$webinar->id} {$webinar->title}\n\n";

// 2. Test Admin/Penyelenggara Store Quiz Question
Auth::login($penyelenggara);
$adminCtrl = new AdminController();

$reqStoreQuiz = Request::create("/admin/webinars/{$webinar->id}/questions", 'POST', [
    'type' => 'quiz',
    'question_text' => 'Berapa lapis arsitektur keamanan data pada portal LMS Pemerintah Kabupaten Pasuruan?',
    'options' => [
        'A' => '1 Lapis',
        'B' => '2 Lapis',
        'C' => '3 Lapis (Multi-Tier Security)',
        'D' => 'Tidak Ada',
    ],
    'correct_answer' => 'C',
    'order_number' => 99,
]);

$resp = $adminCtrl->webinarStoreQuestion($webinar->id, $reqStoreQuiz);
echo "1. Penyelenggara Store Quiz Question: ";
$createdQuiz = WebinarQuestion::where('webinar_id', $webinar->id)->where('order_number', 99)->first();
if ($createdQuiz && $createdQuiz->correct_answer === 'C' && $createdQuiz->options['C'] === '3 Lapis (Multi-Tier Security)') {
    echo "SUCCESS (ID: {$createdQuiz->id})\n";
} else {
    echo "FAILED\n";
    exit(1);
}

// 3. Test Admin/Penyelenggara Update Question
$reqUpdateQuiz = Request::create("/admin/webinars/{$webinar->id}/questions/{$createdQuiz->id}", 'POST', [
    'question_text' => 'Berapa lapis arsitektur keamanan data pada portal LMS Pemerintah Kabupaten Pasuruan? (Diperbarui)',
    'options' => [
        'A' => '1 Lapis Saja',
        'B' => '2 Lapis Enkripsi',
        'C' => '3 Lapis Terpadu (WAF, TLS, & DB Vault)',
        'D' => 'Tanpa Pengamanan',
    ],
    'correct_answer' => 'C',
    'order_number' => 99,
]);

$adminCtrl->webinarUpdateQuestion($webinar->id, $createdQuiz->id, $reqUpdateQuiz);
$createdQuiz->refresh();
echo "2. Penyelenggara Update Quiz Question: ";
if ($createdQuiz->options['C'] === '3 Lapis Terpadu (WAF, TLS, & DB Vault)') {
    echo "SUCCESS\n";
} else {
    echo "FAILED\n";
    exit(1);
}

// 4. Test Store Questionnaire Question
$reqStoreKuesioner = Request::create("/admin/webinars/{$webinar->id}/questions", 'POST', [
    'type' => 'questionnaire',
    'question_text' => 'Bagaimana tanggapan Anda terhadap durasi bimbingan teknis ini?',
    'questionnaire_options' => 'Sangat Pas, Cukup Pas, Terlalu Singkat, Terlalu Panjang',
    'order_number' => 98,
]);

$adminCtrl->webinarStoreQuestion($webinar->id, $reqStoreKuesioner);
$createdKuesioner = WebinarQuestion::where('webinar_id', $webinar->id)->where('order_number', 98)->first();
echo "3. Store Questionnaire Question: ";
if ($createdKuesioner && count($createdKuesioner->options) === 4 && $createdKuesioner->options[0] === 'Sangat Pas') {
    echo "SUCCESS (ID: {$createdKuesioner->id})\n";
} else {
    echo "FAILED\n";
    exit(1);
}

// Clean up temporary test questions so we test with standard questions
$adminCtrl->webinarDeleteQuestion($webinar->id, $createdQuiz->id, Request::create('', 'POST'));
$adminCtrl->webinarDeleteQuestion($webinar->id, $createdKuesioner->id, Request::create('', 'POST'));
echo "4. Delete Temporary Test Questions: SUCCESS\n\n";

// 5. Test Peserta Questionnaire Submit
Auth::login($peserta);
$pesertaCtrl = new PesertaController();

$quizQuestions = WebinarQuestion::where('webinar_id', $webinar->id)->where('type', 'quiz')->orderBy('order_number')->get();
$questionnaireQuestions = WebinarQuestion::where('webinar_id', $webinar->id)->where('type', 'questionnaire')->orderBy('order_number')->get();

echo "Current active bank questions for Webinar #{$webinar->id}:\n";
echo "- Quiz Questions count: {$quizQuestions->count()}\n";
echo "- Questionnaire Questions count: {$questionnaireQuestions->count()}\n\n";

$kAnswers = [];
foreach ($questionnaireQuestions as $kq) {
    $kAnswers[$kq->id] = $kq->options[0] ?? 'Sangat Baik';
}

$reqKuesioner = Request::create("/peserta/webinar/{$webinar->id}/questionnaire", 'POST', [
    'answers' => $kAnswers,
]);

$pesertaCtrl->webinarQuestionnaire($webinar->id, $reqKuesioner);
$kResp = WebinarQuestionnaireResponse::where('webinar_id', $webinar->id)->where('user_id', $peserta->id)->first();
echo "5. Peserta Submit Questionnaire: ";
if ($kResp && count($kResp->answers) === $questionnaireQuestions->count()) {
    echo "SUCCESS (Saved in DB)\n";
} else {
    echo "FAILED\n";
    exit(1);
}

// 6. Test Peserta Exam Submit with Dynamic Scoring
// Test A: 100% Correct Answers
$perfectAnswers = [];
foreach ($quizQuestions as $q) {
    $perfectAnswers[$q->id] = $q->correct_answer;
}

// Reset attempts for clean test
WebinarExamAttempt::where('webinar_id', $webinar->id)->where('user_id', $peserta->id)->delete();

$reqExam1 = Request::create("/peserta/webinar/{$webinar->id}/exam-submit", 'POST', [
    'answers' => $perfectAnswers,
]);

$pesertaCtrl->webinarExamSubmit($webinar->id, $reqExam1);
$att1 = WebinarExamAttempt::where('webinar_id', $webinar->id)->where('user_id', $peserta->id)->where('attempt_number', 1)->first();

echo "6. Peserta Exam Attempt 1 (All Correct): ";
if ($att1 && (float)$att1->raw_score === 100.00 && (float)$att1->final_score === 100.00 && $att1->is_passed) {
    echo "SUCCESS (Raw: {$att1->raw_score}, Final: {$att1->final_score}, Passed: YES)\n";
} else {
    echo "FAILED\n";
    var_dump($att1);
    exit(1);
}

// Test B: Attempt 2 (Remedial with Capped Score)
// Reset and do Attempt 1 fail, Attempt 2 high score to verify capping
WebinarExamAttempt::where('webinar_id', $webinar->id)->where('user_id', $peserta->id)->delete();

// Attempt 1: 0 correct
$failAnswers = [];
foreach ($quizQuestions as $q) {
    $failAnswers[$q->id] = 'WRONG';
}
$pesertaCtrl->webinarExamSubmit($webinar->id, Request::create('', 'POST', ['answers' => $failAnswers]));
$attFail = WebinarExamAttempt::where('webinar_id', $webinar->id)->where('user_id', $peserta->id)->where('attempt_number', 1)->first();

echo "7. Peserta Exam Attempt 1 (All Wrong): ";
if ($attFail && (float)$attFail->raw_score === 0.00 && !$attFail->is_passed) {
    echo "SUCCESS (Raw: 0.00, Passed: NO)\n";
} else {
    echo "FAILED\n";
    exit(1);
}

// Attempt 2: 100% correct -> should be capped at remedial_cap_score (90)
$pesertaCtrl->webinarExamSubmit($webinar->id, Request::create('', 'POST', ['answers' => $perfectAnswers]));
$attRemedial = WebinarExamAttempt::where('webinar_id', $webinar->id)->where('user_id', $peserta->id)->where('attempt_number', 2)->first();

echo "8. Peserta Exam Attempt 2 (Remedial Capped Score): ";
$expectedCap = (float)$webinar->remedial_cap_score;
if ($attRemedial && (float)$attRemedial->raw_score === 100.00 && (float)$attRemedial->final_score === $expectedCap && $attRemedial->is_passed) {
    echo "SUCCESS (Raw: 100.00, Final Capped: {$attRemedial->final_score}, Passed: YES)\n";
} else {
    echo "FAILED: Raw={$attRemedial->raw_score}, Final={$attRemedial->final_score}, Expected={$expectedCap}\n";
    exit(1);
}

echo "\n=== ALL VERIFICATION CHECKS PASSED SUCCESSFULLY! ===\n";
