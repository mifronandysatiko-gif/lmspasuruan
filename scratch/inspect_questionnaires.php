<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Webinar;
use App\Models\WebinarQuestion;
use App\Models\WebinarQuestionnaireResponse;
use App\Models\Course;

echo "Webinars: " . Webinar::count() . "\n";
echo "Webinar Questions: " . WebinarQuestion::count() . "\n";
echo "Webinar Questions (type=questionnaire): " . WebinarQuestion::where('type', 'questionnaire')->count() . "\n";
echo "Webinar Questionnaire Responses: " . WebinarQuestionnaireResponse::count() . "\n";

foreach (WebinarQuestion::where('type', 'questionnaire')->get() as $q) {
    echo "Q ID: {$q->id} | Webinar: {$q->webinar_id} | InputType: {$q->input_type} | Text: {$q->question_text} | Options: " . json_encode($q->options) . "\n";
}

foreach (WebinarQuestionnaireResponse::take(5)->get() as $r) {
    echo "Resp ID: {$r->id} | User: {$r->nama_lengkap} ({$r->nip}) | Submitted: {$r->submitted_at} | Answers: " . json_encode($r->answers) . "\n";
}
