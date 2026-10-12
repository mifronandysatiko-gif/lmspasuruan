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
        Schema::create('sidebar_menu_settings', function (Blueprint $table) {
            $table->id();
            $table->string('role', 50)->index(); // 'peserta', 'super_admin', 'penyelenggara', 'widyaiswara', 'mentor', 'common'
            $table->string('menu_key', 100); // Unique identifier e.g. 'peserta.webinar', 'super_admin.users'
            $table->string('menu_label', 150); // Display label
            $table->string('menu_group', 100)->nullable(); // Category header e.g. 'Tata Kelola', 'Program Diklat'
            $table->string('icon', 100)->nullable(); // FontAwesome icon class
            $table->string('route_name', 150)->nullable(); // Laravel route name
            $table->boolean('is_visible')->default(true); // true = visible, false = hidden
            $table->integer('order_index')->default(0);
            $table->timestamps();

            $table->unique(['role', 'menu_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sidebar_menu_settings');
    }
};
