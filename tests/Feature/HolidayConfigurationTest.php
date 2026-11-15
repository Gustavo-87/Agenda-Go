<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HolidayConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private function adminOf(Business $business): User
    {
        return User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);
    }

    public function test_an_admin_can_configure_holidays_for_their_business(): void
    {
        $business = Business::factory()->create();

        $this->actingAs($this->adminOf($business))
            ->postJson("/businesses/{$business->id}/holidays", [
                'date' => '2026-12-08',
                'name' => 'Inmaculada Concepción',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('holidays', [
            'business_id' => $business->id,
            'date' => '2026-12-08',
            'name' => 'Inmaculada Concepción',
        ]);
    }

    public function test_a_holiday_date_is_unique_per_business(): void
    {
        $business = Business::factory()->create();
        $admin = $this->adminOf($business);

        $this->actingAs($admin)
            ->postJson("/businesses/{$business->id}/holidays", ['date' => '2026-12-08'])
            ->assertStatus(201);

        $this->actingAs($admin)
            ->postJson("/businesses/{$business->id}/holidays", ['date' => '2026-12-08'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('date');
    }

    public function test_holidays_are_isolated_between_businesses(): void
    {
        $ownBusiness = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $admin = $this->adminOf($ownBusiness);

        $this->actingAs($admin)
            ->postJson("/businesses/{$otherBusiness->id}/holidays", ['date' => '2026-12-08'])
            ->assertStatus(403);

        $otherHoliday = Holiday::create([
            'business_id' => $otherBusiness->id,
            'date' => '2026-12-25',
        ]);

        $this->actingAs($admin)
            ->deleteJson("/holidays/{$otherHoliday->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('holidays', ['id' => $otherHoliday->id]);

        $this->actingAs($admin)
            ->getJson("/businesses/{$ownBusiness->id}/holidays")
            ->assertOk()
            ->assertJsonMissing(['date' => '2026-12-25']);
    }

    public function test_an_admin_can_delete_a_holiday_of_their_business(): void
    {
        $business = Business::factory()->create();
        $holiday = Holiday::create([
            'business_id' => $business->id,
            'date' => '2026-12-08',
        ]);

        $this->actingAs($this->adminOf($business))
            ->deleteJson("/holidays/{$holiday->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('holidays', ['id' => $holiday->id]);
    }

    public function test_a_super_admin_cannot_configure_holidays_of_any_business(): void
    {
        $business = Business::factory()->create();
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($superAdmin)
            ->postJson("/businesses/{$business->id}/holidays", ['date' => '2026-12-08'])
            ->assertStatus(403);
    }
}
