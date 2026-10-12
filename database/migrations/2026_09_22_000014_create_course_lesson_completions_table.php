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
        if (!Schema::hasTable('course_lesson_completions')) {
            Schema::create('course_lesson_completions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
                $table->foreignId('course_lesson_id')->constrained('course_lessons')->onDelete('cascade');
                $table->boolean('is_video_completed')->default(false);
                $table->boolean('is_pdf_read')->default(false);
                $table->decimal('quiz_score', 5, 2)->nullable();
                $table->boolean('is_passed')->default(false);
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'course_lesson_id']);
                $table->index(['user_id', 'course_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_lesson_completions');
    }
};
