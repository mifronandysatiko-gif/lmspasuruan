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
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'max_remedial')) {
                $table->integer('max_remedial')->default(5)->after('passing_grade');
            }
            if (!Schema::hasColumn('courses', 'remedial_cap_score')) {
                $table->integer('remedial_cap_score')->default(90)->after('max_remedial');
            }
        });

        Schema::table('course_lesson_completions', function (Blueprint $table) {
            if (!Schema::hasColumn('course_lesson_completions', 'raw_score')) {
                $table->decimal('raw_score', 5, 2)->nullable()->after('is_pdf_read');
            }
            if (!Schema::hasColumn('course_lesson_completions', 'attempt_count')) {
                $table->integer('attempt_count')->default(1)->after('quiz_score');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'remedial_cap_score')) {
                $table->dropColumn('remedial_cap_score');
            }
            if (Schema::hasColumn('courses', 'max_remedial')) {
                $table->dropColumn('max_remedial');
            }
        });

        Schema::table('course_lesson_completions', function (Blueprint $table) {
            if (Schema::hasColumn('course_lesson_completions', 'attempt_count')) {
                $table->dropColumn('attempt_count');
            }
            if (Schema::hasColumn('course_lesson_completions', 'raw_score')) {
                $table->dropColumn('raw_score');
            }
        });
    }
};
