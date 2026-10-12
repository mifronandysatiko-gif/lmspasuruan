<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('pangkat_golongan', 50)->nullable()->after('jabatan');
            $table->string('sync_source', 30)->default('local')->after('phone_number'); // 'local' or 'api_simpeg'
            $table->timestamp('last_synced_at')->nullable()->after('sync_source');
        });

        // Seed default API settings into system_settings if not exists
        $defaultApiSettings = [
            [
                'setting_key' => 'simpeg_api_enabled',
                'setting_value' => 'true',
                'setting_group' => 'general',
                'field_type' => 'boolean',
                'label' => 'Status Integrasi API PINTAR / Eksternal',
                'description' => 'Aktifkan sinkronisasi otomatis data pegawai ASN dari API SIMPEG Kabupaten Pasuruan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'setting_key' => 'simpeg_api_url',
                'setting_value' => 'http://127.0.0.1:8000/api/external-simpeg/mock-pegawai',
                'setting_group' => 'general',
                'field_type' => 'text',
                'label' => 'URL Endpoint API Data Pegawai',
                'description' => 'Tautan endpoint API JSON eksternal penyedia data ASN (SIMPEG / SIASN / SSO)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'setting_key' => 'simpeg_api_auth_type',
                'setting_value' => 'bearer',
                'setting_group' => 'general',
                'field_type' => 'text',
                'label' => 'Tipe Otentikasi API',
                'description' => 'Metode keamanan API (bearer, apikey, basic, none)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'setting_key' => 'simpeg_api_token',
                'setting_value' => 'PASURUAN-SIMPEG-SECURE-KEY-2026',
                'setting_group' => 'general',
                'field_type' => 'text',
                'label' => 'Token Rahasia / API Key',
                'description' => 'Kunci otorisasi Bearer Token atau API Secret',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'setting_key' => 'simpeg_api_last_synced',
                'setting_value' => null,
                'setting_group' => 'general',
                'field_type' => 'text',
                'label' => 'Waktu Sinkronisasi Terakhir',
                'description' => 'Waktu terakhir data ditarik dari API eksternal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($defaultApiSettings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['setting_key' => $setting['setting_key']],
                $setting
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['pangkat_golongan', 'sync_source', 'last_synced_at']);
        });

        DB::table('system_settings')->whereIn('setting_key', [
            'simpeg_api_enabled',
            'simpeg_api_url',
            'simpeg_api_auth_type',
            'simpeg_api_token',
            'simpeg_api_last_synced',
        ])->delete();
    }
};
