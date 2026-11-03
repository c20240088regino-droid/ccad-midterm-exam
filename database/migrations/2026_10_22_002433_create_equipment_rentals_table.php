<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipment_rentals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('heavy_equipment_id')
                ->constrained('heavy_equipment')
                ->onDelete('restrict');

            $table->foreignId('contractor_id')
                ->constrained('contractors')
                ->onDelete('restrict');

            $table->foreignId('project_site_id')
                ->constrained('project_sites')
                ->onDelete('restrict');

            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->decimal('daily_rate', 12, 2);
            $table->decimal('fuel_liters_used', 12, 2)->default(0);
            $table->decimal('fuel_rate', 12, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_rentals');
    }
};