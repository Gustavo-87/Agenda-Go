<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Jobs\EnviarNotificacionWhatsApp;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class WhatsAppMultiempresaTest extends TestCase
{
    use RefreshDatabase;

    private function negocioConProfesional(string $slug, string $name, ?string $phoneId = null): array
    {
        $business = Business::factory()->create([
            'slug' => $slug,
            'whatsapp_phone_number_id' => $phoneId,
        ]);
        $professional = Professional::create(['name' => $name, 'business_id' => $business->id]);
        Service::create([
            'name' => 'Semipermanente',
            'duration_minutes' => 60,
            'price' => 50000,
            'professional_id' => $professional->id,
            'business_id' => $business->id,
        ]);

        return [$business, $professional];
    }

    private function webhook(?string $phoneId, string $waId, string $secret = 'app-secret'): TestResponse
    {
        config(['services.whatsapp.app_secret' => $secret]);

        $content = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'metadata' => ['phone_number_id' => $phoneId],
                        'messages' => [[
                            'id' => $waId,
                            'from' => '3001112233',
                            'type' => 'text',
                            'text' => ['body' => 'hola'],
                        ]],
                    ],
                ]],
            ]],
        ]);

        return $this->call('POST', '/api/webhooks/whatsapp', [], [], [], [
            'HTTP_X_Hub_Signature_256' => 'sha256='.hash_hmac('sha256', $content, $secret),
            'CONTENT_TYPE' => 'application/json',
        ], $content);
    }

    public function test_the_webhook_identifies_the_business_by_phone_number_id(): void
    {
        Http::fake();
        $this->negocioConProfesional('viglam', 'Stefania', 'PHONE-A');
        [$businessB] = $this->negocioConProfesional('otro-negocio', 'Laura', 'PHONE-B');

        $this->webhook('PHONE-B', 'wamid.1')->assertOk();

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'PHONE-B/messages')
                && str_contains($request['text']['body'], 'Laura')
                && ! str_contains($request['text']['body'], 'Stefania');
        });

        $this->assertDatabaseHas('whatsapp_messages', [
            'business_id' => $businessB->id,
            'wa_message_id' => 'wamid.1',
            'direction' => 'inbound',
            'status' => 'processed',
        ]);
    }

    public function test_the_webhook_is_idempotent(): void
    {
        Http::fake();
        $this->negocioConProfesional('viglam', 'Stefania', 'PHONE-A');

        $this->webhook('PHONE-A', 'wamid.2')->assertOk();
        $this->webhook('PHONE-A', 'wamid.2')->assertOk();

        Http::assertSentCount(1);
        $this->assertDatabaseCount('whatsapp_messages', 1);
    }

    public function test_unknown_phone_numbers_fall_back_to_the_configured_channel(): void
    {
        Http::fake();
        config(['services.reservas.business_slug' => 'viglam']);
        $this->negocioConProfesional('viglam', 'Stefania');
        $this->negocioConProfesional('otro-negocio', 'Laura', 'PHONE-B');

        $this->webhook('DESCONOCIDO', 'wamid.3')->assertOk();

        Http::assertSent(function (Request $request) {
            return str_contains($request['text']['body'], 'Stefania')
                && ! str_contains($request['text']['body'], 'Laura');
        });
    }

    public function test_confirmation_and_cancellation_notifications_are_dispatched(): void
    {
        Queue::fake();
        $business = Business::factory()->create();
        $professional = Professional::create(['name' => 'Stefania', 'business_id' => $business->id]);
        $service = Service::create([
            'name' => 'Semipermanente',
            'duration_minutes' => 60,
            'price' => 50000,
            'professional_id' => $professional->id,
            'business_id' => $business->id,
        ]);
        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);
        $cita = Appointment::forceCreate([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'business_id' => $business->id,
            'customer_name' => 'Cliente',
            'customer_phone' => '3000000000',
            'starts_at' => now('America/Bogota')->addDays(3)->format('Y-m-d 09:00:00'),
            'ends_at' => now('America/Bogota')->addDays(3)->format('Y-m-d 10:00:00'),
            'status' => AppointmentStatus::Pending->value,
        ]);

        $this->actingAs($admin)->postJson("/appointments/{$cita->id}/confirm")->assertStatus(200);
        Queue::assertPushed(EnviarNotificacionWhatsApp::class);

        $this->actingAs($admin)->postJson("/appointments/{$cita->id}/cancel")->assertStatus(200);
        Queue::assertPushed(EnviarNotificacionWhatsApp::class, 2);
    }

    public function test_the_notification_job_sends_through_the_business_whatsapp_number(): void
    {
        Http::fake();
        [$business] = $this->negocioConProfesional('viglam', 'Stefania', 'PHONE-A');

        (new EnviarNotificacionWhatsApp('3000000000', 'Tu cita fue confirmada.', $business->id))
            ->handle();

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'PHONE-A/messages');
        });

        $this->assertDatabaseHas('whatsapp_messages', [
            'business_id' => $business->id,
            'direction' => 'outbound',
            'status' => 'sent',
        ]);
    }
}
