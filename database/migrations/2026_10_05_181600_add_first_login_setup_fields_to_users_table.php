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
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'first_login_completed')) {
                    $table->boolean('first_login_completed')->default(false)->after('is_active');
                }
                if (!Schema::hasColumn('users', 'first_login_profile_updated')) {
                    $table->boolean('first_login_profile_updated')->default(false)->after('first_login_completed');
                }
                if (!Schema::hasColumn('users', 'first_login_password_changed')) {
                    $table->boolean('first_login_password_changed')->default(false)->after('first_login_profile_updated');
                }
            });

            // Tandai pengguna admin/staff pengelola yang sudah ada sebagai sudah selesai
            DB::table('users')->where('role', '!=', 'peserta')->update([
                'first_login_completed' => true,
                'first_login_profile_updated' => true,
                'first_login_password_changed' => true,
            ]);

            // Untuk peserta eksisting yang sudah ada di database saat ini, set true agar tidak mengganggu data berjalan
            DB::table('users')->where('role', 'peserta')->update([
                'first_login_completed' => true,
                'first_login_profile_updated' => true,
                'first_login_password_changed' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'first_login_completed')) {
                    $table->dropColumn(['first_login_completed', 'first_login_profile_updated', 'first_login_password_changed']);
                }
            });
        }
    }
};
