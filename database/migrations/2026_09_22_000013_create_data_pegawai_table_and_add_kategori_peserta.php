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
        // 1. Buat tabel master data_pegawai untuk verifikasi ASN Pasuruan
        if (!Schema::hasTable('data_pegawai')) {
            Schema::create('data_pegawai', function (Blueprint $table) {
                $table->id();
                $table->string('nip', 30)->unique();
                $table->string('nama', 255);
                $table->string('gelar_depan', 50)->nullable();
                $table->string('gelar_belakang', 50)->nullable();
                $table->string('jabatan', 255)->nullable();
                $table->string('unit_kerja', 255)->nullable(); // OPD Pemkab Pasuruan
                $table->string('pangkat_golongan', 100)->nullable();
                $table->string('email', 255)->nullable();
                $table->string('no_hp', 30)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('nip');
                $table->index('nama');
                $table->index('unit_kerja');
            });
        }

        // 2. Tambah kolom kategori_peserta di tabel user_profiles
        if (Schema::hasTable('user_profiles') && !Schema::hasColumn('user_profiles', 'kategori_peserta')) {
            Schema::table('user_profiles', function (Blueprint $table) {
                $table->string('kategori_peserta', 30)->default('umum')->after('unit_kerja');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('user_profiles') && Schema::hasColumn('user_profiles', 'kategori_peserta')) {
            Schema::table('user_profiles', function (Blueprint $table) {
                $table->dropColumn('kategori_peserta');
            });
        }

        Schema::dropIfExists('data_pegawai');
    }
};
