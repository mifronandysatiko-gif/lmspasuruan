<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->string('number_format', 100)->nullable()->default('893.3/{NOMOR}/BKPSDM-PAS/{TAHUN}');
            $table->string('signer_name', 150)->nullable()->default('Drs. H. Ahmad Fauzi, M.Si');
            $table->string('signer_nip', 50)->nullable()->default('19720415 199603 1 002');
            $table->string('signer_position', 200)->nullable()->default('Kepala Badan Kepegawaian dan Pengembangan Sumber Daya Manusia');
            $table->string('issue_city', 100)->nullable()->default('Bangil, Pasuruan');
            $table->string('signature_path')->nullable();
            $table->string('stamp_path')->nullable();
            $table->string('tte_barcode_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->dropColumn([
                'number_format',
                'signer_name',
                'signer_nip',
                'signer_position',
                'issue_city',
                'signature_path',
                'stamp_path',
                'tte_barcode_path',
            ]);
        });
    }
};
