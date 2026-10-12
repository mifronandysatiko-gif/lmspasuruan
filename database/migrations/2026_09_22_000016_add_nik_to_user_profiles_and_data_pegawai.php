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
        // 1. Tambahkan kolom NIK pada user_profiles jika belum ada
        if (Schema::hasTable('user_profiles') && !Schema::hasColumn('user_profiles', 'nik')) {
            Schema::table('user_profiles', function (Blueprint $table) {
                $table->string('nik', 30)->nullable()->after('user_id');
                $table->index('nik');
            });
        }

        // 2. Tambahkan kolom NIK pada data_pegawai jika belum ada
        if (Schema::hasTable('data_pegawai') && !Schema::hasColumn('data_pegawai', 'nik')) {
            Schema::table('data_pegawai', function (Blueprint $table) {
                $table->string('nik', 30)->nullable()->after('nip');
                $table->index('nik');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('user_profiles') && Schema::hasColumn('user_profiles', 'nik')) {
            Schema::table('user_profiles', function (Blueprint $table) {
                $table->dropIndex(['nik']);
                $table->dropColumn('nik');
            });
        }

        if (Schema::hasTable('data_pegawai') && Schema::hasColumn('data_pegawai', 'nik')) {
            Schema::table('data_pegawai', function (Blueprint $table) {
                $table->dropIndex(['nik']);
                $table->dropColumn('nik');
            });
        }
    }
};
