<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('webinar_questions') && !Schema::hasColumn('webinar_questions', 'input_type')) {
            Schema::table('webinar_questions', function (Blueprint $table) {
                $table->string('input_type', 30)->default('options')->after('type'); // 'options' (pilihan ganda/skala) atau 'text' (isian ketikan bebas/esai)
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('webinar_questions') && Schema::hasColumn('webinar_questions', 'input_type')) {
            Schema::table('webinar_questions', function (Blueprint $table) {
                $table->dropColumn('input_type');
            });
        }
    }
};
