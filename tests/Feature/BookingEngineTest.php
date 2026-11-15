<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Holiday;
use App\Models\Professional;
use App\Models\ScheduleException;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BookingEngineTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(array $businessAttributes = []): array
    {
        config(['services.reservas.business_slug' => 'canal-test']);
        $business = Business::factory()->create(['slug' => 'canal-test', ...$businessAttributes]);
        $professional = Professional::create(['name' => 'Stefania', 'business_id' => $business->id]);
        $service = Service::create([
            'name' => 'Semipermanente',
            'duration_minutes' => 60,
            'price' => 50000,
            'professional_id' => $professional->id,
            'business_id' => $business->id,
        ]);

        return [$business, $professional, $service];
    }

    private function reservar(Professional $professional, Service $service, string $startsAt)
    {
        return $this->postJson('/api/appointments', [
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'customer_name' => 'Cliente',
            'customer_phone' => '3000000000',
            'starts_at' => $startsAt,
        ]);
    }

    public function test_availability_follows_configured_work_schedules(): void
    {
        [, $professional, $service] = $this->escenario();
        $fecha = now('America/Bogota')->addDays(3)->toDateString();

        WorkSchedule::create([
            'professional_id' => $professional->id,
            'weekday' => (int) now('America/Bogota')->addDays(3)->dayOfWeekIso - 1,
            'start_time' => '09:00',
            'end_time' => '13:00',
        ]);

        $respuesta = $this->getJson("/api/availability?professional_id={$professional->id}&service_id={$service->id}&date={$fecha}")
            ->assertOk()
            ->json('available');

        $this->assertContains('09:00', $respuesta);
        $this->assertContains('12:00', $respuesta);
        $this->assertNotContains('12:30', $respuesta);
        $this->assertNotContains('15:00', $respuesta);
    }

    public function test_breaks_are_excluded_from_availability(): void
    {
        [, $professional, $service] = $this->escenario();
        $fecha = now('America/Bogota')->addDays(3)->toDateString();
        $weekday = (int) now('America/Bogota')->addDays(3)->dayOfWeekIso - 1;

        WorkSchedule::create(['professional_id' => $professional->id, 'weekday' => $weekday, 'start_time' => '09:00', 'end_time' => '18:00']);
        WorkSchedule::create(['professional_id' => $professional->id, 'weekday' => $weekday, 'start_time' => '13:00', 'end_time' => '14:00', 'is_break' => true]);

        $respuesta = $this->getJson("/api/availability?professional_id={$professional->id}&service_id={$service->id}&date={$fecha}")
            ->assertOk()
            ->json('available');

        $this->assertNotContains('12:30', $respuesta);
        $this->assertNotContains('13:00', $respuesta);
        $this->assertContains('14:00', $respuesta);
    }

    public function test_vacations_and_holidays_block_availability(): void
    {
        [$business, $professional, $service] = $this->escenario();
        $fecha = now('America/Bogota')->addDays(3)->toDateString();

        ScheduleException::create([
            'professional_id' => $professional->id,
            'kind' => 'vacation',
            'starts_on' => $fecha,
            'ends_on' => $fecha,
        ]);

        $this->getJson("/api/availability?professional_id={$professional->id}&service_id={$service->id}&date={$fecha}")
            ->assertOk()
            ->assertJson(['available' => []]);

        ScheduleException::query()->delete();
        Holiday::create(['business_id' => $business->id, 'date' => $fecha]);

        $this->getJson("/api/availability?professional_id={$professional->id}&service_id={$service->id}&date={$fecha}")
            ->assertOk()
            ->assertJson(['available' => []]);
    }

    public function test_extra_exceptions_open_slots_outside_the_schedule(): void
    {
        [, $professional, $service] = $this->escenario();
        $fecha = now('America/Bogota')->addDays(3)->toDateString();
        $weekday = (int) now('America/Bogota')->addDays(3)->dayOfWeekIso - 1;

        WorkSchedule::create(['professional_id' => $professional->id, 'weekday' => $weekday, 'start_time' => '09:00', 'end_time' => '13:00']);
        ScheduleException::create([
            'professional_id' => $professional->id,
            'kind' => 'extra',
            'starts_on' => $fecha,
            'start_time' => '20:00',
            'end_time' => '22:00',
        ]);

        $respuesta = $this->getJson("/api/availability?professional_id={$professional->id}&service_id={$service->id}&date={$fecha}")
            ->assertOk()
            ->json('available');

        $this->assertContains('20:00', $respuesta);
        $this->assertNotContains('15:00', $respuesta);
    }

    public function test_minimum_notice_and_maximum_advance_are_enforced(): void
    {
        [$business, $professional, $service] = $this->escenario([
            'booking_min_notice_minutes' => 2880,
            'booking_max_advance_days' => 7,
        ]);

        $cerca = now('America/Bogota')->addDay()->toDateString();
        $lejos = now('America/Bogota')->addDays(10)->toDateString();

        $this->getJson("/api/availability?professional_id={$professional->id}&service_id={$service->id}&date={$cerca}")
            ->assertOk()
            ->assertJson(['available' => []]);

        $this->getJson("/api/availability?professional_id={$professional->id}&service_id={$service->id}&date={$lejos}")
            ->assertOk()
            ->assertJson(['available' => []]);

        $this->reservar($professional, $service, "{$cerca} 09:00:00")
            ->assertStatus(422);
    }

    public function test_pending_requests_block_the_slot_until_they_expire(): void
    {
        Queue::fake();
        [, $professional, $service] = $this->escenario(['booking_auto_confirm' => false]);
        $fecha = now('America/Bogota')->addDays(3)->toDateString();

        $this->reservar($professional, $service, "{$fecha} 09:00:00")
            ->assertStatus(201);

        $this->assertDatabaseHas('appointments', [
            'status' => AppointmentStatus::Pending->value,
        ]);

        $this->reservar($professional, $service, "{$fecha} 09:00:00")
            ->assertStatus(422);

        $this->travel(31)->minutes();

        $this->artisan('appointments:expire')->assertSuccessful();

        $this->assertDatabaseHas('appointments', ['status' => AppointmentStatus::Expired->value]);

        $this->reservar($professional, $service, "{$fecha} 09:00:00")
            ->assertStatus(201);
    }

    public function test_simultaneous_requests_cannot_double_book(): void
    {
        Queue::fake();
        [, $professional, $service] = $this->escenario();
        $fecha = now('America/Bogota')->addDays(3)->toDateString();

        $this->reservar($professional, $service, "{$fecha} 09:00:00")->assertStatus(201);
        $this->reservar($professional, $service, "{$fecha} 09:00:00")->assertStatus(422);

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_confirmation_follows_the_business_policy(): void
    {
        Queue::fake();
        [, $professional, $service] = $this->escenario();
        $fecha = now('America/Bogota')->addDays(3)->toDateString();

        $this->reservar($professional, $service, "{$fecha} 09:00:00")->assertStatus(201);

        $this->assertDatabaseHas('appointments', [
            'status' => AppointmentStatus::Confirmed->value,
        ]);
    }

    public function test_status_transitions_are_validated(): void
    {
        Queue::fake();
        [$business, $professional, $service] = $this->escenario();
        $user = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);
        $fecha = now('America/Bogota')->addDays(3)->toDateString();
        $cita = Appointment::forceCreate([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'business_id' => $business->id,
            'customer_name' => 'Cliente',
            'customer_phone' => '300',
            'starts_at' => "{$fecha} 09:00:00",
            'ends_at' => "{$fecha} 10:00:00",
            'status' => AppointmentStatus::Confirmed->value,
        ]);

        $this->actingAs($user)->postJson("/appointments/{$cita->id}/confirm")
            ->assertStatus(422);

        $this->actingAs($user)->postJson("/appointments/{$cita->id}/cancel")
            ->assertStatus(200);

        $this->actingAs($user)->postJson("/appointments/{$cita->id}/cancel")
            ->assertStatus(422);

        $this->actingAs($user)->postJson("/appointments/{$cita->id}/confirm")
            ->assertStatus(422);
    }

    public function test_reschedule_moves_the_appointment_and_revalidates(): void
    {
        Queue::fake();
        [$business, $professional, $service] = $this->escenario();
        $user = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);
        $fecha = now('America/Bogota')->addDays(3)->toDateString();

        $cita = Appointment::forceCreate([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'business_id' => $business->id,
            'customer_name' => 'Cliente',
            'customer_phone' => '300',
            'starts_at' => "{$fecha} 09:00:00",
            'ends_at' => "{$fecha} 10:00:00",
            'status' => AppointmentStatus::Confirmed->value,
        ]);
        Appointment::forceCreate([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'business_id' => $business->id,
            'customer_name' => 'Otra',
            'customer_phone' => '301',
            'starts_at' => "{$fecha} 11:00:00",
            'ends_at' => "{$fecha} 12:00:00",
            'status' => AppointmentStatus::Confirmed->value,
        ]);

        $this->actingAs($user)
            ->postJson("/appointments/{$cita->id}/reschedule", ['starts_at' => "{$fecha} 14:00:00"])
            ->assertStatus(200);

        $this->assertDatabaseHas('appointments', [
            'id' => $cita->id,
            'starts_at' => "{$fecha} 14:00:00",
        ]);

        $this->actingAs($user)
            ->postJson("/appointments/{$cita->id}/reschedule", ['starts_at' => "{$fecha} 11:00:00"])
            ->assertStatus(422);
    }

    public function test_appointments_cannot_be_managed_across_businesses(): void
    {
        [$business, $professional, $service] = $this->escenario();
        $otroAdmin = User::factory()->create([
            'business_id' => Business::factory()->create()->id,
            'role' => UserRole::Admin,
        ]);
        $fecha = now('America/Bogota')->addDays(3)->toDateString();
        $cita = Appointment::forceCreate([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'business_id' => $business->id,
            'customer_name' => 'Cliente',
            'customer_phone' => '300',
            'starts_at' => "{$fecha} 09:00:00",
            'ends_at' => "{$fecha} 10:00:00",
            'status' => AppointmentStatus::Confirmed->value,
        ]);

        $this->actingAs($otroAdmin)->postJson("/appointments/{$cita->id}/cancel")
            ->assertStatus(403);
    }

    public function test_availability_falls_back_to_mvp_hours_without_schedules(): void
    {
        [, $professional, $service] = $this->escenario();
        $fecha = now('America/Bogota')->addDays(3)->toDateString();

        $respuesta = $this->getJson("/api/availability?professional_id={$professional->id}&service_id={$service->id}&date={$fecha}")
            ->assertOk()
            ->json('available');

        $this->assertContains('09:00', $respuesta);
        $this->assertNotContains('18:00', $respuesta);
    }

    public function test_the_booking_policy_is_configurable_per_business(): void
    {
        [$business] = $this->escenario();
        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($admin)
            ->patchJson("/businesses/{$business->id}/booking-policy", [
                'booking_min_notice_minutes' => 120,
                'booking_auto_confirm' => false,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'booking_min_notice_minutes' => 120,
            'booking_auto_confirm' => false,
        ]);

        $otroAdmin = User::factory()->create([
            'business_id' => Business::factory()->create()->id,
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($otroAdmin)
            ->patchJson("/businesses/{$business->id}/booking-policy", ['booking_min_notice_minutes' => 5])
            ->assertStatus(403);
    }
}
