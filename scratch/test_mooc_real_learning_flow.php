<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CourseLessonCompletion;
use App\Models\CourseLessonQuestion;
use App\Models\User;
use App\Http\Controllers\PesertaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== TEST ALUR PEMBELAJARAN MOOC REAL (VIDEO, DOKUMEN, KUIS, PROGRESS PERSISTEN) ===\n\n";

// 1. Test Video Parser Embed Helper
echo "1. Menguji Video URL Parser di CourseLesson...\n";
$lessonTest = new CourseLesson([
    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
]);
assert($lessonTest->youtube_id === 'dQw4w9WgXcQ', 'Gagal ekstrak YouTube ID');
assert(str_contains($lessonTest->embed_video_url, 'https://www.youtube.com/embed/dQw4w9WgXcQ'), 'Gagal konversi embed YouTube watch');

$lessonTest2 = new CourseLesson([
    'video_url' => 'https://youtu.be/abc123XYZ00'
]);
assert($lessonTest2->youtube_id === 'abc123XYZ00', 'Gagal ekstrak youtu.be ID');
assert(str_contains($lessonTest2->embed_video_url, 'https://www.youtube.com/embed/abc123XYZ00'), 'Gagal konversi embed youtu.be');

$lessonTest3 = new CourseLesson([
    'video_url' => 'https://example.com/videos/materi_spbe.mp4'
]);
assert($lessonTest3->is_direct_video === true, 'Gagal deteksi file mp4');

echo "   [OK] Seluruh format video YouTube & MP4 berhasil diparsing menjadi URL embed valid!\n\n";

// 2. Setup Participant & Multi-Lesson Course
$peserta = User::where('role', 'peserta')->first();
Auth::login($peserta);
$pesertaCtrl = new PesertaController();

// Bersihkan data uji lama jika ada
Course::where('slug', 'like', 'uji-real-mooc-%')->delete();

$course = Course::create([
    'title' => 'Pelatihan Implementasi SPBE Pemkab Pasuruan (Real Test)',
    'slug' => 'uji-real-mooc-' . time(),
    'category' => 'Teknologi Informasi',
    'type' => 'mooc',
    'jp' => 12,
    'passing_grade' => 80,
    'description' => 'Pelatihan SPBE untuk pengujian alur nyata.',
    'is_published' => true,
]);

// Buat Sesi 1
$lesson1 = CourseLesson::create([
    'course_id' => $course->id,
    'sequence' => 1,
    'title' => 'Bab 1: Arsitektur SPBE Pasuruan',
    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'duration_seconds' => 900,
    'pdf_path' => 'materials/modul_spbe_1.pdf',
    'content_summary' => 'Dasar regulasi dan arsitektur SPBE.',
    'has_quiz' => true,
]);

$q1 = CourseLessonQuestion::create([
    'course_lesson_id' => $lesson1->id,
    'question_text' => 'Berapa pilar utama SPBE?',
    'options' => ['A' => '4 Pilar', 'B' => '2 Pilar', 'C' => '1 Pilar'],
    'correct_answer' => 'A',
    'order_number' => 1,
]);

// Buat Sesi 2
$lesson2 = CourseLesson::create([
    'course_id' => $course->id,
    'sequence' => 2,
    'title' => 'Bab 2: Manajemen Keamanan Siber & CSIRT',
    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'duration_seconds' => 1200,
    'pdf_path' => 'materials/modul_spbe_2.pdf',
    'content_summary' => 'Penanganan insiden keamanan siber daerah.',
    'has_quiz' => true,
]);

$q2 = CourseLessonQuestion::create([
    'course_lesson_id' => $lesson2->id,
    'question_text' => 'Apa kepanjangan dari CSIRT?',
    'options' => ['A' => 'Computer Security Incident Response Team', 'B' => 'Cyber Security Information Real Time'],
    'correct_answer' => 'A',
    'order_number' => 1,
]);

echo "2. Kursus & 2 Sesi Pembelajaran berhasil disiapkan.\n\n";

// 3. Uji moocShow (Pendaftaran Awal)
echo "3. Menguji moocShow saat pertama kali masuk...\n";
$reqView = Request::create("/peserta/mooc/{$course->slug}", 'GET');
$viewResult = $pesertaCtrl->moocShow($course->slug);
assert($viewResult->getName() === 'peserta.mooc.show', 'Gagal render view mooc.show');

