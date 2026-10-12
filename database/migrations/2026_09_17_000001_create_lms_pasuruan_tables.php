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
        // 1. Profil Lengkap Peserta
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('full_name');
            $table->string('gelar_depan', 50)->nullable();
            $table->string('gelar_belakang', 50)->nullable();
            $table->string('jabatan')->nullable();
            $table->string('unit_kerja');
            $table->string('photo_path')->nullable();
            $table->string('phone_number', 30)->nullable();
            $table->timestamps();
        });

        // 2. Pengaturan Dinamis Sistem (No-Code Admin Settings)
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->longText('setting_value')->nullable();
            $table->enum('setting_group', ['branding', 'general', 'evaluation', 'contact'])->default('general');
            $table->enum('field_type', ['text', 'textarea', 'image', 'number', 'boolean'])->default('text');
            $table->string('label')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // 3. Modul Webinar & Bimtek Terbuka
        Schema::create('webinars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('banner_path')->nullable();
            $table->text('meeting_link')->nullable();
            $table->unsignedInteger('jp')->default(2);
            $table->date('event_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_live')->default(false);
            $table->time('questionnaire_start_time')->nullable();
            $table->time('questionnaire_end_time')->nullable();
            $table->time('exam_start_time')->nullable();
            $table->time('exam_end_time')->nullable();
            $table->integer('passing_grade')->default(75);
            $table->integer('max_remedial')->default(5);
            $table->integer('remedial_cap_score')->default(90);
            $table->timestamps();
        });

        // 4. Presensi Peserta Webinar
        Schema::create('webinar_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webinar_id')->constrained('webinars')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('attended_at')->useCurrent();
            $table->unique(['webinar_id', 'user_id']);
        });

        // 5. Percobaan Evaluasi & Remedial
        Schema::create('webinar_exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webinar_id')->constrained('webinars')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('attempt_number')->default(1);
            $table->decimal('raw_score', 5, 2);
            $table->decimal('final_score', 5, 2);
            $table->boolean('is_passed')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });

        // 6. Template & Koordinat Sertifikat
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('background_path');
            $table->json('layout_coordinates');
            $table->timestamps();
        });

        // 7. Penerbitan Sertifikat
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number', 100)->unique();
            $table->string('hash_token', 64)->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('webinar_id')->nullable()->constrained('webinars')->onDelete('set null');
            $table->foreignId('template_id')->constrained('certificate_templates');
            $table->string('training_title')->nullable();
            $table->unsignedInteger('jp')->default(2);
            $table->string('pdf_path')->nullable();
            $table->timestamp('issued_at')->useCurrent();
        });

        // 8. Audit Trail
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action', 100);
            $table->string('auditable_type', 150)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 9. Modul Pelatihan Formal / MOOC
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('type', ['mooc', 'teknis_fungsional', 'kepemimpinan'])->default('mooc');
            $table->string('category')->default('Umum');
            $table->text('description')->nullable();
            $table->string('banner_path')->nullable();
            $table->unsignedInteger('jp')->default(10);
            $table->integer('passing_grade')->default(75);
            $table->enum('status', ['draft', 'published', 'archived'])->default('published');
            $table->timestamps();
        });

        // 10. Modul Pelajaran MOOC
        Schema::create('course_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('title');
            $table->integer('sequence')->default(1);
            $table->string('video_url')->nullable();
            $table->unsignedInteger('duration_seconds')->default(600);
            $table->string('pdf_path')->nullable();
            $table->text('content_summary')->nullable();
            $table->boolean('has_quiz')->default(false);
            $table->timestamps();
        });

        // 11. Pendaftaran Kursus MOOC
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('progress_percentage')->default(0);
            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
            $table->decimal('final_score', 5, 2)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['course_id', 'user_id']);
        });

        // 12. Pelatihan Kepemimpinan (Logbook & Milestone Approval)
        Schema::create('leadership_logbooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Peserta ASN
            $table->foreignId('coach_id')->nullable()->constrained('users')->onDelete('set null'); // Widyaiswara
            $table->foreignId('mentor_id')->nullable()->constrained('users')->onDelete('set null'); // Atasan Langsung
            $table->unsignedInteger('week_number')->default(1);
            $table->string('milestone_title');
            $table->date('activity_date');
            $table->text('activity_description');
            $table->string('evidence_file_name')->nullable();
            $table->enum('status', ['submitted', 'revision', 'approved'])->default('submitted');
            $table->text('coach_feedback')->nullable();
            $table->text('mentor_feedback')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leadership_logbooks');
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('course_lessons');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('certificate_templates');
        Schema::dropIfExists('webinar_exam_attempts');
        Schema::dropIfExists('webinar_attendances');
        Schema::dropIfExists('webinars');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('user_profiles');
    }
};
