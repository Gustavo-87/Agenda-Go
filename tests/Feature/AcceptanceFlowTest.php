<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Prueba integral de aceptación del ciclo operativo: alta de
 * negocio, membresía, configuración, reserva, asistencia, pago y
 * reporte, con suspensión final.
 */
class AcceptanceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_complete_operational_cycle_of_a_business(): void
    {
        Queue::fake();
        config(['services.reservas.business_slug' => 'viglam']);
        $fecha = now('America/Bogota')->addDays(3)->toDateString();
        $weekday = (int) now('America/Bogota')->addDays(3)->dayOfWeekIso - 1;

        // 1. Alta y membresía por parte del superadministrador.
        $super = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $businessId = $this->actingAs($super)
            ->postJson('/admin/businesses', ['name' => 'Viglam', 'slug' => 'viglam'])
            ->assertStatus(201)
            ->json('business_id');

        $paymentId = $this->actingAs($super)
            ->postJson("/admin/businesses/{$businessId}/memberships", [
                'starts_on' => now()->toDateString(),
                'ends_on' => now()->addMonth()->toDateString(),
                'amount' => 150000,
                'paid_on' => now()->toDateString(),
            ])
            ->assertStatus(201)
            ->json('payment_id');

        $this->actingAs($super)
            ->postJson("/admin/membership-payments/{$paymentId}/verify")
            ->assertStatus(200);

        $this->assertDatabaseHas('businesses', ['id' => $businessId, 'active' => true]);

        // 2. Configuración por parte del administrador del negocio.
        $admin = User::factory()->create([
            'business_id' => $businessId,
            'role' => UserRole::Admin,
        ]);

        $professionalId = $this->actingAs($admin)
            ->postJson('/professionals', ['name' => 'Stefania', 'phone' => '3100000000'])
            ->assertStatus(201)
            ->json('professional_id');

        $serviceId = $this->actingAs($admin)
            ->postJson("/businesses/{$businessId}/services", [
                'name' => 'Semipermanente',
                'duration_minutes' => 60,
                'price' => 50000,
                'professional_id' => $professionalId,
            ])
            ->assertStatus(201)
            ->json('service_id');

        $this->actingAs($admin)
            ->postJson("/professionals/{$professionalId}/schedules", [
                'weekday' => $weekday,
                'start_time' => '09:00',
                'end_time' => '18:00',
            ])
            ->assertStatus(201);

        // 3. Reserva pública y ciclo de la cita.
        $appointmentId = $this->postJson('/api/appointments', [
            'professional_id' => $professionalId,
            'service_id' => $serviceId,
            'customer_name' => 'Carla',
            'customer_phone' => '3000000000',
            'starts_at' => "{$fecha} 09:00:00",
        ])
            ->assertStatus(201)
            ->json('appointment_id');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'status' => AppointmentStatus::Confirmed->value,
        ]);

        $this->actingAs($admin)
            ->postJson("/appointments/{$appointmentId}/attend")
            ->assertStatus(200);

        // 4. Pago del servicio y reporte.
        $servicePaymentId = $this->actingAs($admin)
            ->postJson("/businesses/{$businessId}/payments", [
                'appointment_id' => $appointmentId,
                'amount' => 50000,
                'paid_on' => $fecha,
                'method' => 'efectivo',
            ])
            ->assertStatus(201)
            ->json('payment_id');

        $this->actingAs($admin)->postJson("/payments/{$servicePaymentId}/verify")->assertStatus(200);

        $this->actingAs($admin)
            ->getJson("/businesses/{$businessId}/reports/summary?from={$fecha}&to={$fecha}")
            ->assertOk()
            ->assertJsonPath('ingresos_servicios', 50000);

        // 5. La suspensión bloquea nuevas reservas y conserva el histórico.
        $this->actingAs($super)
            ->postJson("/admin/businesses/{$businessId}/suspend")
            ->assertStatus(200);

        $this->postJson('/api/appointments', [
            'professional_id' => $professionalId,
            'service_id' => $serviceId,
            'customer_name' => 'Carla',
            'customer_phone' => '3000000000',
            'starts_at' => "{$fecha} 11:00:00",
        ])->assertStatus(422);

        $this->assertDatabaseHas('appointments', ['id' => $appointmentId]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'negocio.suspendido']);
    }
}
