<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Soal Kuis Evaluasi dan Pertanyaan Kuesioner Webinar
        Schema::create('webinar_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webinar_id')->constrained('webinars')->onDelete('cascade');
            $table->string('type', 30)->default('quiz'); // 'quiz' (evaluasi pemahaman) atau 'questionnaire' (kuesioner kepuasan)
            $table->text('question_text');
            $table->json('options')->nullable(); // Opsi pilihan jawaban ['A' => '...', 'B' => '...'] atau array pilihan respon
            $table->string('correct_answer', 10)->nullable(); // Kunci jawaban untuk kuis ('A', 'B', 'C', 'D', dst), null untuk kuesioner
            $table->integer('order_number')->default(1);
            $table->timestamps();
        });

        // 2. Tabel Respons Kuesioner Kepuasan Peserta
        Schema::create('webinar_questionnaire_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webinar_id')->constrained('webinars')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->json('answers'); // Menyimpan respon peserta terhadap pertanyaan kuesioner
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();

            $table->unique(['webinar_id', 'user_id']);
        });

        // 3. Tambahkan kolom answers pada tabel webinar_exam_attempts jika belum ada
        if (Schema::hasTable('webinar_exam_attempts') && !Schema::hasColumn('webinar_exam_attempts', 'answers')) {
            Schema::table('webinar_exam_attempts', function (Blueprint $table) {
                $table->json('answers')->nullable()->after('final_score');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('webinar_exam_attempts') && Schema::hasColumn('webinar_exam_attempts', 'answers')) {
            Schema::table('webinar_exam_attempts', function (Blueprint $table) {
                $table->dropColumn('answers');
            });
        }

        Schema::dropIfExists('webinar_questionnaire_responses');
        Schema::dropIfExists('webinar_questions');
    }
};
