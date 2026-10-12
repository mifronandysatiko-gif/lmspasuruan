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
        // 1. Tambah certificate_number ke tabel courses
        Schema::table('courses', function (Blueprint $table) {
            $table->string('certificate_number', 150)->nullable()->after('category');
        });

        // 2. Tambah certificate_number ke tabel webinars
        Schema::table('webinars', function (Blueprint $table) {
            $table->string('certificate_number', 150)->nullable()->after('description');
        });

        // 3. Tambah course_id ke tabel certificates dan lepas unique index pada certificate_number
        Schema::table('certificates', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->after('webinar_id')->constrained('courses')->onDelete('set null');
            $table->dropUnique('certificates_certificate_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropColumn('course_id');
            $table->unique('certificate_number', 'certificates_certificate_number_unique');
        });

        Schema::table('webinars', function (Blueprint $table) {
            $table->dropColumn('certificate_number');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('certificate_number');
        });
    }
};
