<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('heavy_equipment', function (Blueprint $table) {
            $table->id();

            $table->string('equipment_tag', 50)->unique();
            $table->string('machine_model', 150);
            $table->decimal('daily_rate', 12, 2);

            $table->enum('machine_status', [
                'Available',
                'Rented',
                'Maintenance',
            ])->default('Available')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heavy_equipment');
    }
};