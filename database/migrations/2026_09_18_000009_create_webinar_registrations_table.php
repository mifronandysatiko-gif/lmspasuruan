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
        if (!Schema::hasTable('webinar_registrations')) {
            Schema::create('webinar_registrations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('webinar_id')->constrained('webinars')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->timestamp('registered_at')->useCurrent();
                $table->string('notes')->nullable();
                $table->timestamps();

                $table->unique(['webinar_id', 'user_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webinar_registrations');
    }
};
