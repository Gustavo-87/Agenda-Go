<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Professional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebInterfaceTest extends TestCase
{
    use RefreshDatabase;

    private function adminOf(Business $business): User
    {
        return User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);
    }

    public function test_the_dashboard_renders_with_indicators_for_each_role(): void
    {
        $business = Business::factory()->create();
        $admin = $this->adminOf($business);

        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Panel principal');

        $profesional = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Professional,
        ]);
        $this->actingAs($profesional)->get('/dashboard')->assertOk();

        $super = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $this->actingAs($super)->get('/dashboard')->assertOk()->assertSee('Administración SaaS');
    }

    public function test_the_management_screen_shows_only_the_business_resources(): void
    {
        $ownBusiness = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        Professional::create(['name' => 'Stefania', 'business_id' => $ownBusiness->id]);
        Professional::create(['name' => 'Laura', 'business_id' => $otherBusiness->id]);

        $this->actingAs($this->adminOf($ownBusiness))
            ->get('/gestion')
            ->assertOk()
            ->assertSee('Stefania')
            ->assertDontSee('Laura');
    }

    public function test_management_and_reports_are_denied_to_professionals(): void
    {
        $business = Business::factory()->create();
        $profesional = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Professional,
        ]);

        $this->actingAs($profesional)->get('/gestion')->assertStatus(403);
        $this->actingAs($profesional)->get('/reportes')->assertStatus(403);
    }

    public function test_web_forms_create_and_update_professionals_with_flash_messages(): void
    {
        $business = Business::factory()->create();
        $admin = $this->adminOf($business);

        $this->actingAs($admin)
            ->from('/gestion')
            ->post('/professionals', ['name' => 'Stefania', 'phone' => '300'])
            ->assertRedirect('/gestion')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('professionals', ['name' => 'Stefania', 'business_id' => $business->id]);
    }

    public function test_the_customer_file_screen_renders_and_is_isolated(): void
    {
        $ownBusiness = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        $cliente = Customer::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Carla',
            'phone' => '300',
        ]);

        $this->actingAs($this->adminOf($ownBusiness))
            ->get('/clientes')
            ->assertOk()
            ->assertDontSee('Carla');

        $this->actingAs($this->adminOf($otherBusiness))
            ->get("/customers/{$cliente->id}")
            ->assertOk()
            ->assertSee('Carla');

        $this->actingAs($this->adminOf($ownBusiness))
            ->get("/customers/{$cliente->id}")
            ->assertStatus(403);
    }

    public function test_reports_screen_renders_with_exports(): void
    {
        $business = Business::factory()->create();

        $this->actingAs($this->adminOf($business))
            ->get('/reportes')
            ->assertOk()
            ->assertSee('Ingresos por servicios')
            ->assertSee('Excel');
    }

    public function test_the_admin_panel_is_restricted_to_super_admins(): void
    {
        $business = Business::factory()->create();
        $admin = $this->adminOf($business);

        $this->actingAs($admin)->get('/admin')->assertStatus(403);

        $super = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $this->actingAs($super)->get('/admin')->assertOk()->assertSee('Registrar negocio');
    }

    public function test_the_settings_screen_allows_configuring_the_booking_policy(): void
    {
        $business = Business::factory()->create();
        $admin = $this->adminOf($business);

        $this->actingAs($admin)->get('/configuracion')->assertOk()->assertSee('Política de reservas');

        $this->actingAs($admin)
            ->from('/configuracion')
            ->patch('/businesses/'.$business->id.'/booking-policy', ['booking_min_notice_minutes' => 90])
            ->assertRedirect('/configuracion')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'booking_min_notice_minutes' => 90]);
    }

    public function test_navigation_shows_only_authorized_options(): void
    {
        $business = Business::factory()->create();
        $profesional = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Professional,
        ]);

        $this->actingAs($profesional)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Agenda')
            ->assertDontSee('>Gestión<')
            ->assertDontSee('Administración');

        $super = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $this->actingAs($super)->get('/dashboard')->assertSee('Administración');
    }

    public function test_the_agenda_screen_renders_with_actions(): void
    {
        $business = Business::factory()->create();
        $admin = $this->adminOf($business);

        $this->actingAs($admin)->get('/agenda')->assertOk()->assertSee('Agenda');
    }
}
