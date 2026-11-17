<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->dropIndex('contractors_contractor_name_index');
            $table->unique('contractor_name');
        });

        Schema::table('project_sites', function (Blueprint $table) {
            $table->dropIndex('project_sites_project_site_index');
            $table->unique('project_site');
        });

        Schema::table('equipment_rentals', function (Blueprint $table) {
            $table->index(
                ['heavy_equipment_id', 'start_date', 'end_date'],
                'equipment_rentals_equipment_dates_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('equipment_rentals', function (Blueprint $table) {
            $table->dropIndex('equipment_rentals_equipment_dates_index');
        });

        Schema::table('project_sites', function (Blueprint $table) {
            $table->dropUnique('project_sites_project_site_unique');
            $table->index('project_site');
        });

        Schema::table('contractors', function (Blueprint $table) {
            $table->dropUnique('contractors_contractor_name_unique');
            $table->index('contractor_name');
        });
    }
};