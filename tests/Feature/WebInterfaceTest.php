<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkSchedule;
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
        $this->actingAs($super)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Negocios')
            ->assertDontSee('>Agenda<');
    }

    public function test_the_agenda_screen_renders_with_actions(): void
    {
        $business = Business::factory()->create();
        $admin = $this->adminOf($business);

        $this->actingAs($admin)->get('/agenda')->assertOk()->assertSee('Agenda');
    }

    public function test_the_week_view_renders_for_the_agenda(): void
    {
        $business = Business::factory()->create();

        $this->actingAs($this->adminOf($business))
            ->get('/agenda?vista=semana')
            ->assertOk()
            ->assertSee('Semana');
    }

    public function test_services_can_be_edited_from_the_management_screen(): void
    {
        $business = Business::factory()->create();
        $admin = $this->adminOf($business);
        $professional = Professional::create(['name' => 'Stefania', 'business_id' => $business->id]);
        $service = Service::create([
            'name' => 'Semipermanente',
            'duration_minutes' => 60,
            'price' => 50000,
            'professional_id' => $professional->id,
            'business_id' => $business->id,
        ]);

        $this->actingAs($admin)
            ->from('/gestion')
            ->patch('/services/'.$service->id, [
                'name' => 'Manos y pies',
                'duration_minutes' => 90,
                'price' => 70000,
                'professional_id' => $professional->id,
            ])
            ->assertRedirect('/gestion')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Manos y pies',
            'duration_minutes' => 90,
            'price' => 70000,
        ]);
    }

    public function test_users_without_a_business_see_friendly_states(): void
    {
        $user = User::factory()->create(['role' => UserRole::Professional]);

        $this->actingAs($user)->get('/clientes')->assertOk()->assertSee('No tienes un negocio asignado');
        $this->actingAs($user)->get('/configuracion')->assertOk()->assertSee('Seguridad de la cuenta');
    }

    public function test_super_admin_cannot_access_business_operations(): void
    {
        $business = Business::factory()->create();
        Customer::create([
            'business_id' => $business->id,
            'name' => 'Carla',
            'phone' => '300',
        ]);
        $super = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($super)->get('/clientes')->assertStatus(403);
        $this->actingAs($super)->get('/clientes?business_id='.$business->id)->assertStatus(403);
        $this->actingAs($super)->get('/agenda')->assertStatus(403);
    }

    public function test_the_admin_agenda_shows_the_whole_business(): void
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

        Appointment::forceCreate([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'business_id' => $business->id,
            'customer_name' => 'Cliente Global',
            'customer_phone' => '300',
            'starts_at' => "{$fecha} 09:00:00",
            'ends_at' => "{$fecha} 10:00:00",
            'status' => 'confirmed',
        ]);

        $this->actingAs($this->adminOf($business))
            ->get('/agenda?date='.$fecha)
            ->assertOk()
            ->assertSee('Cliente Global');
    }

    public function test_customers_can_be_searched_by_name_and_phone(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        Customer::create(['business_id' => $business->id, 'name' => 'Carla', 'phone' => '3001111111']);
        Customer::create(['business_id' => $business->id, 'name' => 'Carolina', 'phone' => '3002222222']);
        Customer::create(['business_id' => $otherBusiness->id, 'name' => 'Carla Externa', 'phone' => '3001111111']);

        $admin = $this->adminOf($business);

        $this->actingAs($admin)
            ->get('/clientes?q=Carla')
            ->assertOk()
            ->assertSee('Carla')
            ->assertDontSee('Carolina')
            ->assertDontSee('Externa');

        $this->actingAs($admin)
            ->get('/clientes?q=300222')
            ->assertOk()
            ->assertSee('Carolina')
            ->assertDontSee('>Carla<')
            ->assertDontSee('3001111111');
    }

    public function test_validation_messages_are_displayed_in_spanish(): void
    {
        $business = Business::factory()->create();
        $admin = $this->adminOf($business);

        $this->actingAs($admin)
            ->from('/gestion')
            ->post('/professionals', ['name' => str_repeat('a', 200)])
            ->assertRedirect('/gestion')
            ->assertSessionHasErrors('name');

        $this->assertStringContainsString('caracteres', session('errors')->first('name'));
    }

    public function test_the_agenda_shows_colombian_spanish_dates(): void
    {
        $business = Business::factory()->create();

        $this->actingAs($this->adminOf($business))
            ->get('/agenda?date=2026-11-16')
            ->assertOk()
            ->assertSee('lunes, 16 de noviembre');
    }

    public function test_schedule_actions_keep_the_active_tab(): void
    {
        $business = Business::factory()->create();
        $admin = $this->adminOf($business);
        $professional = Professional::create(['name' => 'Stefania', 'business_id' => $business->id]);
        $origen = '/gestion?tab=horarios&profesional='.$professional->id.'&dia=1';

        $this->actingAs($admin)
            ->from($origen)
            ->post("/professionals/{$professional->id}/schedules", [
                'weekday' => 1,
                'start_time' => '09:00',
                'end_time' => '13:00',
            ])
            ->assertRedirect($origen)
            ->assertSessionHas('success');

        $schedule = WorkSchedule::firstOrFail();

        $this->actingAs($admin)
            ->from($origen)
            ->delete("/schedules/{$schedule->id}")
            ->assertRedirect($origen);

        $this->actingAs($admin)
            ->from('/gestion?tab=festivos')
            ->post("/businesses/{$business->id}/holidays", ['date' => '2026-12-08'])
            ->assertRedirect('/gestion?tab=festivos');

        $this->actingAs($admin)
            ->from('/gestion?tab=servicios')
            ->post("/businesses/{$business->id}/services", [
                'name' => 'Semipermanente',
                'duration_minutes' => 60,
                'price' => 50000,
                'professional_id' => $professional->id,
            ])
            ->assertRedirect('/gestion?tab=servicios');
    }

    public function test_horarios_tab_renders_labels_and_remembers_selection(): void
    {
        $business = Business::factory()->create();
        $admin = $this->adminOf($business);
        $professional = Professional::create(['name' => 'Stefania', 'business_id' => $business->id]);

        $this->actingAs($admin)
            ->get('/gestion?tab=horarios&profesional='.$professional->id.'&dia=1')
            ->assertOk()
            ->assertSee("tab: 'horarios'", false)
            ->assertSee("profesional: '".$professional->id."'", false)
            ->assertSee("dia: '1'", false)
            ->assertSee('Profesional')
            ->assertSee('Día de la semana')
            ->assertSee('Hora de inicio')
            ->assertSee('Hora de finalización')
            ->assertSee('Descanso');
    }

    public function test_management_screen_keeps_responsive_layout(): void
    {
        $business = Business::factory()->create();
        $professional = Professional::create(['name' => 'Stefania', 'business_id' => $business->id]);
        WorkSchedule::create([
            'professional_id' => $professional->id,
            'weekday' => 0,
            'start_time' => '09:00',
            'end_time' => '13:00',
        ]);

        $this->actingAs($this->adminOf($business))
            ->get('/gestion')
            ->assertOk()
            ->assertSee('grid-cols-1 sm:grid-cols-2 lg:grid-cols-3', false)
            ->assertSee('overflow-x-auto');
    }

    public function test_forms_render_submit_buttons(): void
    {
        $this->get('/login')->assertOk()->assertSee('type="submit"', false);
        $this->get('/forgot-password')->assertOk()->assertSee('type="submit"', false);

        $user = User::factory()->create(['role' => UserRole::Professional]);
        $this->actingAs($user)->get('/configuracion')->assertOk()->assertSee('type="submit"', false);
    }
}
