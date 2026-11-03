<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\HeavyEquipment;
use App\Models\ProjectSite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentRentalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_equipment_and_active_rental_are_created_with_calculated_values(): void
    {
        $this->actingAs(User::factory()->create());

        $equipmentResponse = $this->postJson('/heavy-equipment', [
            'equipment_tag' => ' dozer-01 ',
            'machine_model' => 'Crawler Dozer',
            'daily_rate' => 100,
        ])->assertCreated();

        $equipmentId = $equipmentResponse->json('id');
        $this->assertSame('DOZER-01', $equipmentResponse->json('equipment_tag'));

        $contractor = Contractor::create(['contractor_name' => 'North Works']);
        $site = ProjectSite::create(['project_site' => 'River Crossing']);
        $startDate = now()->toDateString();

        $rental = $this->postJson('/equipment-rentals', [
            'heavy_equipment_id' => $equipmentId,
            'contractor_id' => $contractor->id,
            'project_site_id' => $site->id,
            'start_date' => $startDate,
            'end_date' => now()->addDays(2)->toDateString(),
            'fuel_liters_used' => 10,
            'fuel_rate' => 5,
        ])->assertCreated();

        $rental->assertJsonPath('total_days', 3)
            ->assertJsonPath('total_rental_cost', 350);
        $this->assertDatabaseHas('heavy_equipment', [
            'id' => $equipmentId,
            'machine_status' => 'Rented',
        ]);
    }

    public function test_rental_can_be_updated_and_deleted(): void
    {
        $this->actingAs(User::factory()->create());

        $equipment = HeavyEquipment::create([
            'equipment_tag' => 'EXCAVATOR-01',
            'machine_model' => 'Excavator',
            'daily_rate' => 125,
        ]);
        $contractor = Contractor::create(['contractor_name' => 'Original Contractor']);
        $newContractor = Contractor::create(['contractor_name' => 'Updated Contractor']);
        $site = ProjectSite::create(['project_site' => 'Central Site']);
        $startDate = now()->toDateString();

        $rental = $this->postJson('/equipment-rentals', [
            'heavy_equipment_id' => $equipment->id,
            'contractor_id' => $contractor->id,
            'project_site_id' => $site->id,
            'start_date' => $startDate,
            'end_date' => now()->addDay()->toDateString(),
            'fuel_rate' => 5,
        ])->assertCreated();

        $rentalId = $rental->json('id');

        $this->putJson("/equipment-rentals/{$rentalId}", [
            'contractor_id' => $newContractor->id,
        ])->assertOk()->assertJsonPath('contractor.contractor_name', 'Updated Contractor');

        $this->deleteJson("/equipment-rentals/{$rentalId}")->assertOk();
        $this->assertDatabaseMissing('equipment_rentals', ['id' => $rentalId]);
        $this->assertDatabaseHas('heavy_equipment', [
            'id' => $equipment->id,
            'machine_status' => 'Available',
        ]);
    }
}
