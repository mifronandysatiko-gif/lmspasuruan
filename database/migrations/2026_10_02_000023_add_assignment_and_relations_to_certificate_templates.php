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
        // 1. Tambah assignment columns ke certificate_templates
        Schema::table('certificate_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificate_templates', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('name');
            }
            if (!Schema::hasColumn('certificate_templates', 'webinar_id')) {
                $table->foreignId('webinar_id')->nullable()->after('is_default')->constrained('webinars')->nullOnDelete();
            }
            if (!Schema::hasColumn('certificate_templates', 'course_id')) {
                $table->foreignId('course_id')->nullable()->after('webinar_id')->constrained('courses')->nullOnDelete();
            }
        });

        // 2. Tambah foreign key certificate_template_id ke webinars
        Schema::table('webinars', function (Blueprint $table) {
            if (!Schema::hasColumn('webinars', 'certificate_template_id')) {
                $table->foreignId('certificate_template_id')->nullable()->after('organizer_id')->constrained('certificate_templates')->nullOnDelete();
            }
        });

        // 3. Tambah foreign key certificate_template_id ke courses
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'certificate_template_id')) {
                $table->foreignId('certificate_template_id')->nullable()->after('organizer_id')->constrained('certificate_templates')->nullOnDelete();
            }
        });

        // 4. Set template pertama sebagai is_default = true jika ada
        DB::table('certificate_templates')->orderBy('id')->limit(1)->update(['is_default' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'certificate_template_id')) {
                $table->dropConstrainedForeignId('certificate_template_id');
            }
        });

        Schema::table('webinars', function (Blueprint $table) {
            if (Schema::hasColumn('webinars', 'certificate_template_id')) {
                $table->dropConstrainedForeignId('certificate_template_id');
            }
        });

        Schema::table('certificate_templates', function (Blueprint $table) {
            if (Schema::hasColumn('certificate_templates', 'course_id')) {
                $table->dropConstrainedForeignId('course_id');
            }
            if (Schema::hasColumn('certificate_templates', 'webinar_id')) {
                $table->dropConstrainedForeignId('webinar_id');
            }
            if (Schema::hasColumn('certificate_templates', 'is_default')) {
                $table->dropColumn('is_default');
            }
        });
    }
};
