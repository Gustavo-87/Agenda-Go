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
use App\Services\ReservaWhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeFormatLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_times_are_displayed_in_12_hour_format_without_changing_storage(): void
    {
        $business = Business::factory()->create();
        $professional = Professional::create(['name' => 'Stefania', 'business_id' => $business->id]);
        $service = Service::create([
            'name' => 'Semipermanente',
            'duration_minutes' => 60,
            'price' => 50000,
            'professional_id' => $professional->id,
            'business_id' => $business->id,
        ]);
        $fecha = now('America/Bogota')->addDays(3)->format('Y-m-d');

        foreach (['09:00:00', '14:00:00'] as $hora) {
            Appointment::forceCreate([
                'professional_id' => $professional->id,
                'service_id' => $service->id,
                'business_id' => $business->id,
                'customer_name' => 'Carla',
                'customer_phone' => '300',
                'starts_at' => "{$fecha} {$hora}",
                'ends_at' => $hora === '09:00:00' ? "{$fecha} 10:00:00" : "{$fecha} 15:00:00",
                'status' => AppointmentStatus::Confirmed->value,
            ]);
        }

        WorkSchedule::create([
            'professional_id' => $professional->id,
            'weekday' => (int) now('America/Bogota')->addDays(3)->dayOfWeekIso - 1,
            'start_time' => '09:00',
            'end_time' => '13:00',
        ]);

        $this->actingAs(User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]))
            ->get("/agenda?date={$fecha}")
            ->assertOk()
            ->assertSee('9:00 a. m.')
            ->assertSee('2:00 p. m.');

        $this->actingAs(User::where('role', UserRole::Admin)->first())
            ->get('/gestion?tab=horarios')
            ->assertOk()
            ->assertSee('9:00 a. m.')
            ->assertSee('1:00 p. m.');

        // El almacenamiento interno permanece en 24 horas.
        $this->assertDatabaseHas('appointments', ['starts_at' => "{$fecha} 09:00:00"]);
        $this->assertDatabaseHas('work_schedules', ['start_time' => '09:00']);
    }

    public function test_confirmation_messages_use_12_hour_format(): void
    {
        $business = Business::factory()->create();
        $professional = Professional::create(['name' => 'Stefania', 'business_id' => $business->id]);
        $service = Service::create([
            'name' => 'Semipermanente',
            'duration_minutes' => 60,
            'price' => 50000,
            'professional_id' => $professional->id,
            'business_id' => $business->id,
        ]);

        Appointment::forceCreate([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'business_id' => $business->id,
            'customer_name' => 'Carla',
            'customer_phone' => '3000000000',
            'starts_at' => now('America/Bogota')->addDays(2)->format('Y-m-d 14:00:00'),
            'ends_at' => now('America/Bogota')->addDays(2)->format('Y-m-d 15:00:00'),
            'status' => AppointmentStatus::Pending->value,
            'pending_expires_at' => now('America/Bogota')->addHour()->format('Y-m-d H:i:s'),
        ]);

        $respuesta = app(ReservaWhatsAppService::class)->procesar('3000000000', 'confirmar', $business->id);

        $this->assertStringContainsString('2:00 p. m.', $respuesta);
    }

    public function test_management_forms_show_creation_sections_and_visible_labels(): void
    {
        $business = Business::factory()->create();

        $this->actingAs(User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]))
            ->get('/gestion')
            ->assertOk()
            ->assertSee('Agregar profesional')
            ->assertSee('Agregar servicio')
            ->assertSee('Registrar excepción')
            ->assertSee('Agregar festivo')
            ->assertSee('Listado de profesionales')
            ->assertSee('Listado de servicios')
            ->assertSee('Duración (minutos)')
            ->assertSee('Precio (COP)')
            ->assertSee('Desde (fecha)');
    }
}
