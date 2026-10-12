<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('course_lesson_questions')) {
            Schema::create('course_lesson_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_lesson_id')->constrained('course_lessons')->onDelete('cascade');
                $table->text('question_text');
                $table->json('options');
                $table->string('correct_answer', 10)->default('A');
                $table->integer('order_number')->default(1);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_lesson_questions');
    }
};
