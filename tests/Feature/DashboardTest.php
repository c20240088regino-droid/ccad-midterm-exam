<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\EquipmentRental;
use App\Models\HeavyEquipment;
use App\Models\ProjectSite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $this->actingAs($user = User::factory()->create());

        $this->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->missing('rentals'));
    }

    public function test_authenticated_users_are_redirected_to_the_dashboard_from_homepage()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_admin_users_are_redirected_to_the_admin_dashboard()
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get('/dashboard')->assertRedirect(route('admin.dashboard'));
    }

    public function test_non_admin_users_cannot_visit_the_admin_dashboard()
    {
        $this->actingAs(User::factory()->create(['role' => 'user']));

        $this->get('/admin/dashboard')->assertStatus(403);
    }

    public function test_admin_dashboard_shows_all_rental_transactions()
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $equipment = HeavyEquipment::create([
            'equipment_tag' => 'ADMIN-EXC-01',
            'machine_model' => 'Excavator',
            'daily_rate' => 100,
        ]);
        $contractor = Contractor::create(['contractor_name' => 'Admin Dashboard Contractor']);
        $site = ProjectSite::create(['project_site' => 'Admin Dashboard Site']);

        foreach (range(1, 13) as $day) {
            $date = now()->subDays($day)->toDateString();
            EquipmentRental::create([
                'heavy_equipment_id' => $equipment->id,
                'contractor_id' => $contractor->id,
                'project_site_id' => $site->id,
                'start_date' => $date,
                'end_date' => $date,
                'daily_rate' => 100,
                'fuel_liters_used' => 0,
                'fuel_rate' => 0,
            ]);
        }

        $this->get('/admin/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('admin-dashboard')
            ->has('rentals', 13));
    }

    public function test_admin_can_add_equipment_from_the_existing_endpoint()
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/heavy-equipment', [
            'equipment_tag' => 'ADMIN-NEW-01',
            'machine_model' => 'Wheel Loader',
            'daily_rate' => 175,
            'machine_status' => 'Available',
        ])->assertCreated();

        $this->assertDatabaseHas('heavy_equipment', [
            'equipment_tag' => 'ADMIN-NEW-01',
            'machine_model' => 'Wheel Loader',
        ]);
    }
}
