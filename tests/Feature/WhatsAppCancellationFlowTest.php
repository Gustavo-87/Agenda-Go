<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Jobs\EnviarNotificacionWhatsApp;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Professional;
use App\Models\Service;
use App\Services\DisponibilidadService;
use App\Services\ReservaWhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Flujo guiado de cancelación por WhatsApp: identificación por
 * teléfono, selección de cita, confirmación y liberación.
 */
class WhatsAppCancellationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(): array
    {
        $business = Business::factory()->create([
            'name' => 'Barbería Demo',
            'active' => true,
            'booking_min_notice_minutes' => 0,
        ]);
        $professional = Professional::create([
            'name' => 'Carlos',
            'phone' => '3100000000',
            'business_id' => $business->id,
        ]);
        $service = Service::create([
            'name' => 'Corte',
            'duration_minutes' => 60,
            'price' => 30000,
            'professional_id' => $professional->id,
            'business_id' => $business->id,
        ]);

        return [$business, $professional, $service];
    }

    private function crearCita(Business $business, Professional $professional, Service $service, string $fechaHora, string $estado = 'confirmed'): Appointment
    {
        return Appointment::forceCreate([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'business_id' => $business->id,
            'customer_name' => 'Carla',
            'customer_phone' => '3000000000',
            'starts_at' => $fechaHora,
            'ends_at' => Carbon::parse($fechaHora)->addHour()->format('Y-m-d H:i:s'),
            'status' => $estado,
        ]);
    }

    public function test_cancel_flow_lists_selects_confirms_and_notifies(): void
    {
        Queue::fake();
        [$business, $professional, $service] = $this->escenario();
        $fecha = now('America/Bogota')->addDays(2)->toDateString();

        $primera = $this->crearCita($business, $professional, $service, "{$fecha} 09:00:00");
        $segunda = $this->crearCita($business, $professional, $service, "{$fecha} 14:00:00");

        $servicio = app(ReservaWhatsAppService::class);

        // Intención en lenguaje natural.
        $respuesta = $servicio->procesar('3000000000', 'Quiero cancelar mi cita', $business->id);
        $this->assertStringContainsString('Selecciona la cita', $respuesta);
        $this->assertStringContainsString('9:00 a. m.', $respuesta);
        $this->assertStringContainsString('2:00 p. m.', $respuesta);

        // Selección de la segunda cita.
        $respuesta = $servicio->procesar('3000000000', '2', $business->id);
        $this->assertStringContainsString('Confirmar cancelación', $respuesta);
        $this->assertStringContainsString('2:00 p. m.', $respuesta);

        // La disponibilidad no se libera antes de confirmar.
        $this->assertSame(AppointmentStatus::Confirmed->value, $segunda->fresh()->status);

        // Confirmación: se cancela, se libera y se notifica.
        $respuesta = $servicio->procesar('3000000000', '1', $business->id);
        $this->assertStringContainsString('cancelada', $respuesta);

        $this->assertSame(AppointmentStatus::Cancelled->value, $segunda->fresh()->status);
        $this->assertSame(AppointmentStatus::Confirmed->value, $primera->fresh()->status);

        $disponibles = app(DisponibilidadService::class)->slots($professional, $service, $fecha);
        $this->assertContains('14:00', $disponibles);
        $this->assertNotContains('09:00', $disponibles);

        Queue::assertPushed(EnviarNotificacionWhatsApp::class);
        $this->assertDatabaseHas('app_notifications', ['type' => 'cita']);
    }

    public function test_cancel_without_appointments_is_reported_and_idempotent(): void
    {
        [$business] = $this->escenario();
        $servicio = app(ReservaWhatsAppService::class);

        $respuesta = $servicio->procesar('3111111111', 'cancelar mi cita', $business->id);
        $this->assertStringContainsString('No encontramos citas próximas', $respuesta);

        // Duplicado: repetir la solicitud no crea estados ni cancela nada.
        $respuesta = $servicio->procesar('3111111111', 'Quiero cancelar mi cita', $business->id);
        $this->assertStringContainsString('No encontramos citas próximas', $respuesta);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_the_cancel_flow_can_be_aborted_back_to_the_menu(): void
    {
        [$business, $professional, $service] = $this->escenario();
        $fecha = now('America/Bogota')->addDays(2)->toDateString();
        $cita = $this->crearCita($business, $professional, $service, "{$fecha} 09:00:00");
        $servicio = app(ReservaWhatsAppService::class);

        $this->assertStringContainsString('0. Volver al menú principal', $servicio->procesar('3000000000', 'cancelar', $business->id));

        $this->assertStringContainsString('Elige una profesional', $servicio->procesar('3000000000', '0', $business->id));

        $respuesta = $servicio->procesar('3000000000', 'cancelar mi cita', $business->id);
        $this->assertStringContainsString('Selecciona la cita', $respuesta);

        $respuesta = $servicio->procesar('3000000000', '1', $business->id);
        $this->assertStringContainsString('Confirmar cancelación', $respuesta);

        $respuesta = $servicio->procesar('3000000000', '2', $business->id);
        $this->assertStringContainsString('Elige una profesional', $respuesta);

        $this->assertSame(AppointmentStatus::Confirmed->value, $cita->fresh()->status);
    }

    public function test_invalid_options_reprompt_and_policies_are_respected(): void
    {
        [$business, $professional, $service] = $this->escenario();
        $fecha = now('America/Bogota')->addDays(2)->toDateString();

        $cita = $this->crearCita($business, $professional, $service, "{$fecha} 09:00:00");
        $expirada = $this->crearCita($business, $professional, $service, "{$fecha} 14:00:00", AppointmentStatus::Expired->value);

        $servicio = app(ReservaWhatsAppService::class);

        $respuesta = $servicio->procesar('3000000000', 'cancelar cita', $business->id);
        $this->assertStringContainsString('Selecciona la cita', $respuesta);
        $this->assertStringNotContainsString('2:00 p. m.', $respuesta); // expirada no se lista

        $this->assertStringContainsString('Selecciona un número válido', $servicio->procesar('3000000000', '9', $business->id));

        $respuesta = $servicio->procesar('3000000000', '1', $business->id);
        $this->assertStringContainsString('Confirmar cancelación', $respuesta);

        $this->assertStringContainsString('Confirmar cancelación', $servicio->procesar('3000000000', '5', $business->id));

        $this->assertSame(AppointmentStatus::Confirmed->value, $cita->fresh()->status);
        $this->assertSame(AppointmentStatus::Expired->value, $expirada->fresh()->status);
    }
}
