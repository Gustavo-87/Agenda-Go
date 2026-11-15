<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Jobs\EnviarNotificacionWhatsApp;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CustomerCommunicationsTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(): array
    {
        config(['services.reservas.business_slug' => 'canal-clientes']);
        $business = Business::factory()->create(['slug' => 'canal-clientes']);
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

    private function reservar(Professional $professional, Service $service, string $phone, string $name, string $startsAt)
    {
        return $this->postJson('/api/appointments', [
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'customer_name' => $name,
            'customer_phone' => $phone,
            'starts_at' => $startsAt,
        ]);
    }

    public function test_booking_creates_the_customer_record(): void
    {
        Queue::fake();
        [$business, $professional, $service] = $this->escenario();
        $fecha = now('America/Bogota')->addDays(3)->format('Y-m-d');

        $this->reservar($professional, $service, '3000000000', 'Carla', "{$fecha} 09:00:00")
            ->assertStatus(201);

        $this->assertDatabaseHas('customers', [
            'business_id' => $business->id,
            'phone' => '3000000000',
            'name' => 'Carla',
        ]);

        $this->assertDatabaseHas('appointments', ['customer_id' => Customer::first()->id]);
    }

    public function test_the_customer_file_shows_their_history(): void
    {
        [$business, $professional, $service] = $this->escenario();
        $customer = Customer::create([
            'business_id' => $business->id,
            'name' => 'Carla',
            'phone' => '3000000000',
        ]);

        foreach ([['09:00', '10:00'], ['11:00', '12:00']] as [$ini, $fin]) {
            Appointment::forceCreate([
                'professional_id' => $professional->id,
                'service_id' => $service->id,
                'business_id' => $business->id,
                'customer_id' => $customer->id,
                'customer_name' => 'Carla',
                'customer_phone' => '3000000000',
                'starts_at' => now('America/Bogota')->addDays(3)->format("Y-m-d {$ini}:00"),
                'ends_at' => now('America/Bogota')->addDays(3)->format("Y-m-d {$fin}:00"),
                'status' => AppointmentStatus::Completed->value,
            ]);
        }

        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($admin)->getJson("/customers/{$customer->id}")
            ->assertOk()
            ->assertJsonCount(2, 'historial');
    }

    public function test_customer_files_are_isolated_between_businesses(): void
    {
        [$business] = $this->escenario();
        $customer = Customer::create([
            'business_id' => $business->id,
            'name' => 'Carla',
            'phone' => '3000000000',
        ]);

        $intruso = User::factory()->create([
            'business_id' => Business::factory()->create()->id,
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($intruso)->getJson("/customers/{$customer->id}")->assertStatus(403);
        $this->actingAs($intruso)->postJson("/customers/{$customer->id}/consent", ['data_consent' => true])
            ->assertStatus(403);
    }

    public function test_data_consent_is_recorded(): void
    {
        [$business] = $this->escenario();
        $customer = Customer::create([
            'business_id' => $business->id,
            'name' => 'Carla',
            'phone' => '3000000000',
        ]);
        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($admin)
            ->postJson("/customers/{$customer->id}/consent", ['data_consent' => true])
            ->assertStatus(200);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'data_consent' => true,
        ]);
    }

    public function test_attendance_and_absences_are_registered(): void
    {
        [$business, $professional, $service] = $this->escenario();
        $customer = Customer::create([
            'business_id' => $business->id,
            'name' => 'Carla',
            'phone' => '3000000000',
        ]);
        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);

        $asistida = Appointment::forceCreate([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'customer_name' => 'Carla',
            'customer_phone' => '3000000000',
            'starts_at' => now('America/Bogota')->addDays(3)->format('Y-m-d 09:00:00'),
            'ends_at' => now('America/Bogota')->addDays(3)->format('Y-m-d 10:00:00'),
            'status' => AppointmentStatus::Confirmed->value,
        ]);

        $this->actingAs($admin)->postJson("/appointments/{$asistida->id}/attend")->assertStatus(200);
        $this->assertDatabaseHas('appointments', ['id' => $asistida->id, 'status' => 'completed']);

        $this->actingAs($admin)->postJson("/appointments/{$asistida->id}/absent")->assertStatus(422);

        $ausente = Appointment::forceCreate([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'customer_name' => 'Carla',
            'customer_phone' => '3000000000',
            'starts_at' => now('America/Bogota')->addDays(4)->format('Y-m-d 09:00:00'),
            'ends_at' => now('America/Bogota')->addDays(4)->format('Y-m-d 10:00:00'),
            'status' => AppointmentStatus::Confirmed->value,
        ]);

        $this->actingAs($admin)->postJson("/appointments/{$ausente->id}/absent")->assertStatus(200);

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'absences' => 1]);
    }

    public function test_absence_history_triggers_alerts_on_new_bookings(): void
    {
        Queue::fake();
        [$business, $professional, $service] = $this->escenario();
        $professional->update(['phone' => '3100000000']);
        Customer::create([
            'business_id' => $business->id,
            'name' => 'Carla',
            'phone' => '3000000000',
            'absences' => 2,
        ]);

        $fecha = now('America/Bogota')->addDays(3)->format('Y-m-d');
        $this->reservar($professional, $service, '3000000000', 'Carla', "{$fecha} 09:00:00")
            ->assertStatus(201);

        Queue::assertPushed(EnviarNotificacionWhatsApp::class, 1);
    }

    public function test_reminders_are_sent_once_within_24_hours(): void
    {
        Queue::fake();
        [$business, $professional, $service] = $this->escenario();

        $cita = Appointment::forceCreate([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'business_id' => $business->id,
            'customer_name' => 'Carla',
            'customer_phone' => '3000000000',
            'starts_at' => now()->addHours(23)->format('Y-m-d H:i:s'),
            'ends_at' => now()->addHours(24)->format('Y-m-d H:i:s'),
            'status' => AppointmentStatus::Confirmed->value,
        ]);

        $this->artisan('appointments:remind')->assertSuccessful();
        Queue::assertPushed(EnviarNotificacionWhatsApp::class, 1);

        $this->assertNotNull($cita->fresh()->reminder_sent_at);

        $this->artisan('appointments:remind')->assertSuccessful();
        Queue::assertPushed(EnviarNotificacionWhatsApp::class, 1);
    }
}
