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
            $table->string('time_limit_type', 30)->default('none')->after('passing_grade');
            $table->unsignedInteger('duration_days')->nullable()->after('time_limit_type');
            $table->dateTime('start_date')->nullable()->after('duration_days');
            $table->dateTime('end_date')->nullable()->after('start_date');
        });

        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dateTime('enrolled_at')->nullable()->after('user_id');
            $table->dateTime('deadline_at')->nullable()->after('enrolled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropColumn(['enrolled_at', 'deadline_at']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['time_limit_type', 'duration_days', 'start_date', 'end_date']);
        });
    }
};
