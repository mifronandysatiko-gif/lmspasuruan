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
        // 1. Tambahkan kolom lampiran template dan nomor awal di certificate_templates
        Schema::table('certificate_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificate_templates', 'lampiran_background_path')) {
                $table->string('lampiran_background_path')->nullable()->after('background_path');
            }
            if (!Schema::hasColumn('certificate_templates', 'start_number')) {
                $table->unsignedInteger('start_number')->default(1)->after('number_format');
            }
        });

        // 2. Tambahkan kolom nomor awal, format custom, dan blanko lampiran khusus di courses
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'certificate_start_number')) {
                $table->unsignedInteger('certificate_start_number')->nullable()->after('certificate_number');
            }
            if (!Schema::hasColumn('courses', 'certificate_format')) {
                $table->string('certificate_format')->nullable()->after('certificate_start_number');
            }
            if (!Schema::hasColumn('courses', 'certificate_lampiran_path')) {
                $table->string('certificate_lampiran_path')->nullable()->after('certificate_format');
            }
        });

        // 3. Tambahkan kolom nomor awal dan format custom di webinars
        Schema::table('webinars', function (Blueprint $table) {
            if (!Schema::hasColumn('webinars', 'certificate_start_number')) {
                $table->unsignedInteger('certificate_start_number')->nullable()->after('certificate_number');
            }
            if (!Schema::hasColumn('webinars', 'certificate_format')) {
                $table->string('certificate_format')->nullable()->after('certificate_start_number');
            }
        });

        // 4. Tambahkan kolom JP per sesi di course_lessons
        Schema::table('course_lessons', function (Blueprint $table) {
            if (!Schema::hasColumn('course_lessons', 'jp')) {
                $table->unsignedInteger('jp')->nullable()->after('duration_seconds');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->dropColumn(['lampiran_background_path', 'start_number']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['certificate_start_number', 'certificate_format', 'certificate_lampiran_path']);
        });

        Schema::table('webinars', function (Blueprint $table) {
            $table->dropColumn(['certificate_start_number', 'certificate_format']);
        });

        Schema::table('course_lessons', function (Blueprint $table) {
            $table->dropColumn(['jp']);
        });
    }
};
