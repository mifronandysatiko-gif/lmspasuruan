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
        if (Schema::hasTable('webinars') && !Schema::hasColumn('webinars', 'share_message')) {
            Schema::table('webinars', function (Blueprint $table) {
                $table->text('share_message')->nullable()->after('description');
            });
        }

        if (Schema::hasTable('courses') && !Schema::hasColumn('courses', 'share_message')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->text('share_message')->nullable()->after('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('webinars') && Schema::hasColumn('webinars', 'share_message')) {
            Schema::table('webinars', function (Blueprint $table) {
                $table->dropColumn('share_message');
            });
        }

        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'share_message')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('share_message');
            });
        }
    }
};
