<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\HeavyEquipment;
use App\Models\ProjectSite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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
        $this->actingAs(User::factory()->create(['role' => 'admin']));

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
        ])->assertOk()
            ->assertJsonPath('contractor.contractor_name', 'Updated Contractor');

        $this->deleteJson('/contractors/'.$newContractor->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contractor');
        $this->deleteJson('/project-sites/'.$site->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('project_site');
        $this->deleteJson('/heavy-equipment/'.$equipment->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('equipment');

        $this->getJson('/equipment-rentals/'.$rentalId)
            ->assertOk()
            ->assertJsonPath('id', $rentalId);

        $this->deleteJson("/equipment-rentals/{$rentalId}")->assertOk();
        $this->assertDatabaseMissing('equipment_rentals', ['id' => $rentalId]);
        $this->assertDatabaseHas('heavy_equipment', [
            'id' => $equipment->id,
            'machine_status' => 'Available',
        ]);
        $this->deleteJson('/contractors/'.$newContractor->id)->assertOk();
        $this->deleteJson('/project-sites/'.$site->id)->assertOk();
        $this->deleteJson('/heavy-equipment/'.$equipment->id)->assertOk();
    }

    public function test_non_admin_users_cannot_view_update_or_delete_rental_transactions(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']));
        $equipment = HeavyEquipment::create([
            'equipment_tag' => 'PRIVATE-RENTAL-01',
            'machine_model' => 'Excavator',
            'daily_rate' => 100,
        ]);
        $contractor = Contractor::create(['contractor_name' => 'Private Rental Contractor']);
        $site = ProjectSite::create(['project_site' => 'Private Rental Site']);
        $rental = $this->postJson('/equipment-rentals', [
            'heavy_equipment_id' => $equipment->id,
            'contractor_id' => $contractor->id,
            'project_site_id' => $site->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'fuel_rate' => 5,
        ])->assertCreated();
        $rentalId = $rental->json('id');

        $this->getJson('/equipment-rentals')->assertForbidden();
        $this->getJson("/equipment-rentals/{$rentalId}")->assertForbidden();
        $this->putJson("/equipment-rentals/{$rentalId}", ['contractor_id' => $contractor->id])->assertForbidden();
        $this->deleteJson("/equipment-rentals/{$rentalId}")->assertForbidden();
        $this->assertDatabaseHas('equipment_rentals', ['id' => $rentalId]);
    }

    public function test_contractor_and_project_site_endpoints_normalize_and_validate_names(): void
    {
        $this->actingAs(User::factory()->create());

        $contractor = $this->postJson('/contractors', ['contractor_name' => '  North   Works  '])
            ->assertCreated()
            ->assertJsonPath('contractor_name', 'North Works');
        $this->postJson('/contractors', ['contractor_name' => 'North Works'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contractor_name');
        $this->putJson('/contractors/'.$contractor->json('id'), ['contractor_name' => 'North Construction'])
            ->assertOk()
            ->assertJsonPath('contractor_name', 'North Construction');
        $this->getJson('/contractors/'.$contractor->json('id'))
            ->assertOk()
            ->assertJsonPath('contractor_name', 'North Construction');

        $site = $this->postJson('/project-sites', ['project_site' => '  River   Crossing  '])
            ->assertCreated()
            ->assertJsonPath('project_site', 'River Crossing');
        $this->postJson('/project-sites', ['project_site' => 'River Crossing'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('project_site');
        $this->putJson('/project-sites/'.$site->json('id'), ['project_site' => 'Western Crossing'])
            ->assertOk()
            ->assertJsonPath('project_site', 'Western Crossing');
        $this->getJson('/project-sites/'.$site->json('id'))
            ->assertOk()
            ->assertJsonPath('project_site', 'Western Crossing');

        $this->deleteJson('/contractors/'.$contractor->json('id'))->assertOk();
        $this->deleteJson('/project-sites/'.$site->json('id'))->assertOk();
    }

    public function test_equipment_tags_are_normalized_before_uniqueness_validation(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson('/heavy-equipment', [
            'equipment_tag' => '  dozer-01 ',
            'machine_model' => 'Crawler Dozer',
            'daily_rate' => 100,
        ])->assertCreated()->assertJsonPath('equipment_tag', 'DOZER-01');

        $equipmentId = HeavyEquipment::query()->where('equipment_tag', 'DOZER-01')->value('id');
        $this->getJson('/heavy-equipment/'.$equipmentId)
            ->assertOk()
            ->assertJsonPath('equipment_tag', 'DOZER-01');

        $this->putJson('/heavy-equipment/'.$equipmentId, [
            'machine_model' => 'Updated Crawler Dozer',
            'daily_rate' => 125,
        ])->assertOk()
            ->assertJsonPath('machine_model', 'Updated Crawler Dozer');

        $this->postJson('/heavy-equipment', [
            'equipment_tag' => 'dozer-01',
            'machine_model' => 'Crawler Dozer',
            'daily_rate' => 100,
        ])->assertUnprocessable()->assertJsonValidationErrors('equipment_tag');

        $this->deleteJson('/heavy-equipment/'.$equipmentId)->assertOk();
    }

    public function test_register_equipment_page_and_equipment_creation_work(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/register-equipment')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('register-equipment'));

        $this->post('/heavy-equipment', [
            'equipment_tag' => 'new-machine-01',
            'machine_model' => 'Excavator',
            'daily_rate' => 200,
        ], ['X-Inertia' => 'true'])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('heavy_equipment', [
            'equipment_tag' => 'NEW-MACHINE-01',
            'machine_status' => 'Available',
        ]);
    }

    public function test_rentals_reject_invalid_date_ranges_and_overlapping_equipment_bookings(): void
    {
        $this->actingAs(User::factory()->create());

        $equipment = HeavyEquipment::create([
            'equipment_tag' => 'LOADER-01',
            'machine_model' => 'Wheel Loader',
            'daily_rate' => 90,
        ]);
        $contractor = Contractor::create(['contractor_name' => 'North Works']);
        $site = ProjectSite::create(['project_site' => 'River Crossing']);
        $startDate = now()->addDays(3)->toDateString();
        $endDate = now()->addDays(5)->toDateString();
        $payload = [
            'heavy_equipment_id' => $equipment->id,
            'contractor_id' => $contractor->id,
            'project_site_id' => $site->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'fuel_rate' => 4,
        ];

        $this->postJson('/equipment-rentals', array_replace($payload, [
            'end_date' => now()->addDays(2)->toDateString(),
        ]))->assertUnprocessable()->assertJsonValidationErrors('end_date');

        $this->postJson('/equipment-rentals', $payload)->assertCreated();
        $this->postJson('/equipment-rentals', array_replace($payload, [
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
        ]))->assertUnprocessable()->assertJsonValidationErrors('heavy_equipment_id');
    }
}
