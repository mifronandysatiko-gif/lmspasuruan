<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseLessonQuestion;
use App\Models\User;
use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== TEST EDIT DAN HAPUS SESI PEMBELAJARAN (MOOC, BIMTEK, KEPEMIMPINAN) ===\n\n";

$admin = User::where('role', 'super_admin')->first();
Auth::login($admin);
$adminCtrl = new AdminController();

// 1. Buat course uji
$course = Course::create([
    'title' => 'Uji Sesi Pelatihan MOOC BKPSDM',
    'slug' => 'uji-sesi-pelatihan-mooc-bkpsdm-' . time(),
    'category' => 'Kompetensi Digital',
    'type' => 'mooc',
    'jp' => 10,
    'passing_grade' => 80,
    'description' => 'Pelatihan untuk pengujian edit dan hapus sesi.',
    'is_published' => true,
]);

// 2. Buat lesson uji
$lesson = CourseLesson::create([
    'course_id' => $course->id,
    'sequence' => 1,
    'title' => 'Sesi 1: Pengenalan SPBE Dasar',
    'video_url' => 'https://www.youtube.com/watch?v=old123',
    'duration_seconds' => 900, // 15 menit
    'content_summary' => 'Ringkasan lama',
    'has_quiz' => true,
]);

$question = CourseLessonQuestion::create([
    'course_lesson_id' => $lesson->id,
    'question_text' => 'Apakah SPBE wajib?',
    'options' => ['A' => 'Ya', 'B' => 'Tidak'],
    'correct_answer' => 'A',
    'order_number' => 1,
]);

echo "1. Data sesi awal berhasil dibuat (ID: {$lesson->id}, Judul: '{$lesson->title}')\n";

// 3. Test Edit Sesi via Controller
$reqUpdate = Request::create("/admin/courses/{$course->id}/lessons/{$lesson->id}/update", 'POST', [
    'sequence' => 2,
    'title' => 'Sesi 1 (Revisi): Tata Kelola SPBE Lanjutan',
    'duration_minutes' => 30,
    'video_url' => 'https://www.youtube.com/watch?v=new456',
    'content_summary' => 'Ringkasan materi yang telah diperbarui oleh Widyaiswara/Admin.',
    'has_quiz' => 1,
]);

$resUpdate = $adminCtrl->courseLessonUpdate($course->id, $lesson->id, $reqUpdate);
$updatedLesson = $lesson->fresh();

assert($updatedLesson->sequence === 2, 'Gagal update sequence');
assert($updatedLesson->title === 'Sesi 1 (Revisi): Tata Kelola SPBE Lanjutan', 'Gagal update title');
assert($updatedLesson->duration_seconds === 1800, 'Gagal update duration');
assert($updatedLesson->video_url === 'https://www.youtube.com/watch?v=new456', 'Gagal update video_url');
assert($updatedLesson->content_summary === 'Ringkasan materi yang telah diperbarui oleh Widyaiswara/Admin.', 'Gagal update content_summary');

echo "2. [OK] Berhasil mengedit sesi: Judul baru '{$updatedLesson->title}', Durasi: " . ($updatedLesson->duration_seconds / 60) . " Menit\n";

// 4. Test Hapus Sesi via Controller
$reqDelete = Request::create("/admin/courses/{$course->id}/lessons/{$lesson->id}/delete", 'POST');
$resDelete = $adminCtrl->courseLessonDestroy($course->id, $lesson->id, $reqDelete);

assert(CourseLesson::find($lesson->id) === null, 'Gagal menghapus lesson');
assert(CourseLessonQuestion::find($question->id) === null, 'Gagal menghapus relasi question');

echo "3. [OK] Berhasil menghapus sesi dan soal terkait dari database!\n";

// 5. Cleanup
$course->delete();
echo "\n=== SEMUA TEST EDIT & HAPUS SESI PELATIHAN SUKSES (100% PASS) ===\n";
