<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\HeavyEquipment;
use App\Models\Contractor;
use App\Models\ProjectSite;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ],
        );

        $equipment = [
            ['equipment_tag' => 'EXC-001', 'machine_model' => 'Komatsu PC210 Excavator', 'daily_rate' => 450],
            ['equipment_tag' => 'DZ-001', 'machine_model' => 'Caterpillar D6 Dozer', 'daily_rate' => 600],
            ['equipment_tag' => 'CR-001', 'machine_model' => 'Volvo A30G Articulated Hauler', 'daily_rate' => 525],
            ['equipment_tag' => 'GR-001', 'machine_model' => 'Caterpillar 140K Motor Grader', 'daily_rate' => 400],
        ];

        foreach ($equipment as $item) {
            HeavyEquipment::query()->firstOrCreate(
                ['equipment_tag' => $item['equipment_tag']],
                [...$item, 'machine_status' => 'Available'],
            );
        }

        foreach (['North Works Construction', 'Riverbend Contractors', 'Summit Infrastructure'] as $name) {
            Contractor::query()->firstOrCreate(['contractor_name' => $name]);
        }

        foreach (['Central Highway Project', 'River Crossing Site', 'North Industrial Park'] as $site) {
            ProjectSite::query()->firstOrCreate(['project_site' => $site]);
        }
    }
}
