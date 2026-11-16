<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Membership;
use App\Models\Professional;
use App\Models\ScheduleException;
use App\Models\Service;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\ReservaWhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PendingRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private function adminOf(Business $business): User
    {
        return User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);
    }

    public function test_commercial_consent_is_independent_evidenced_and_revocable(): void
    {
        $business = Business::factory()->create();
        $admin = $this->adminOf($business);
        $customer = Customer::create([
            'business_id' => $business->id,
            'name' => 'Carla',
            'phone' => '3000000000',
        ]);

        // Consentimiento de datos necesario para reservar: no activa el comercial.
        $this->actingAs($admin)
            ->postJson("/customers/{$customer->id}/consent", ['data_consent' => 1])
            ->assertStatus(200);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'data_consent' => true,
            'commercial_consent' => false,
        ]);

        // Consentimiento comercial independiente, con evidencia.
        $this->actingAs($admin)
            ->postJson("/customers/{$customer->id}/commercial-consent", ['commercial_consent' => 1])
            ->assertStatus(200);

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'commercial_consent' => true]);
        $this->assertNotNull($customer->fresh()->commercial_consent_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'consentimiento.comercial.otorgado']);

        // Revocación: se conserva la evidencia del otorgamiento.
        $otorgadoEn = $customer->fresh()->commercial_consent_at;

        $this->actingAs($admin)
            ->postJson("/customers/{$customer->id}/commercial-consent", ['commercial_consent' => 0])
            ->assertStatus(200);

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'commercial_consent' => false]);
        $this->assertTrue($customer->fresh()->commercial_consent_at->equalTo($otorgadoEn));
        $this->assertDatabaseHas('audit_logs', ['action' => 'consentimiento.comercial.revocado']);

        // Aislamiento multiempresa.
        $foreign = $this->adminOf(Business::factory()->create());
        $this->actingAs($foreign)
            ->postJson("/customers/{$customer->id}/commercial-consent", ['commercial_consent' => 1])
            ->assertStatus(403);
    }

    public function test_retention_report_never_deletes_data(): void
    {
        $business = Business::factory()->create();
        Membership::create([
            'business_id' => $business->id,
            'starts_on' => now()->subDays(120)->toDateString(),
            'ends_on' => now()->subDays(100)->toDateString(),
            'amount' => 150000,
            'status' => 'cancelled',
        ]);
        $customer = Customer::create([
            'business_id' => $business->id,
            'name' => 'Carla',
            'phone' => '3000000000',
        ]);

        $this->artisan('retention:report')
            ->expectsOutputToContain('Negocios con retención vencida: 1')
            ->expectsOutputToContain('no se elimina automáticamente')
            ->assertSuccessful();

        $this->assertDatabaseHas('businesses', ['id' => $business->id]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
        $this->assertDatabaseHas('memberships', ['business_id' => $business->id, 'status' => 'cancelled']);
    }

    public function test_messaging_consumption_panel_and_explicit_overage_authorization(): void
    {
        $business = Business::factory()->create();
        $admin = $this->adminOf($business);
        $super = User::factory()->create(['role' => UserRole::SuperAdmin]);

        foreach (range(1, 3) as $i) {
            WhatsAppMessage::create([
                'business_id' => $business->id,
                'direction' => 'outbound',
                'phone' => '300',
                'body' => 'Mensaje '.$i,
                'status' => 'sent',
            ]);
        }
        WhatsAppMessage::create([
            'business_id' => $business->id,
            'direction' => 'inbound',
            'phone' => '300',
            'body' => 'hola',
            'status' => 'processed',
        ]);

        $this->actingAs($admin)
            ->get('/configuracion')
            ->assertOk()
            ->assertSee('Incluido')
            ->assertSee('Por definir')
            ->assertSee('Utilizado')
            ->assertSee('Excedentes autorizados');

        // Solo el superadministrador autoriza cuotas, de forma expresa.
        $this->actingAs($admin)
            ->patchJson("/admin/businesses/{$business->id}/messaging", ['messaging_included' => 100])
            ->assertStatus(403);

        $this->actingAs($super)
            ->patchJson("/admin/businesses/{$business->id}/messaging", [
                'messaging_included' => 100,
                'messaging_overage_authorized' => 20,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'messaging_included' => 100,
            'messaging_overage_authorized' => 20,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'mensajeria.cuota.autorizada']);

        // Incluido 100, utilizados 3 (solo salientes): disponible 97.
        $this->actingAs($admin)
            ->get('/configuracion')
            ->assertOk()
            ->assertSee('97');
    }

    public function test_imported_holidays_block_availability_and_extra_exceptions_open_it(): void
    {
        Queue::fake();
        config(['services.reservas.business_slug' => 'canal']);
        $business = Business::factory()->create(['slug' => 'canal']);
        $professional = Professional::create(['name' => 'Stefania', 'business_id' => $business->id]);
        $service = Service::create([
            'name' => 'Semipermanente',
            'duration_minutes' => 60,
            'price' => 50000,
            'professional_id' => $professional->id,
            'business_id' => $business->id,
        ]);

        $fecha = now('America/Bogota')->addDays(3)->toDateString();
        $archivo = tempnam(sys_get_temp_dir(), 'festivos');
        file_put_contents($archivo, "{$fecha},Festivo de prueba\n");

        $this->artisan('holidays:import', ['file' => $archivo, '--business' => 'canal'])
            ->expectsOutputToContain('Festivos importados: 1')
            ->assertSuccessful();

        $this->assertDatabaseHas('holidays', ['business_id' => $business->id, 'date' => $fecha]);

        // Bloqueo por defecto.
        $this->getJson("/api/availability?professional_id={$professional->id}&service_id={$service->id}&date={$fecha}")
            ->assertOk()
            ->assertJson(['available' => []]);

        // Excepción por profesional: horario extra abre el festivo.
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
    }

    public function test_whatsapp_replies_confirm_and_cancel_appointments(): void
    {
        $businessA = Business::factory()->create();
        $businessB = Business::factory()->create();
        $professionalA = Professional::create(['name' => 'Stefania', 'business_id' => $businessA->id]);
        $professionalB = Professional::create(['name' => 'Laura', 'business_id' => $businessB->id]);

        $citas = [];
        foreach ([[$professionalA, $businessA], [$professionalB, $businessB]] as [$professional, $business]) {
            $service = Service::create([
                'name' => 'Servicio',
                'duration_minutes' => 60,
                'price' => 50000,
                'professional_id' => $professional->id,
                'business_id' => $business->id,
            ]);

            $citas[$business->id] = Appointment::forceCreate([
                'professional_id' => $professional->id,
                'service_id' => $service->id,
                'business_id' => $business->id,
                'customer_name' => 'Carla',
                'customer_phone' => '3000000000',
                'starts_at' => now('America/Bogota')->addDays(2)->format('Y-m-d 09:00:00'),
                'ends_at' => now('America/Bogota')->addDays(2)->format('Y-m-d 10:00:00'),
                'status' => AppointmentStatus::Pending->value,
                'pending_expires_at' => now('America/Bogota')->addHour()->format('Y-m-d H:i:s'),
            ]);
        }

        $servicio = app(ReservaWhatsAppService::class);

        $respuesta = $servicio->procesar('3000000000', 'confirmar', $businessA->id);
        $this->assertStringContainsString('confirmada', $respuesta);
        $this->assertDatabaseHas('appointments', ['id' => $citas[$businessA->id]->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('appointments', ['id' => $citas[$businessB->id]->id, 'status' => 'pending']);

        $respuesta = $servicio->procesar('3000000000', 'Quiero cancelar mi cita', $businessA->id);
        $this->assertStringContainsString('Selecciona la cita', $respuesta);

        $respuesta = $servicio->procesar('3000000000', '1', $businessA->id);
        $this->assertStringContainsString('Confirmar cancelación', $respuesta);

        $respuesta = $servicio->procesar('3000000000', '1', $businessA->id);
        $this->assertStringContainsString('cancelada', $respuesta);
        $this->assertDatabaseHas('appointments', ['id' => $citas[$businessA->id]->id, 'status' => 'cancelled']);

        $respuesta = $servicio->procesar('3111111111', 'confirmar', $businessA->id);
        $this->assertStringContainsString('No encontramos citas', $respuesta);
    }
}
