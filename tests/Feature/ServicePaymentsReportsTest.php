<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Membership;
use App\Models\MembershipPayment;
use App\Models\Professional;
use App\Models\Service;
use App\Models\ServicePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicePaymentsReportsTest extends TestCase
{
    use RefreshDatabase;

    private function adminOf(Business $business): User
    {
        return User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);
    }

    public function test_a_service_payment_can_be_registered_and_verified(): void
    {
        $business = Business::factory()->create();
        $admin = $this->adminOf($business);

        $respuesta = $this->actingAs($admin)
            ->postJson("/businesses/{$business->id}/payments", [
                'amount' => 50000,
                'paid_on' => '2026-11-15',
                'method' => 'efectivo',
                'reference' => 'REC-001',
            ])
            ->assertStatus(201);

        $paymentId = $respuesta->json('payment_id');

        $this->actingAs($admin)->postJson("/payments/{$paymentId}/verify")->assertStatus(200);

        $this->assertDatabaseHas('service_payments', [
            'id' => $paymentId,
            'business_id' => $business->id,
            'verified' => true,
            'created_by' => $admin->id,
        ]);
    }

    public function test_the_receipt_reports_the_payment_details(): void
    {
        $business = Business::factory()->create();
        $admin = $this->adminOf($business);
        $payment = ServicePayment::create([
            'business_id' => $business->id,
            'amount' => 50000,
            'paid_on' => '2026-11-15',
            'method' => 'efectivo',
            'reference' => 'REC-001',
        ]);

        $this->actingAs($admin)->getJson("/payments/{$payment->id}/receipt")
            ->assertOk()
            ->assertJsonPath('comprobante.monto', 50000)
            ->assertJsonPath('comprobante.referencia', 'REC-001')
            ->assertJsonPath('comprobante.concepto', 'Pago de servicios');
    }

    public function test_service_payments_and_memberships_are_reported_separately(): void
    {
        $business = Business::factory()->create();
        $admin = $this->adminOf($business);

        ServicePayment::create([
            'business_id' => $business->id,
            'amount' => 50000,
            'paid_on' => '2026-11-10',
            'verified' => true,
        ]);
        $membership = Membership::create([
            'business_id' => $business->id,
            'starts_on' => '2026-11-01',
            'ends_on' => '2026-11-30',
            'amount' => 150000,
        ]);
        MembershipPayment::create([
            'business_id' => $business->id,
            'membership_id' => $membership->id,
            'amount' => 150000,
            'paid_on' => '2026-11-01',
            'verified' => true,
        ]);

        $this->actingAs($admin)
            ->getJson("/businesses/{$business->id}/reports/summary?from=2026-11-01&to=2026-11-30")
            ->assertOk()
            ->assertJsonPath('ingresos_servicios', 50000)
            ->assertJsonPath('ingresos_membresias', 150000);
    }

    public function test_reports_respect_business_isolation(): void
    {
        $ownBusiness = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $admin = $this->adminOf($ownBusiness);

        $this->actingAs($admin)
            ->getJson("/businesses/{$otherBusiness->id}/reports/summary?from=2026-11-01&to=2026-11-30")
            ->assertStatus(403);

        $this->actingAs($admin)
            ->getJson("/businesses/{$otherBusiness->id}/reports/appointments.csv?from=2026-11-01&to=2026-11-30")
            ->assertStatus(403);
    }

    public function test_the_csv_export_contains_only_the_business_appointments(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $admin = $this->adminOf($business);

        $fecha = now('America/Bogota')->addDays(3)->format('Y-m-d');

        Professional::create(['name' => 'Stefania', 'business_id' => $business->id]);
        $professional = Professional::where('business_id', $business->id)->first();
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
            'customer_phone' => '300',
            'starts_at' => "{$fecha} 09:00:00",
            'ends_at' => "{$fecha} 10:00:00",
            'status' => 'confirmed',
        ]);

        $otroPro = Professional::create(['name' => 'Laura', 'business_id' => $otherBusiness->id]);
        $otroServicio = Service::create([
            'name' => 'Esmaltado',
            'duration_minutes' => 30,
            'price' => 30000,
            'professional_id' => $otroPro->id,
            'business_id' => $otherBusiness->id,
        ]);
        Appointment::forceCreate([
            'professional_id' => $otroPro->id,
            'service_id' => $otroServicio->id,
            'business_id' => $otherBusiness->id,
            'customer_name' => 'Otra',
            'customer_phone' => '301',
            'starts_at' => "{$fecha} 11:00:00",
            'ends_at' => "{$fecha} 11:30:00",
            'status' => 'confirmed',
        ]);

        $csv = $this->actingAs($admin)
            ->get("/businesses/{$business->id}/reports/appointments.csv?from={$fecha}&to={$fecha}")
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Carla', $csv);
        $this->assertStringNotContainsString('Otra', $csv);
    }

    public function test_professional_role_users_cannot_access_financial_reports(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Professional,
        ]);

        $this->actingAs($user)
            ->getJson("/businesses/{$business->id}/reports/summary?from=2026-11-01&to=2026-11-30")
            ->assertStatus(403);
    }

    public function test_appointments_can_be_exported_to_excel_and_pdf(): void
    {
        $business = Business::factory()->create();
        $admin = $this->adminOf($business);
        $fecha = now('America/Bogota')->addDays(3)->format('Y-m-d');

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
            'customer_phone' => '300',
            'starts_at' => "{$fecha} 09:00:00",
            'ends_at' => "{$fecha} 10:00:00",
            'status' => 'confirmed',
        ]);

        $xlsx = $this->actingAs($admin)
            ->get("/businesses/{$business->id}/reports/appointments.xlsx?from={$fecha}&to={$fecha}")
            ->assertOk();

        $xlsx->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $xlsx->getContent());

        $pdf = $this->actingAs($admin)
            ->get("/businesses/{$business->id}/reports/appointments.pdf?from={$fecha}&to={$fecha}")
            ->assertOk();

        $pdf->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_excel_and_pdf_exports_respect_business_isolation(): void
    {
        $ownBusiness = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $admin = $this->adminOf($ownBusiness);

        $this->actingAs($admin)
            ->get("/businesses/{$otherBusiness->id}/reports/appointments.xlsx?from=2026-11-01&to=2026-11-30")
            ->assertStatus(403);

        $this->actingAs($admin)
            ->get("/businesses/{$otherBusiness->id}/reports/appointments.pdf?from=2026-11-01&to=2026-11-30")
            ->assertStatus(403);
    }
}
