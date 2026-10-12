<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->string('assignment_file')->nullable()->after('final_score');
            $table->text('assignment_notes')->nullable()->after('assignment_file');
            $table->text('instructor_feedback')->nullable()->after('assignment_notes');
            $table->timestamp('assignment_submitted_at')->nullable()->after('instructor_feedback');
        });
    }

    public function down(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropColumn([
                'assignment_file',
                'assignment_notes',
                'instructor_feedback',
                'assignment_submitted_at',
            ]);
        });
    }
};
