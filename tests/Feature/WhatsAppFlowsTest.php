<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Holiday;
use App\Models\Professional;
use App\Models\Service;
use App\Models\WorkSchedule;
use App\Services\FlujoReservasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Flows de WhatsApp: formularios visuales de reserva y cancelación,
 * reutilizando el motor de disponibilidad y las reglas existentes.
 */
class WhatsAppFlowsTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(): array
    {
        $business = Business::factory()->create([
            'name' => 'Barbería Demo',
            'whatsapp_phone_number_id' => 'PHONE-FLOW',
            'active' => true,
            'booking_min_notice_minutes' => 0,
        ]);
        $professional = Professional::create(['name' => 'Carlos', 'business_id' => $business->id]);
        $service = Service::create([
            'name' => 'Corte',
            'duration_minutes' => 60,
            'price' => 30000,
            'professional_id' => $professional->id,
            'business_id' => $business->id,
        ]);

        return [$business, $professional, $service];
    }

    public function test_booking_form_scopes_catalog_and_shows_12_hour_times(): void
    {
        [$business, $professional, $service] = $this->escenario();
        $otro = Business::factory()->create(['active' => true]);
        Professional::create(['name' => 'Lucía', 'business_id' => $otro->id]);
        $fecha = now('America/Bogota')->addDays(3)->toDateString();

        $flujo = app(FlujoReservasService::class);

        $respuesta = $flujo->procesar([
            'action' => 'INIT',
            'phone_number_id' => 'PHONE-FLOW',
            'phone_number' => '3000000000',
        ]);

        $profesionales = collect($respuesta['data']['profesionales']);
        $this->assertCount(1, $profesionales);
        $this->assertSame('Carlos', $profesionales[0]['title']);

        $respuesta = $flujo->procesar([
            'action' => 'DATA',
            'phone_number_id' => 'PHONE-FLOW',
            'data' => ['accion' => 'fecha', 'profesional' => $professional->id, 'servicio' => $service->id, 'fecha' => $fecha],
        ]);

        $horarios = collect($respuesta['data']['horarios']);
        $this->assertContains('9:00 a. m.', $horarios->pluck('title')->all());
        $this->assertContains('09:00', $horarios->pluck('id')->all()); // valores internos en 24 h
    }

    public function test_forms_exclude_occupied_breaks_holidays_and_unavailable_dates(): void
    {
        Queue::fake();
        [$business, $professional, $service] = $this->escenario();
        $fecha = now('America/Bogota')->addDays(3)->toDateString();
        $flujo = app(FlujoReservasService::class);

        WorkSchedule::create([
            'professional_id' => $professional->id,
            'weekday' => (int) now('America/Bogota')->addDays(3)->dayOfWeekIso - 1,
            'start_time' => '09:00',
            'end_time' => '13:00',
        ]);
        WorkSchedule::create([
            'professional_id' => $professional->id,
            'weekday' => (int) now('America/Bogota')->addDays(3)->dayOfWeekIso - 1,
            'start_time' => '11:00',
            'end_time' => '11:30',
            'is_break' => true,
        ]);
        Holiday::create(['business_id' => $business->id, 'date' => $fecha]);

        $respuesta = $flujo->procesar([
            'action' => 'DATA',
            'phone_number_id' => 'PHONE-FLOW',
            'data' => ['accion' => 'fecha', 'profesional' => $professional->id, 'servicio' => $service->id, 'fecha' => $fecha],
        ]);

        $this->assertContains($fecha, $respuesta['data']['fechas_no_disponibles']);

        Holiday::query()->delete();

        Appointment::forceCreate([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'business_id' => $business->id,
            'customer_name' => 'Ocupada',
            'customer_phone' => '399',
            'starts_at' => "{$fecha} 09:00:00",
            'ends_at' => "{$fecha} 10:00:00",
            'status' => 'confirmed',
        ]);

        $respuesta = $flujo->procesar([
            'action' => 'DATA',
            'phone_number_id' => 'PHONE-FLOW',
            'data' => ['accion' => 'fecha', 'profesional' => $professional->id, 'servicio' => $service->id, 'fecha' => $fecha],
        ]);

        $ids = collect($respuesta['data']['horarios'])->pluck('id')->all();
        $this->assertNotContains('09:00', $ids); // ocupado
        $this->assertNotContains('10:30', $ids); // solapa el descanso
        $this->assertNotContains('11:00', $ids); // descanso
    }

    public function test_sender_phone_is_captured_and_can_be_replaced_by_a_contact_phone(): void
    {
        Queue::fake();
        [, $professional, $service] = $this->escenario();
        $fecha = now('America/Bogota')->addDays(3)->toDateString();
        $flujo = app(FlujoReservasService::class);

        $base = [
            'action' => 'DATA',
            'phone_number_id' => 'PHONE-FLOW',
            'phone_number' => '3000000000',
            'data' => [
                'accion' => 'confirmar',
                'profesional' => $professional->id,
                'servicio' => $service->id,
                'fecha' => $fecha,
                'hora' => '09:00',
                'nombre' => 'Carla',
            ],
        ];

        // Sin teléfono de contacto: se usa el del remitente.
        $respuesta = $flujo->procesar($base);
        $this->assertSame('SUCCESS', $respuesta['screen']);
        $this->assertSame('3000000000', $respuesta['data']['remitente']);
        $this->assertDatabaseHas('appointments', [
            'customer_phone' => '3000000000',
            'starts_at' => "{$fecha} 09:00:00",
            'status' => 'confirmed',
        ]);

        // Con otro teléfono de contacto: se conserva el remitente.
        $base['data']['hora'] = '10:00';
        $base['data']['telefono'] = '3111111111';

        $respuesta = $flujo->procesar($base);
        $this->assertSame('SUCCESS', $respuesta['screen']);
        $this->assertSame('3000000000', $respuesta['data']['remitente']);
        $this->assertDatabaseHas('appointments', [
            'customer_phone' => '3111111111',
            'starts_at' => "{$fecha} 10:00:00",
        ]);
    }

    public function test_cancel_flow_lists_confirms_and_cancels_with_policies(): void
    {
        [$business, $professional, $service] = $this->escenario();
        $fecha = now('America/Bogota')->addDays(3)->toDateString();
        $flujo = app(FlujoReservasService::class);

        $cita = Appointment::forceCreate([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'business_id' => $business->id,
            'customer_name' => 'Carla',
            'customer_phone' => '3000000000',
            'starts_at' => "{$fecha} 09:00:00",
            'ends_at' => "{$fecha} 10:00:00",
            'status' => 'confirmed',
        ]);

        // Acceso cruzado: las citas de otro negocio no se listan.
        $otro = Business::factory()->create(['active' => true]);
        $otroProfesional = Professional::create(['name' => 'Lucía', 'business_id' => $otro->id]);
        $otroServicio = Service::create([
            'name' => 'Servicio ajeno',
            'duration_minutes' => 60,
            'price' => 10000,
            'professional_id' => $otroProfesional->id,
            'business_id' => $otro->id,
        ]);
        Appointment::forceCreate([
            'professional_id' => $otroProfesional->id,
            'service_id' => $otroServicio->id,
            'business_id' => $otro->id,
            'customer_name' => 'Carla',
            'customer_phone' => '3000000000',
            'starts_at' => "{$fecha} 15:00:00",
            'ends_at' => "{$fecha} 16:00:00",
            'status' => 'confirmed',
        ]);

        $respuesta = $flujo->procesar([
            'action' => 'DATA',
            'phone_number_id' => 'PHONE-FLOW',
            'phone_number' => '3000000000',
            'data' => ['accion' => 'cancelar'],
        ]);

        $this->assertSame('CANCELAR', $respuesta['screen']);
        $this->assertCount(1, $respuesta['data']['citas']);
        $this->assertStringContainsString('9:00 a. m.', $respuesta['data']['citas'][0]['title']);

        // Selección y confirmación expresa antes de cancelar.
        $respuesta = $flujo->procesar([
            'action' => 'DATA',
            'phone_number_id' => 'PHONE-FLOW',
            'phone_number' => '3000000000',
            'data' => ['accion' => 'cancelar_seleccion', 'cita' => $cita->id],
        ]);
        $this->assertSame('CONFIRMAR_CANCELACION', $respuesta['screen']);

        $this->assertSame(AppointmentStatus::Confirmed->value, $cita->fresh()->status);

        $respuesta = $flujo->procesar([
            'action' => 'DATA',
            'phone_number_id' => 'PHONE-FLOW',
            'phone_number' => '3000000000',
            'data' => ['accion' => 'cancelar_confirmar', 'cita' => $cita->id],
        ]);
        $this->assertSame('SUCCESS', $respuesta['screen']);
        $this->assertSame(AppointmentStatus::Cancelled->value, $cita->fresh()->status);

        // Doble cancelación / citas inexistentes.
        $respuesta = $flujo->procesar([
            'action' => 'DATA',
            'phone_number_id' => 'PHONE-FLOW',
            'phone_number' => '3000000000',
            'data' => ['accion' => 'cancelar'],
        ]);
        $this->assertSame('CANCELAR', $respuesta['screen']);
        $this->assertStringContainsString('No encontramos citas', $respuesta['data']['aviso']);
    }

    public function test_flow_definition_matches_the_laravel_contract(): void
    {
        $flow = json_decode((string) file_get_contents(base_path('flow-reservas.json')), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('7.3', $flow['version']);
        $this->assertSame('3.0', $flow['data_api_version']);

        // El esquema de Flows no admite data-source en TextInput.
        $serializado = json_encode($flow['screens'], JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('"type": "TextInput", "name": "telefono", "label": "Teléfono de contacto", "input-type": "phone", "data-source"', $serializado);

        $ids = collect($flow['screens'])->pluck('id')->all();
        foreach (['APPOINTMENT', 'DETAILS', 'SUMMARY', 'SUCCESS', 'CANCELAR', 'CONFIRMAR_CANCELACION'] as $pantalla) {
            $this->assertContains($pantalla, $ids);
        }

        $this->assertSame(['CONFIRMAR_CANCELACION'], $flow['routing_model']['CANCELAR']);
        $this->assertSame(['SUCCESS'], $flow['routing_model']['CONFIRMAR_CANCELACION']);
        $this->assertSame([], $flow['routing_model']['SUCCESS']);

        // Restricciones estructurales de Meta: un solo Form y un solo
        // Footer por pantalla.
        foreach ($flow['screens'] as $pantalla) {
            $formularios = array_filter($pantalla['layout']['children'], fn ($hijo) => ($hijo['type'] ?? '') === 'Form');
            $this->assertLessThanOrEqual(1, count($formularios), $pantalla['id']);

            $footers = 0;
            array_walk_recursive($pantalla['layout'], function ($valor, $clave) use (&$footers) {
                if ($clave === 'type' && $valor === 'Footer') {
                    $footers++;
                }
            });
            $this->assertLessThanOrEqual(1, $footers, $pantalla['id']);
        }

        // Los modelos data declaran los campos exigidos por Meta.
        $modelos = collect($flow['screens'])->keyBy('id');
        $this->assertArrayHasKey('telefono_remitente', $modelos['APPOINTMENT']['data']);
        $this->assertArrayHasKey('accion', $modelos['CANCELAR']['data']);
        $this->assertArrayHasKey('telefono', $modelos['CANCELAR']['data']);
        $this->assertArrayHasKey('accion', $modelos['CONFIRMAR_CANCELACION']['data']);

        // Los payloads navigate contienen todos los campos del modelo
        // de la pantalla destino y solo campos declarados en él.
        foreach ($flow['screens'] as $pantalla) {
            $serializadoPantalla = json_encode($pantalla['layout'], JSON_THROW_ON_ERROR);
            preg_match_all('/"name": "navigate", "next": \{"type": "screen", "name": "([A-Z_]+)"\}, "payload": \{(.*?)\}\}\}/', $serializadoPantalla, $coincidencias, PREG_SET_ORDER);

            foreach ($coincidencias as $coincidencia) {
                $destino = $modelos[$coincidencia[1]]['data'] ?? [];
                preg_match_all('/"([a-z_]+)":/', $coincidencia[2], $claves);
                $enviadas = $claves[1];

                foreach ($enviadas as $clave) {
                    $this->assertArrayHasKey($clave, $destino, $pantalla['id'].' → '.$coincidencia[1]);
                }

                foreach (array_keys($destino) as $campo) {
                    $this->assertContains($campo, $enviadas, $pantalla['id'].' → '.$coincidencia[1]);
                }
            }
        }

        // Contrato de intercambio: las acciones y campos coinciden con
        // los que lee FlujoReservasService.
        $payloads = [];
        $recorrer = function ($nodo) use (&$recorrer, &$payloads) {
            if (is_array($nodo)) {
                foreach ($nodo as $clave => $valor) {
                    if ($clave === 'payload' && is_array($valor)) {
                        $payloads[] = $valor;
                    }
                    $recorrer($valor);
                }
            }
        };
        $recorrer($flow['screens']);

        $permitidas = ['accion', 'profesional', 'servicio', 'fecha', 'hora', 'nombre', 'telefono', 'cita'];

        foreach ($payloads as $payload) {
            if (! isset($payload['accion'])) {
                continue; // payloads de navegación/completado de Meta
            }

            foreach (array_keys($payload) as $clave) {
                $this->assertContains($clave, $permitidas);
            }
        }

        $acciones = collect($payloads)->pluck('accion')->filter()->unique()->all();
        $this->assertContains('revisar', $acciones);
        $this->assertContains('confirmar', $acciones);
        $this->assertStringContainsString('Cancelar cita', (string) json_encode($flow, JSON_THROW_ON_ERROR));
        $this->assertContains('cancelar_seleccion', $acciones);
        $this->assertContains('cancelar_confirmar', $acciones);

        $detalles = collect($flow['screens'])->firstWhere('id', 'DETAILS');
        $this->assertArrayHasKey('telefono_remitente', $detalles['data']);

        $cancelar = collect($flow['screens'])->firstWhere('id', 'CANCELAR');
        $this->assertArrayHasKey('citas', $cancelar['data']);
    }
}
