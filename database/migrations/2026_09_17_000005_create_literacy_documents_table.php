<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('literacy_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category', 50); // jurnal, karya_ilmiah, policy_brief, modul_diklat, buku_panduan
            $table->string('author');
            $table->string('institution');
            $table->unsignedSmallInteger('publication_year');
            $table->string('edition_volume', 100)->nullable(); // e.g. Vol. 5 No. 1
            $table->string('isbn_issn', 50)->nullable();
            $table->text('description'); // Abstrak / Sinopsis
            $table->string('keywords')->nullable(); // Tag pencarian dipisah koma
            $table->string('file_path')->nullable(); // Path PDF di storage/app/public/literasi/...
            $table->unsignedInteger('file_size_kb')->default(0);
            $table->string('cover_image')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('download_count')->default(0);
            $table->boolean('is_published')->default(true);
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('literacy_documents');
    }
};