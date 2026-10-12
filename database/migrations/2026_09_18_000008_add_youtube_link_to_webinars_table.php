<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('webinars') && !Schema::hasColumn('webinars', 'youtube_link')) {
            Schema::table('webinars', function (Blueprint $table) {
                $table->string('youtube_link', 500)->nullable()->after('meeting_link');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('webinars') && Schema::hasColumn('webinars', 'youtube_link')) {
            Schema::table('webinars', function (Blueprint $table) {
                $table->dropColumn('youtube_link');
            });
        }
    }
};
