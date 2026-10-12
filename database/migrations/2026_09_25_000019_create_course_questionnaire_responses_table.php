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
        Schema::create('course_questionnaire_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nip', 30)->nullable();
            $table->string('nik', 30)->nullable();
            $table->string('nama_lengkap', 255)->nullable();
            $table->string('jabatan', 255)->nullable();
            $table->string('unit_kerja', 255)->nullable();
            $table->unsignedTinyInteger('rating_materi')->default(5);
            $table->unsignedTinyInteger('rating_sistem')->default(5);
            $table->unsignedTinyInteger('rating_manfaat')->default(5);
            $table->unsignedTinyInteger('rating_instruktur')->default(5);
            $table->text('feedback_text')->nullable();
            $table->json('answers')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_questionnaire_responses');
    }
};
