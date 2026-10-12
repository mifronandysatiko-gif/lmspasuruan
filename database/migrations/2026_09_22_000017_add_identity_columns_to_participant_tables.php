<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan kolom identitas lengkap (NIP, NIK, Nama Lengkap, Jabatan, Unit Kerja)
     * langsung ke tabel-tabel transaksi peserta di database MySQL.
     */
    public function up(): void
    {
        // 1. users table
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'nik')) {
                    $table->string('nik', 16)->nullable()->after('nip');
                }
                if (!Schema::hasColumn('users', 'jabatan')) {
                    $table->string('jabatan', 255)->nullable()->after('role');
                }
                if (!Schema::hasColumn('users', 'unit_kerja')) {
                    $table->string('unit_kerja', 255)->nullable()->after('jabatan');
                }
            });
        }

        // 2. webinar_attendances
        if (Schema::hasTable('webinar_attendances')) {
            Schema::table('webinar_attendances', function (Blueprint $table) {
                if (!Schema::hasColumn('webinar_attendances', 'nip')) {
                    $table->string('nip', 50)->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('webinar_attendances', 'nik')) {
                    $table->string('nik', 16)->nullable()->after('nip');
                }
                if (!Schema::hasColumn('webinar_attendances', 'nama_lengkap')) {
                    $table->string('nama_lengkap', 255)->nullable()->after('nik');
                }
                if (!Schema::hasColumn('webinar_attendances', 'jabatan')) {
                    $table->string('jabatan', 255)->nullable()->after('nama_lengkap');
                }
                if (!Schema::hasColumn('webinar_attendances', 'unit_kerja')) {
                    $table->string('unit_kerja', 255)->nullable()->after('jabatan');
                }
            });
        }

        // 3. webinar_registrations
        if (Schema::hasTable('webinar_registrations')) {
            Schema::table('webinar_registrations', function (Blueprint $table) {
                if (!Schema::hasColumn('webinar_registrations', 'nip')) {
                    $table->string('nip', 50)->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('webinar_registrations', 'nik')) {
                    $table->string('nik', 16)->nullable()->after('nip');
                }
                if (!Schema::hasColumn('webinar_registrations', 'nama_lengkap')) {
                    $table->string('nama_lengkap', 255)->nullable()->after('nik');
                }
                if (!Schema::hasColumn('webinar_registrations', 'jabatan')) {
                    $table->string('jabatan', 255)->nullable()->after('nama_lengkap');
                }
                if (!Schema::hasColumn('webinar_registrations', 'unit_kerja')) {
                    $table->string('unit_kerja', 255)->nullable()->after('jabatan');
                }
            });
        }

        // 4. webinar_questionnaire_responses
        if (Schema::hasTable('webinar_questionnaire_responses')) {
            Schema::table('webinar_questionnaire_responses', function (Blueprint $table) {
                if (!Schema::hasColumn('webinar_questionnaire_responses', 'nip')) {
                    $table->string('nip', 50)->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('webinar_questionnaire_responses', 'nik')) {
                    $table->string('nik', 16)->nullable()->after('nip');
                }
                if (!Schema::hasColumn('webinar_questionnaire_responses', 'nama_lengkap')) {
                    $table->string('nama_lengkap', 255)->nullable()->after('nik');
                }
                if (!Schema::hasColumn('webinar_questionnaire_responses', 'jabatan')) {
                    $table->string('jabatan', 255)->nullable()->after('nama_lengkap');
                }
                if (!Schema::hasColumn('webinar_questionnaire_responses', 'unit_kerja')) {
                    $table->string('unit_kerja', 255)->nullable()->after('jabatan');
                }
            });
        }

        // 5. webinar_exam_attempts
        if (Schema::hasTable('webinar_exam_attempts')) {
            Schema::table('webinar_exam_attempts', function (Blueprint $table) {
                if (!Schema::hasColumn('webinar_exam_attempts', 'nip')) {
                    $table->string('nip', 50)->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('webinar_exam_attempts', 'nik')) {
                    $table->string('nik', 16)->nullable()->after('nip');
                }
                if (!Schema::hasColumn('webinar_exam_attempts', 'nama_lengkap')) {
                    $table->string('nama_lengkap', 255)->nullable()->after('nik');
                }
                if (!Schema::hasColumn('webinar_exam_attempts', 'jabatan')) {
                    $table->string('jabatan', 255)->nullable()->after('nama_lengkap');
                }
                if (!Schema::hasColumn('webinar_exam_attempts', 'unit_kerja')) {
                    $table->string('unit_kerja', 255)->nullable()->after('jabatan');
                }
            });
        }

        // 6. course_enrollments
        if (Schema::hasTable('course_enrollments')) {
            Schema::table('course_enrollments', function (Blueprint $table) {
                if (!Schema::hasColumn('course_enrollments', 'nip')) {
                    $table->string('nip', 50)->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('course_enrollments', 'nik')) {
                    $table->string('nik', 16)->nullable()->after('nip');
                }
                if (!Schema::hasColumn('course_enrollments', 'nama_lengkap')) {
                    $table->string('nama_lengkap', 255)->nullable()->after('nik');
                }
                if (!Schema::hasColumn('course_enrollments', 'jabatan')) {
                    $table->string('jabatan', 255)->nullable()->after('nama_lengkap');
                }
                if (!Schema::hasColumn('course_enrollments', 'unit_kerja')) {
                    $table->string('unit_kerja', 255)->nullable()->after('jabatan');
                }
            });
        }

        // 7. course_lesson_completions
        if (Schema::hasTable('course_lesson_completions')) {
            Schema::table('course_lesson_completions', function (Blueprint $table) {
                if (!Schema::hasColumn('course_lesson_completions', 'nip')) {
                    $table->string('nip', 50)->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('course_lesson_completions', 'nik')) {
                    $table->string('nik', 16)->nullable()->after('nip');
                }
                if (!Schema::hasColumn('course_lesson_completions', 'nama_lengkap')) {
                    $table->string('nama_lengkap', 255)->nullable()->after('nik');
                }
                if (!Schema::hasColumn('course_lesson_completions', 'jabatan')) {
                    $table->string('jabatan', 255)->nullable()->after('nama_lengkap');
                }
                if (!Schema::hasColumn('course_lesson_completions', 'unit_kerja')) {
                    $table->string('unit_kerja', 255)->nullable()->after('jabatan');
                }
            });
        }

        // 8. leadership_logbooks
        if (Schema::hasTable('leadership_logbooks')) {
            Schema::table('leadership_logbooks', function (Blueprint $table) {
                if (!Schema::hasColumn('leadership_logbooks', 'nip')) {
                    $table->string('nip', 50)->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('leadership_logbooks', 'nik')) {
                    $table->string('nik', 16)->nullable()->after('nip');
                }
                if (!Schema::hasColumn('leadership_logbooks', 'nama_lengkap')) {
                    $table->string('nama_lengkap', 255)->nullable()->after('nik');
                }
                if (!Schema::hasColumn('leadership_logbooks', 'jabatan')) {
                    $table->string('jabatan', 255)->nullable()->after('nama_lengkap');
                }
                if (!Schema::hasColumn('leadership_logbooks', 'unit_kerja')) {
                    $table->string('unit_kerja', 255)->nullable()->after('jabatan');
                }
            });
        }

        // 9. certificates
        if (Schema::hasTable('certificates')) {
            Schema::table('certificates', function (Blueprint $table) {
                if (!Schema::hasColumn('certificates', 'nip')) {
                    $table->string('nip', 50)->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('certificates', 'nik')) {
                    $table->string('nik', 16)->nullable()->after('nip');
                }
                if (!Schema::hasColumn('certificates', 'nama_lengkap')) {
                    $table->string('nama_lengkap', 255)->nullable()->after('nik');
                }
                if (!Schema::hasColumn('certificates', 'jabatan')) {
                    $table->string('jabatan', 255)->nullable()->after('nama_lengkap');
                }
                if (!Schema::hasColumn('certificates', 'unit_kerja')) {
                    $table->string('unit_kerja', 255)->nullable()->after('jabatan');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'users' => ['nik', 'jabatan', 'unit_kerja'],
            'webinar_attendances' => ['nip', 'nik', 'nama_lengkap', 'jabatan', 'unit_kerja'],
            'webinar_registrations' => ['nip', 'nik', 'nama_lengkap', 'jabatan', 'unit_kerja'],
            'webinar_questionnaire_responses' => ['nip', 'nik', 'nama_lengkap', 'jabatan', 'unit_kerja'],
            'webinar_exam_attempts' => ['nip', 'nik', 'nama_lengkap', 'jabatan', 'unit_kerja'],
            'course_enrollments' => ['nip', 'nik', 'nama_lengkap', 'jabatan', 'unit_kerja'],
            'course_lesson_completions' => ['nip', 'nik', 'nama_lengkap', 'jabatan', 'unit_kerja'],
            'leadership_logbooks' => ['nip', 'nik', 'nama_lengkap', 'jabatan', 'unit_kerja'],
            'certificates' => ['nip', 'nik', 'nama_lengkap', 'jabatan', 'unit_kerja'],
        ];

        foreach ($tables as $tableName => $columns) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
                    foreach ($columns as $col) {
                        if (Schema::hasColumn($tableName, $col)) {
                            $table->dropColumn($col);
                        }
                    }
                });
            }
        }
    }
};
