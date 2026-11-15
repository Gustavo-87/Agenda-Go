<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    private function adminOf(Business $business): User
    {
        return User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);
    }

    private function professionalOf(Business $business, string $name = 'Stefania'): Professional
    {
        return Professional::create([
            'name' => $name,
            'business_id' => $business->id,
        ]);
    }

    public function test_an_admin_can_create_a_service_for_a_professional_of_their_business(): void
    {
        $business = Business::factory()->create();
        $professional = $this->professionalOf($business);

        $this->actingAs($this->adminOf($business))
            ->postJson("/businesses/{$business->id}/services", [
                'name' => 'Semipermanente',
                'duration_minutes' => 120,
                'price' => 50000,
                'professional_id' => $professional->id,
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('services', [
            'name' => 'Semipermanente',
            'duration_minutes' => 120,
            'price' => 50000,
            'professional_id' => $professional->id,
            'business_id' => $business->id,
        ]);
    }

    public function test_a_service_cannot_be_assigned_to_a_professional_of_another_business(): void
    {
        $business = Business::factory()->create();
        $otherProfessional = $this->professionalOf(Business::factory()->create(), 'Laura');

        $this->actingAs($this->adminOf($business))
            ->postJson("/businesses/{$business->id}/services", [
                'name' => 'Semipermanente',
                'duration_minutes' => 120,
                'price' => 50000,
                'professional_id' => $otherProfessional->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('professional_id');

        $this->assertDatabaseCount('services', 0);
    }

    public function test_durations_and_prices_are_configurable(): void
    {
        $business = Business::factory()->create();
        $professional = $this->professionalOf($business);
        $service = Service::create([
            'name' => 'Semipermanente',
            'duration_minutes' => 60,
            'price' => 30000,
            'professional_id' => $professional->id,
            'business_id' => $business->id,
        ]);

        $this->actingAs($this->adminOf($business))
            ->patchJson("/services/{$service->id}", [
                'duration_minutes' => 180,
                'price' => 75000,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'duration_minutes' => 180,
            'price' => 75000,
        ]);
    }

    public function test_an_admin_cannot_modify_a_service_of_another_business(): void
    {
        $ownBusiness = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $professional = $this->professionalOf($otherBusiness, 'Laura');
        $service = Service::create([
            'name' => 'Esmaltado',
            'duration_minutes' => 30,
            'price' => 30000,
            'professional_id' => $professional->id,
            'business_id' => $otherBusiness->id,
        ]);

        $admin = $this->adminOf($ownBusiness);

        $this->actingAs($admin)
            ->patchJson("/services/{$service->id}", ['price' => 1])
            ->assertStatus(403);

        $this->actingAs($admin)
            ->patchJson("/services/{$service->id}/toggle")
            ->assertStatus(403);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'price' => 30000,
            'active' => true,
        ]);
    }

    public function test_a_super_admin_can_manage_services_of_any_business(): void
    {
        $business = Business::factory()->create();
        $professional = $this->professionalOf($business, 'Laura');
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($superAdmin)
            ->postJson("/businesses/{$business->id}/services", [
                'name' => 'Manos y pies',
                'duration_minutes' => 180,
                'price' => 90000,
                'professional_id' => $professional->id,
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('services', ['name' => 'Manos y pies']);
    }

    public function test_a_service_can_be_deactivated(): void
    {
        $business = Business::factory()->create();
        $professional = $this->professionalOf($business);
        $service = Service::create([
            'name' => 'Esmaltado',
            'duration_minutes' => 30,
            'price' => 30000,
            'professional_id' => $professional->id,
            'business_id' => $business->id,
        ]);

        $this->actingAs($this->adminOf($business))
            ->patchJson("/services/{$service->id}/toggle")
            ->assertStatus(200);

        $this->assertDatabaseHas('services', ['id' => $service->id, 'active' => false]);
    }

    public function test_the_listing_shows_only_services_of_the_target_business(): void
    {
        $ownBusiness = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        Service::create([
            'name' => 'Semipermanente',
            'duration_minutes' => 120,
            'price' => 50000,
            'professional_id' => $this->professionalOf($ownBusiness)->id,
            'business_id' => $ownBusiness->id,
        ]);
        Service::create([
            'name' => 'Esmaltado',
            'duration_minutes' => 30,
            'price' => 30000,
            'professional_id' => $this->professionalOf($otherBusiness, 'Laura')->id,
            'business_id' => $otherBusiness->id,
        ]);

        $this->actingAs($this->adminOf($ownBusiness))
            ->getJson("/businesses/{$ownBusiness->id}/services")
            ->assertOk()
            ->assertJsonFragment(['name' => 'Semipermanente'])
            ->assertJsonMissing(['name' => 'Esmaltado']);
    }

    public function test_a_professional_role_user_cannot_manage_services(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Professional,
        ]);

        $this->actingAs($user)
            ->postJson("/businesses/{$business->id}/services", [
                'name' => 'Semipermanente',
                'duration_minutes' => 120,
                'price' => 50000,
                'professional_id' => $this->professionalOf($business)->id,
            ])
            ->assertStatus(403);
    }
}
