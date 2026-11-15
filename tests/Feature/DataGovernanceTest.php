<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Membership;
use App\Models\MembershipPayment;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DataGovernanceTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => UserRole::SuperAdmin]);
    }

    public function test_data_export_is_authorized_audited_and_secure(): void
    {
        $business = Business::factory()->create();
        Customer::create(['business_id' => $business->id, 'name' => 'Carla', 'phone' => '300']);
        $super = $this->superAdmin();
        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($admin)
            ->postJson("/admin/businesses/{$business->id}/export")
            ->assertStatus(403);

        $archivo = $this->actingAs($super)
            ->postJson("/admin/businesses/{$business->id}/export")
            ->assertStatus(201)
            ->json('archivo');

        $this->assertFileExists(storage_path('app/exports/'.$archivo));
        $this->assertDatabaseHas('audit_logs', ['action' => 'negocio.exportado']);

        $this->actingAs($super)
            ->get("/admin/exports/{$archivo}")
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', ['action' => 'negocio.exportacion.descargada']);

        $this->actingAs($super)->get('/admin/exports/otro.json')->assertStatus(404);
    }

    public function test_notification_center_scopes_by_business_and_marks_read(): void
    {
        Queue::fake();
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $admin = User::factory()->create(['business_id' => $business->id, 'role' => UserRole::Admin]);
        $professionalUser = User::factory()->create(['business_id' => $business->id, 'role' => UserRole::Professional]);
        $foreign = User::factory()->create(['business_id' => $otherBusiness->id, 'role' => UserRole::Admin]);

        $professional = Professional::create([
            'name' => 'Stefania',
            'business_id' => $business->id,
            'user_id' => $professionalUser->id,
        ]);
        $service = Service::create([
            'name' => 'Semipermanente',
            'duration_minutes' => 60,
            'price' => 50000,
            'professional_id' => $professional->id,
            'business_id' => $business->id,
        ]);
        $appointment = Appointment::forceCreate([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'business_id' => $business->id,
            'customer_name' => 'Carla',
            'customer_phone' => '300',
            'starts_at' => now('America/Bogota')->addDays(2)->format('Y-m-d 09:00:00'),
            'ends_at' => now('America/Bogota')->addDays(2)->format('Y-m-d 10:00:00'),
            'status' => 'pending',
            'pending_expires_at' => now('America/Bogota')->addHour()->format('Y-m-d H:i:s'),
        ]);

        $this->actingAs($admin)->postJson("/appointments/{$appointment->id}/confirm")->assertStatus(200);

        $notificacion = AppNotification::where('type', 'cita')->firstOrFail();
        $this->assertStringContainsString('Cita confirmada', $notificacion->message);

        $this->actingAs($admin)->get('/notificaciones')->assertOk()->assertSee('Cita confirmada');
        $this->actingAs($professionalUser)->get('/notificaciones')->assertOk()->assertSee('Cita confirmada');
        $this->actingAs($foreign)->get('/notificaciones')->assertOk()->assertDontSee('Cita confirmada');

        $this->actingAs($professionalUser)
            ->postJson("/notificaciones/{$notificacion->id}/read")
            ->assertStatus(200);

        $this->assertNotNull($notificacion->fresh()->read_at);
    }

    public function test_retention_review_and_purge_require_explicit_authorization(): void
    {
        Queue::fake();
        $business = Business::factory()->create();
        Membership::create([
            'business_id' => $business->id,
            'starts_on' => now()->subDays(130)->toDateString(),
            'ends_on' => now()->subDays(100)->toDateString(),
            'amount' => 150000,
            'status' => 'cancelled',
        ]);
        MembershipPayment::create([
            'business_id' => $business->id,
            'membership_id' => $business->memberships()->first()->id,
            'amount' => 150000,
            'paid_on' => now()->subDays(130)->toDateString(),
            'verified' => true,
        ]);
        Customer::create(['business_id' => $business->id, 'name' => 'Carla', 'phone' => '300']);
        WhatsAppMessage::create([
            'business_id' => $business->id,
            'direction' => 'outbound',
            'phone' => '300',
            'body' => 'Hola',
            'status' => 'sent',
        ]);
        $super = $this->superAdmin();

        $this->actingAs($super)->get('/admin/retention')->assertOk()->assertSee($business->name);

        // Sin autorización explícita: no se purga.
        $this->artisan('retention:purge', ['slug' => $business->slug])->assertFailed();
        $this->assertDatabaseCount('customers', 1);

        // Exclusión legal: no se purga.
        $this->actingAs($super)
            ->postJson("/admin/businesses/{$business->id}/legal-hold", ['legal_hold' => 1])
            ->assertStatus(200);

        $this->artisan('retention:purge', ['slug' => $business->slug, '--confirm' => $business->slug])
            ->assertFailed();
        $this->assertDatabaseCount('customers', 1);

        // Autorización explícita: purga de datos operativos con
        // conservación de registros financieros.
        $this->actingAs($super)
            ->postJson("/admin/businesses/{$business->id}/legal-hold", ['legal_hold' => 0])
            ->assertStatus(200);

        $this->artisan('retention:purge', ['slug' => $business->slug, '--confirm' => $business->slug])
            ->assertSuccessful();

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('whatsapp_messages', 0);
        $this->assertDatabaseCount('memberships', 1);
        $this->assertDatabaseCount('membership_payments', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'negocio.purga']);
    }
}
