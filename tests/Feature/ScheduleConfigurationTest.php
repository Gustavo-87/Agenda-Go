<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Professional;
use App\Models\ScheduleException;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleConfigurationTest extends TestCase
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

    public function test_an_admin_can_configure_split_shifts_and_breaks(): void
    {
        $business = Business::factory()->create();
        $professional = $this->professionalOf($business);
        $admin = $this->adminOf($business);

        foreach ([['09:00', '13:00', false], ['16:00', '19:00', false], ['13:00', '14:00', true]] as [$start, $end, $isBreak]) {
            $this->actingAs($admin)
                ->postJson("/professionals/{$professional->id}/schedules", [
                    'weekday' => 0,
                    'start_time' => $start,
                    'end_time' => $end,
                    'is_break' => $isBreak,
                ])
                ->assertStatus(201);
        }

        $this->assertDatabaseHas('work_schedules', [
            'professional_id' => $professional->id,
            'weekday' => 0,
            'start_time' => '16:00',
            'is_break' => false,
        ]);
        $this->assertDatabaseHas('work_schedules', [
            'professional_id' => $professional->id,
            'start_time' => '13:00',
            'is_break' => true,
        ]);
    }

    public function test_a_schedule_cannot_end_before_it_starts(): void
    {
        $business = Business::factory()->create();
        $professional = $this->professionalOf($business);

        $this->actingAs($this->adminOf($business))
            ->postJson("/professionals/{$professional->id}/schedules", [
                'weekday' => 0,
                'start_time' => '13:00',
                'end_time' => '09:00',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('end_time');
    }

    public function test_an_admin_cannot_configure_schedules_of_another_business(): void
    {
        $ownBusiness = Business::factory()->create();
        $otherProfessional = $this->professionalOf(Business::factory()->create(), 'Laura');
        $admin = $this->adminOf($ownBusiness);

        $this->actingAs($admin)
            ->postJson("/professionals/{$otherProfessional->id}/schedules", [
                'weekday' => 0,
                'start_time' => '09:00',
                'end_time' => '13:00',
            ])
            ->assertStatus(403);

        $this->assertDatabaseCount('work_schedules', 0);
    }

    public function test_schedules_can_only_be_deleted_within_the_business(): void
    {
        $ownBusiness = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $ownProfessional = $this->professionalOf($ownBusiness);
        $otherProfessional = $this->professionalOf($otherBusiness, 'Laura');

        $ownSchedule = WorkSchedule::create([
            'professional_id' => $ownProfessional->id,
            'weekday' => 0,
            'start_time' => '09:00',
            'end_time' => '13:00',
        ]);
        $otherSchedule = WorkSchedule::create([
            'professional_id' => $otherProfessional->id,
            'weekday' => 0,
            'start_time' => '09:00',
            'end_time' => '13:00',
        ]);

        $admin = $this->adminOf($ownBusiness);

        $this->actingAs($admin)
            ->deleteJson("/schedules/{$otherSchedule->id}")
            ->assertStatus(403);

        $this->actingAs($admin)
            ->deleteJson("/schedules/{$ownSchedule->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('work_schedules', ['id' => $ownSchedule->id]);
        $this->assertDatabaseHas('work_schedules', ['id' => $otherSchedule->id]);
    }

    public function test_an_admin_can_register_vacations_blocks_and_extra_shifts(): void
    {
        $business = Business::factory()->create();
        $professional = $this->professionalOf($business);
        $admin = $this->adminOf($business);

        $this->actingAs($admin)
            ->postJson("/professionals/{$professional->id}/exceptions", [
                'kind' => 'vacation',
                'starts_on' => '2026-12-24',
                'ends_on' => '2027-01-02',
            ])->assertStatus(201);

        $this->actingAs($admin)
            ->postJson("/professionals/{$professional->id}/exceptions", [
                'kind' => 'block',
                'starts_on' => '2026-12-10',
            ])->assertStatus(201);

        $this->actingAs($admin)
            ->postJson("/professionals/{$professional->id}/exceptions", [
                'kind' => 'extra',
                'starts_on' => '2026-12-07',
                'start_time' => '10:00',
                'end_time' => '14:00',
            ])->assertStatus(201);

        $this->assertDatabaseHas('schedule_exceptions', [
            'professional_id' => $professional->id,
            'kind' => 'vacation',
            'ends_on' => '2027-01-02',
        ]);
        $this->assertDatabaseHas('schedule_exceptions', [
            'professional_id' => $professional->id,
            'kind' => 'extra',
            'start_time' => '10:00',
        ]);
    }

    public function test_extra_exceptions_require_working_hours(): void
    {
        $business = Business::factory()->create();
        $professional = $this->professionalOf($business);

        $this->actingAs($this->adminOf($business))
            ->postJson("/professionals/{$professional->id}/exceptions", [
                'kind' => 'extra',
                'starts_on' => '2026-12-07',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_time', 'end_time']);
    }

    public function test_exceptions_are_isolated_between_businesses(): void
    {
        $ownBusiness = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $otherProfessional = $this->professionalOf($otherBusiness, 'Laura');
        $admin = $this->adminOf($ownBusiness);

        $this->actingAs($admin)
            ->postJson("/professionals/{$otherProfessional->id}/exceptions", [
                'kind' => 'block',
                'starts_on' => '2026-12-10',
            ])
            ->assertStatus(403);

        $exception = ScheduleException::create([
            'professional_id' => $otherProfessional->id,
            'kind' => 'block',
            'starts_on' => '2026-12-10',
        ]);

        $this->actingAs($admin)
            ->deleteJson("/exceptions/{$exception->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('schedule_exceptions', ['id' => $exception->id]);
    }

    public function test_a_super_admin_cannot_configure_schedules_of_any_business(): void
    {
        $professional = $this->professionalOf(Business::factory()->create(), 'Laura');
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($superAdmin)
            ->postJson("/professionals/{$professional->id}/schedules", [
                'weekday' => 1,
                'start_time' => '09:00',
                'end_time' => '18:00',
            ])
            ->assertStatus(403);
    }
}
