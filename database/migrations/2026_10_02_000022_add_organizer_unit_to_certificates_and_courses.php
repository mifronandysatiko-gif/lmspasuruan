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
        // 1. Tambah organizer_id ke tabel courses
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'organizer_id')) {
                $table->foreignId('organizer_id')->nullable()->after('id')->constrained('users')->onDelete('set null');
            }
        });

        // 2. Tambah organizer_unit_kerja ke tabel certificates
        Schema::table('certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificates', 'organizer_unit_kerja')) {
                $table->string('organizer_unit_kerja', 255)->nullable()->after('training_title');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (Schema::hasColumn('certificates', 'organizer_unit_kerja')) {
                $table->dropColumn('organizer_unit_kerja');
            }
        });

        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'organizer_id')) {
                $table->dropForeign(['organizer_id']);
                $table->dropColumn('organizer_id');
            }
        });
    }
};
