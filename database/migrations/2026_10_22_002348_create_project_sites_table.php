<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_sites', function (Blueprint $table) {
            $table->id();
            $table->string('project_site', 200)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_sites');
    }
};