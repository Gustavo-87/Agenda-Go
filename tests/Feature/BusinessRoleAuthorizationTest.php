<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class BusinessRoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_is_persisted_with_their_business_and_role(): void
    {
        $business = Business::factory()->create();

        $user = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'business_id' => $business->id,
            'role' => 'admin',
        ]);
        $this->assertTrue($user->belongsToBusiness($business));
    }

    public function test_a_super_admin_can_manage_any_business(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $business = Business::factory()->create();

        $this->assertTrue(Gate::forUser($superAdmin)->allows('manage', $business));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('view', $business));
    }

    public function test_an_admin_can_manage_their_own_business(): void
    {
        $business = Business::factory()->create();
        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);

        $this->assertTrue(Gate::forUser($admin)->allows('manage', $business));
    }

    public function test_an_admin_cannot_manage_a_business_of_another_tenant(): void
    {
        $ownBusiness = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $admin = User::factory()->create([
            'business_id' => $ownBusiness->id,
            'role' => UserRole::Admin,
        ]);

        $this->assertFalse(Gate::forUser($admin)->allows('manage', $otherBusiness));
        $this->assertFalse(Gate::forUser($admin)->allows('view', $otherBusiness));
    }

    public function test_a_professional_cannot_manage_their_business(): void
    {
        $business = Business::factory()->create();
        $professional = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Professional,
        ]);

        $this->assertFalse(Gate::forUser($professional)->allows('manage', $business));
        $this->assertTrue(Gate::forUser($professional)->allows('view', $business));
    }

    public function test_a_user_without_business_cannot_manage_any_business(): void
    {
        $user = User::factory()->create(['role' => UserRole::Professional]);
        $business = Business::factory()->create();

        $this->assertFalse(Gate::forUser($user)->allows('manage', $business));
        $this->assertFalse(Gate::forUser($user)->allows('view', $business));
    }

    public function test_a_user_cannot_connect_google_calendar_of_another_business(): void
    {
        $owner = User::factory()->create();
        $professional = Professional::forceCreate([
            'name' => 'Stefania',
            'user_id' => $owner->id,
        ]);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->get("/google/connect/{$professional->id}")
            ->assertForbidden();
    }

    public function test_the_agenda_shows_only_the_professionals_own_appointments(): void
    {
        $businessA = Business::factory()->create();
        $businessB = Business::factory()->create();

        $userA = User::factory()->create([
            'business_id' => $businessA->id,
            'role' => UserRole::Professional,
        ]);
        $userB = User::factory()->create([
            'business_id' => $businessB->id,
            'role' => UserRole::Professional,
        ]);

        $professionalA = Professional::forceCreate([
            'name' => 'Stefania',
            'business_id' => $businessA->id,
            'user_id' => $userA->id,
        ]);
        $professionalB = Professional::forceCreate([
            'name' => 'Laura',
            'business_id' => $businessB->id,
            'user_id' => $userB->id,
        ]);

        $serviceA = Service::forceCreate([
            'professional_id' => $professionalA->id,
            'business_id' => $businessA->id,
            'name' => 'Semipermanente',
            'duration_minutes' => 60,
            'price' => 50000,
        ]);
        $serviceB = Service::forceCreate([
            'professional_id' => $professionalB->id,
            'business_id' => $businessB->id,
            'name' => 'Esmaltado',
            'duration_minutes' => 30,
            'price' => 30000,
        ]);

        $date = '2026-11-16';

        foreach ([[$professionalA, $serviceA, 'Cliente A'], [$professionalB, $serviceB, 'Cliente B']] as [$professional, $service, $customer]) {
            DB::table('appointments')->insert([
                'professional_id' => $professional->id,
                'service_id' => $service->id,
                'customer_name' => $customer,
                'customer_phone' => '3000000000',
                'starts_at' => "{$date} 09:00:00",
                'ends_at' => "{$date} 10:00:00",
                'status' => 'confirmed',
            ]);
        }

        $this->actingAs($userA)
            ->get("/agenda?date={$date}")
            ->assertOk()
            ->assertSee('Cliente A')
            ->assertDontSee('Cliente B');
    }
}
