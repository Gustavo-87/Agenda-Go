<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Professional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfessionalManagementTest extends TestCase
{
    use RefreshDatabase;

    private function adminOf(Business $business): User
    {
        return User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);
    }

    private function professionalOf(Business $business, string $name, bool $active = true): Professional
    {
        return Professional::create([
            'name' => $name,
            'business_id' => $business->id,
            'active' => $active,
        ]);
    }

    public function test_an_admin_can_create_a_professional_in_their_business(): void
    {
        $business = Business::factory()->create();

        $this->actingAs($this->adminOf($business))
            ->postJson('/professionals', ['name' => 'Stefania', 'phone' => '3000000000'])
            ->assertStatus(201);

        $this->assertDatabaseHas('professionals', [
            'name' => 'Stefania',
            'business_id' => $business->id,
            'active' => true,
        ]);
    }

    public function test_the_business_id_parameter_is_ignored_for_admins(): void
    {
        $ownBusiness = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        $this->actingAs($this->adminOf($ownBusiness))
            ->postJson('/professionals', [
                'name' => 'Intrusa',
                'business_id' => $otherBusiness->id,
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('professionals', [
            'name' => 'Intrusa',
            'business_id' => $ownBusiness->id,
        ]);
    }

    public function test_a_super_admin_cannot_create_professionals(): void
    {
        $business = Business::factory()->create();
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($superAdmin)
            ->postJson('/professionals', [
                'name' => 'Laura',
                'business_id' => $business->id,
            ])
            ->assertStatus(403);

        $this->assertDatabaseCount('professionals', 0);
    }

    public function test_a_business_cannot_exceed_five_active_professionals(): void
    {
        $business = Business::factory()->create();
        $admin = $this->adminOf($business);

        foreach (range(1, 5) as $i) {
            $this->actingAs($admin)
                ->postJson('/professionals', ['name' => "Profesional {$i}"])
                ->assertStatus(201);
        }

        $this->actingAs($admin)
            ->postJson('/professionals', ['name' => 'Profesional 6'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('active');
    }

    public function test_inactive_professionals_do_not_count_toward_the_active_limit(): void
    {
        $business = Business::factory()->create();
        $admin = $this->adminOf($business);

        $inactive = $this->professionalOf($business, 'Inactiva', false);
        foreach (range(1, 5) as $i) {
            $this->professionalOf($business, "Profesional {$i}");
        }

        $this->actingAs($admin)
            ->postJson('/professionals', ['name' => 'Sexta'])
            ->assertStatus(422);

        $this->actingAs($admin)
            ->patchJson("/professionals/{$inactive->id}/toggle")
            ->assertStatus(422);

        $this->actingAs($admin)
            ->postJson('/professionals', ['name' => 'Otra', 'active' => false])
            ->assertStatus(201);

        $primera = Professional::where('business_id', $business->id)
            ->where('name', 'Profesional 1')->firstOrFail();

        $this->actingAs($admin)
            ->patchJson("/professionals/{$primera->id}/toggle")
            ->assertStatus(200);

        $this->actingAs($admin)
            ->patchJson("/professionals/{$inactive->id}/toggle")
            ->assertStatus(200);

        $this->assertDatabaseHas('professionals', [
            'id' => $inactive->id,
            'active' => true,
        ]);
    }

    public function test_an_admin_can_update_and_deactivate_their_own_professional(): void
    {
        $business = Business::factory()->create();
        $professional = $this->professionalOf($business, 'Stefania');
        $admin = $this->adminOf($business);

        $this->actingAs($admin)
            ->patchJson("/professionals/{$professional->id}", ['name' => 'Stefania Rojas'])
            ->assertStatus(200);

        $this->actingAs($admin)
            ->patchJson("/professionals/{$professional->id}/toggle")
            ->assertStatus(200);

        $this->assertDatabaseHas('professionals', [
            'id' => $professional->id,
            'name' => 'Stefania Rojas',
            'active' => false,
        ]);
    }

    public function test_an_admin_cannot_modify_a_professional_of_another_business(): void
    {
        $ownBusiness = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $professional = $this->professionalOf($otherBusiness, 'Laura');

        $admin = $this->adminOf($ownBusiness);

        $this->actingAs($admin)
            ->patchJson("/professionals/{$professional->id}", ['name' => 'Hackeada'])
            ->assertStatus(403);

        $this->actingAs($admin)
            ->patchJson("/professionals/{$professional->id}/toggle")
            ->assertStatus(403);

        $this->assertDatabaseHas('professionals', [
            'id' => $professional->id,
            'name' => 'Laura',
            'active' => true,
        ]);
    }

    public function test_a_super_admin_cannot_modify_professionals_of_any_business(): void
    {
        $business = Business::factory()->create();
        $professional = $this->professionalOf($business, 'Laura');
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($superAdmin)
            ->patchJson("/professionals/{$professional->id}", ['name' => 'Laura Gómez'])
            ->assertStatus(403);
    }

    public function test_a_professional_role_user_cannot_manage_professionals(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Professional,
        ]);

        $this->actingAs($user)
            ->postJson('/professionals', ['name' => 'Nueva'])
            ->assertStatus(403);

        $this->actingAs($user)
            ->getJson('/professionals')
            ->assertStatus(403);
    }

    public function test_the_listing_shows_only_professionals_of_the_target_business(): void
    {
        $ownBusiness = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        $this->professionalOf($ownBusiness, 'Stefania');
        $this->professionalOf($otherBusiness, 'Laura');

        $this->actingAs($this->adminOf($ownBusiness))
            ->getJson('/professionals')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Stefania'])
            ->assertJsonMissing(['name' => 'Laura']);
    }
}
