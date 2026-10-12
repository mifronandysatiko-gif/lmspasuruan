<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'users',
            'user_profiles',
            'data_pegawai',
            'webinar_attendances',
            'webinar_registrations',
            'webinar_questionnaire_responses',
            'webinar_exam_attempts',
            'course_enrollments',
            'course_lesson_completions',
            'leadership_logbooks',
            'certificates',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'nik')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->string('nik', 30)->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        // no-op
    }
};