$enrollment = CourseEnrollment::where('course_id', $course->id)->where('user_id', $peserta->id)->first();
assert($enrollment !== null, 'Enrollment harus terbentuk');
echo "   [OK] Enrollment aktif dengan progres: {$enrollment->progress_percentage}%\n\n";

// 4. Uji Selesaikan Video & Baca Dokumen Sesi 1 via API
echo "4. Menandai Video dan Dokumen Sesi 1 sebagai Selesai...\n";
$reqComp1 = Request::create("/peserta/mooc/{$course->slug}/complete-lesson/{$lesson1->id}", 'POST', [
    'type' => 'all'
]);
$pesertaCtrl->moocCompleteLesson($course->slug, $lesson1->id, $reqComp1);

$comp1 = CourseLessonCompletion::where('user_id', $peserta->id)->where('course_lesson_id', $lesson1->id)->first();
assert($comp1 !== null, 'Completion sesi 1 harus tersimpan di DB');
assert($comp1->is_video_completed === true, 'Video sesi 1 harus true');
assert($comp1->is_pdf_read === true, 'PDF sesi 1 harus true');
echo "   [OK] Status sesi 1 tersimpan di database: Video Completed & PDF Read.\n\n";

// 5. Uji Submit Kuis Sesi 1 dengan Jawaban Benar
echo "5. Mengirim Jawaban Kuis Sesi 1...\n";
$reqQuiz1 = Request::create("/peserta/mooc/{$course->slug}/quiz", 'POST', [
    'lesson_id' => $lesson1->id,
    'answers' => [
        $q1->id => 'A', // Jawaban benar
    ],
]);
$pesertaCtrl->moocSubmitQuiz($course->slug, $reqQuiz1);

$comp1Fresh = $comp1->fresh();
assert($comp1Fresh->is_passed === true, 'Sesi 1 harus dinyatakan LULUS (is_passed = true)');
assert($comp1Fresh->quiz_score == 100, 'Nilai kuis harus 100');

$enrollmentFresh = $enrollment->fresh();
assert($enrollmentFresh->progress_percentage == 50, 'Progres 1 dari 2 sesi harus 50%');
echo "   [OK] Kuis Sesi 1 Lulus! Nilai: {$comp1Fresh->quiz_score}, Progres Kursus: {$enrollmentFresh->progress_percentage}%\n\n";

// 6. Uji Persistence: Buka Kembali Halaman moocShow
echo "6. Menguji Persistence Data saat Halaman Dibuka Kembali (Reload)...\n";
$viewReload = $pesertaCtrl->moocShow($course->slug);
$completionsLoaded = $viewReload->getData()['lessonCompletions'];
assert(isset($completionsLoaded[$lesson1->id]), 'Data penyelesaian sesi 1 harus termuat');
assert($completionsLoaded[$lesson1->id]->is_passed === true, 'Status lulus sesi 1 harus tetap terjaga');
echo "   [OK] Data sesi 1 tetap LULUS dan TIDAK RESET saat halaman dimuat ulang!\n\n";

// 7. Selesaikan Sesi 2 & Kuis Sesi 2 (Kelulusan Penuh)
echo "7. Menyelesaikan Sesi 2 dan Kuis Sesi 2...\n";
$reqQuiz2 = Request::create("/peserta/mooc/{$course->slug}/quiz", 'POST', [
    'lesson_id' => $lesson2->id,
    'answers' => [
        $q2->id => 'A', // Jawaban benar
    ],
]);
$pesertaCtrl->moocSubmitQuiz($course->slug, $reqQuiz2);

$enrollmentFinal = $enrollment->fresh();
assert($enrollmentFinal->progress_percentage == 100, 'Progres harus 100% setelah semua sesi selesai');
assert($enrollmentFinal->status === 'passed', 'Status kepesertaan harus PASSED');
assert($enrollmentFinal->completed_at !== null, 'completed_at harus terisi');

echo "   [OK] Seluruh sesi tuntas! Progres: 100%, Status: {$enrollmentFinal->status}\n\n";

// 8. Cleanup
$course->delete();
echo "=== SEMUA 8 TEST SISTEM PEMBELAJARAN MOOC REAL SUKSES (100% PASS) ===\n";
