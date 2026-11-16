<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\DisponibilidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Pruebas funcionales del motor de reservas con el negocio de prueba
 * «Barbería Demo» (no toca Viglam ni envía mensajes reales).
 */
class BarberiaDemoBookingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Business, 1: User, 2: Professional, 3: Service, 4: Service, 5: string}
     */
    private function barberiaDemo(array $businessAttributes = []): array
    {
        config(['services.reservas.business_slug' => 'barberia-demo']);

        $business = Business::factory()->create([
            'slug' => 'barberia-demo',
            'name' => 'Barbería Demo',
            'active' => true,
            'booking_min_notice_minutes' => 0,
            ...$businessAttributes,
        ]);

        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);

        $carlos = Professional::create([
            'name' => 'Carlos',
            'phone' => '3100000000',
            'business_id' => $business->id,
            'active' => true,
        ]);

        $corte = Service::create([
            'name' => 'Corte',
            'duration_minutes' => 60,
            'price' => 30000,
            'professional_id' => $carlos->id,
            'business_id' => $business->id,
        ]);

        $combo = Service::create([
            'name' => 'Corte y barba',
            'duration_minutes' => 120,
            'price' => 55000,
            'professional_id' => $carlos->id,
            'business_id' => $business->id,
        ]);

        $fecha = now('America/Bogota')->addDays(3)->toDateString();
        $weekday = (int) now('America/Bogota')->addDays(3)->dayOfWeekIso - 1;

        // Jornada partida 08:00–12:00 y 14:00–18:00 con descanso 11:00–11:30.
        foreach ([['08:00', '12:00'], ['14:00', '18:00']] as [$inicio, $fin]) {
            WorkSchedule::create([
                'professional_id' => $carlos->id,
                'weekday' => $weekday,
                'start_time' => $inicio,
                'end_time' => $fin,
            ]);
        }

        WorkSchedule::create([
            'professional_id' => $carlos->id,
            'weekday' => $weekday,
            'start_time' => '11:00',
            'end_time' => '11:30',
            'is_break' => true,
        ]);

        return [$business, $admin, $carlos, $corte, $combo, $fecha];
    }

    public function test_configuration_of_professionals_services_and_schedules_is_complete(): void
    {
        [$business, , $carlos, $corte, $combo] = $this->barberiaDemo();

        $this->assertSame(1, $business->professionals()->count());
        $this->assertSame(2, $business->services()->count());
        $this->assertSame(3, $carlos->workSchedules()->count());
        $this->assertTrue((bool) $corte->fresh()->active);
        $this->assertSame(60, $corte->duration_minutes);
        $this->assertSame(120, $combo->duration_minutes);
    }

    public function test_availability_respects_shifts_breaks_and_service_durations(): void
    {
        [, , $carlos, $corte, $combo, $fecha] = $this->barberiaDemo();
        $disponibilidad = app(DisponibilidadService::class);

        $slotsCorte = $disponibilidad->slots($carlos, $corte, $fecha);

        $this->assertContains('08:00', $slotsCorte);
        $this->assertContains('10:00', $slotsCorte);
        $this->assertNotContains('10:30', $slotsCorte); // solapa el descanso
        $this->assertNotContains('11:00', $slotsCorte); // descanso
        $this->assertNotContains('13:00', $slotsCorte); // entre jornadas
        $this->assertContains('14:00', $slotsCorte);
        $this->assertNotContains('17:30', $slotsCorte); // terminaría 18:30

        $slotsCombo = $disponibilidad->slots($carlos, $combo, $fecha);

        $this->assertContains('16:00', $slotsCombo); // 16:00–18:00 exacto
        $this->assertNotContains('16:30', $slotsCombo); // terminaría 18:30
    }

    public function test_booking_blocks_occupied_slots_and_double_booking(): void
    {
        Queue::fake();
        [, , $carlos, $corte, , $fecha] = $this->barberiaDemo();

        $this->postJson('/api/appointments', [
            'professional_id' => $carlos->id,
            'service_id' => $corte->id,
            'customer_name' => 'Andrés',
            'customer_phone' => '3000000001',
            'starts_at' => "{$fecha} 09:00:00",
        ])->assertStatus(201);

        // El horario ocupado y sus solapamientos desaparecen.
        $respuesta = $this->getJson("/api/availability?professional_id={$carlos->id}&service_id={$corte->id}&date={$fecha}")
            ->assertOk()
            ->json('available');

        $this->assertNotContains('09:00', $respuesta);
        $this->assertNotContains('09:30', $respuesta);

        $this->postJson('/api/appointments', [
            'professional_id' => $carlos->id,
            'service_id' => $corte->id,
            'customer_name' => 'Beatriz',
            'customer_phone' => '3000000002',
            'starts_at' => "{$fecha} 09:00:00",
        ])->assertStatus(422);

        // Reservas consecutivas sobre el mismo slot: solo una gana.
        $this->postJson('/api/appointments', [
            'professional_id' => $carlos->id,
            'service_id' => $corte->id,
            'customer_name' => 'Carla',
            'customer_phone' => '3000000003',
            'starts_at' => "{$fecha} 10:00:00",
        ])->assertStatus(201);

        $this->postJson('/api/appointments', [
            'professional_id' => $carlos->id,
            'service_id' => $corte->id,
            'customer_name' => 'Diana',
            'customer_phone' => '3000000004',
            'starts_at' => "{$fecha} 10:00:00",
        ])->assertStatus(422);

        $this->assertSame(2, Appointment::count());
    }

    public function test_longer_services_block_the_full_duration(): void
    {
        Queue::fake();
        [, , $carlos, , $combo, $fecha] = $this->barberiaDemo();

        $this->postJson('/api/appointments', [
            'professional_id' => $carlos->id,
            'service_id' => $combo->id,
            'customer_name' => 'Andrés',
            'customer_phone' => '3000000001',
            'starts_at' => "{$fecha} 08:00:00",
        ])->assertStatus(201);

        $respuesta = $this->getJson("/api/availability?professional_id={$carlos->id}&service_id={$combo->id}&date={$fecha}")
            ->assertOk()
            ->json('available');

        $this->assertNotContains('08:00', $respuesta);
        $this->assertNotContains('09:00', $respuesta); // solaparía la reserva 08:00–10:00
        $this->assertNotContains('10:00', $respuesta); // solaparía el descanso 11:00–11:30
        $this->assertContains('14:00', $respuesta); // tarde libre

        $this->postJson('/api/appointments', [
            'professional_id' => $carlos->id,
            'service_id' => $combo->id,
            'customer_name' => 'Beatriz',
            'customer_phone' => '3000000002',
            'starts_at' => "{$fecha} 08:30:00",
        ])->assertStatus(422);
    }

    public function test_pending_requests_can_be_confirmed_rescheduled_cancelled_and_expire(): void
    {
        Queue::fake();
        [$business, $admin, $carlos, $corte, , $fecha] = $this->barberiaDemo(['booking_auto_confirm' => false]);

        $this->postJson('/api/appointments', [
            'professional_id' => $carlos->id,
            'service_id' => $corte->id,
            'customer_name' => 'Andrés',
            'customer_phone' => '3000000001',
            'starts_at' => "{$fecha} 09:00:00",
        ])->assertStatus(201);

        $pendiente = Appointment::firstOrFail();
        $this->assertSame(AppointmentStatus::Pending->value, $pendiente->status);

        // Expiración de la solicitud (2 horas o antes del inicio).
        $this->travel(121)->minutes();
        $this->artisan('appointments:expire')->assertSuccessful();
        $this->assertSame(AppointmentStatus::Expired->value, $pendiente->fresh()->status);

        // El horario vuelve a estar disponible tras expirar.
        $this->postJson('/api/appointments', [
            'professional_id' => $carlos->id,
            'service_id' => $corte->id,
            'customer_name' => 'Beatriz',
            'customer_phone' => '3000000002',
            'starts_at' => "{$fecha} 09:00:00",
        ])->assertStatus(201);

        $confirmada = Appointment::latest('id')->first();
        $this->actingAs($admin)->postJson("/appointments/{$confirmada->id}/confirm")->assertStatus(200);
        $this->assertSame(AppointmentStatus::Confirmed->value, $confirmada->fresh()->status);

        // Reprogramación y conflicto.
        $this->actingAs($admin)
            ->postJson("/appointments/{$confirmada->id}/reschedule", ['starts_at' => "{$fecha} 14:00:00"])
            ->assertStatus(200);

        $this->assertSame("{$fecha} 14:00:00", $confirmada->fresh()->starts_at);

        $this->actingAs($admin)
            ->postJson("/appointments/{$confirmada->id}/reschedule", ['starts_at' => "{$fecha} 15:00:00"])
            ->assertStatus(200);

        $this->actingAs($admin)
            ->postJson("/appointments/{$confirmada->id}/reschedule", ['starts_at' => "{$fecha} 15:30:00"])
            ->assertStatus(422); // solapa 15:00–16:00

        // Cancelación y liberación del horario.
        $this->actingAs($admin)
            ->postJson("/appointments/{$confirmada->id}/cancel")
            ->assertStatus(200);

        $this->assertSame(AppointmentStatus::Cancelled->value, $confirmada->fresh()->status);

        $respuesta = $this->getJson("/api/availability?professional_id={$carlos->id}&service_id={$corte->id}&date={$fecha}")
            ->assertOk()
            ->json('available');

        $this->assertContains('15:00', $respuesta);
    }

    public function test_cross_business_isolation_is_enforced(): void
    {
        Queue::fake();
        [$business, $admin, $carlos, $corte, , $fecha] = $this->barberiaDemo();

        $otroNegocio = Business::factory()->create(['slug' => 'negocio-vecino', 'active' => true]);
        $otroProfesional = Professional::create(['name' => 'Lucía', 'business_id' => $otroNegocio->id]);
        $otroServicio = Service::create([
            'name' => 'Servicio vecino',
            'duration_minutes' => 60,
            'price' => 10000,
            'professional_id' => $otroProfesional->id,
            'business_id' => $otroNegocio->id,
        ]);

        // Mezclar profesional y servicio de distintos negocios.
        $this->postJson('/api/appointments', [
            'professional_id' => $carlos->id,
            'service_id' => $otroServicio->id,
            'customer_name' => 'Andrés',
            'customer_phone' => '3000000001',
            'starts_at' => "{$fecha} 09:00:00",
        ])->assertStatus(422);

        // El canal del negocio no reserva recursos ajenos.
        $this->postJson('/api/appointments', [
            'professional_id' => $otroProfesional->id,
            'service_id' => $otroServicio->id,
            'customer_name' => 'Andrés',
            'customer_phone' => '3000000001',
            'starts_at' => "{$fecha} 09:00:00",
        ])->assertStatus(422);

        // Las acciones de personal no cruzan de negocio.
        $cita = Appointment::forceCreate([
            'professional_id' => $otroProfesional->id,
            'service_id' => $otroServicio->id,
            'business_id' => $otroNegocio->id,
            'customer_name' => 'Vecina',
            'customer_phone' => '3000000009',
            'starts_at' => "{$fecha} 09:00:00",
            'ends_at' => "{$fecha} 10:00:00",
            'status' => AppointmentStatus::Confirmed->value,
        ]);

        $this->actingAs($admin)
            ->postJson("/appointments/{$cita->id}/cancel")
            ->assertStatus(403);
    }
}
