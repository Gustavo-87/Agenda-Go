<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use App\Notifications\TwoFactorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SaaSAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => UserRole::SuperAdmin]);
    }

    public function test_super_admin_dashboard_shows_platform_indicators(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Panel de la plataforma')
            ->assertSee('Ingresos de membresías')
            ->assertDontSee('>Agenda<');
    }

    public function test_super_admin_cannot_manage_client_agendas(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($super)->get('/agenda')->assertStatus(403);
        $this->actingAs($super)->get('/clientes')->assertStatus(403);
        $this->actingAs($super)->get('/gestion')->assertStatus(403);
        $this->actingAs($super)->get('/reportes')->assertStatus(403);
    }

    public function test_business_can_be_edited_and_its_users_managed(): void
    {
        $super = $this->superAdmin();

        $businessId = $this->actingAs($super)
            ->from('/admin')
            ->post('/admin/businesses', ['name' => 'Viglam', 'slug' => 'viglam'])
            ->assertRedirect()
            ->assertSessionHas('success')
            ->getSession()->get('_previous.url') ? Business::where('slug', 'viglam')->value('id') : null;

        $business = Business::where('slug', 'viglam')->firstOrFail();

        $this->actingAs($super)
            ->from('/admin/businesses/'.$business->id)
            ->patch('/admin/businesses/'.$business->id, [
                'name' => 'Viglam SAS',
                'slug' => 'viglam',
                'sector' => 'belleza',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'name' => 'Viglam SAS', 'sector' => 'belleza']);

        $this->actingAs($super)
            ->post('/admin/businesses/'.$business->id.'/users', [
                'name' => 'Ana Admin',
                'email' => 'ana@viglam.test',
                'password' => 'password-secreta',
                'role' => 'admin',
            ])
            ->assertSessionHas('success');

        $user = User::where('email', 'ana@viglam.test')->firstOrFail();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'business_id' => $business->id, 'role' => 'admin']);

        $this->actingAs($super)
            ->patch('/admin/users/'.$user->id, ['role' => 'professional'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'professional']);

        $this->actingAs($super)
            ->delete('/admin/users/'.$user->id.'/assignment')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'business_id' => null]);
    }

    public function test_business_admins_cannot_manage_users_of_the_platform(): void
    {
        $business = Business::factory()->create();
        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($admin)
            ->post('/admin/businesses/'.$business->id.'/users', [
                'name' => 'Intruso',
                'email' => 'intruso@viglam.test',
                'password' => 'password-secreta',
                'role' => 'admin',
            ])
            ->assertStatus(403);

        $this->actingAs($admin)->get('/admin/businesses/'.$business->id)->assertStatus(403);
    }

    public function test_end_to_end_business_registration_with_first_admin(): void
    {
        Notification::fake();
        $super = $this->superAdmin();

        $this->actingAs($super)->post('/admin/businesses', ['name' => 'Viglam', 'slug' => 'viglam'])->assertSessionHas('success');

        $business = Business::where('slug', 'viglam')->firstOrFail();

        $this->actingAs($super)->post('/admin/businesses/'.$business->id.'/users', [
            'name' => 'Ana Admin',
            'email' => 'ana@viglam.test',
            'password' => 'password-secreta',
            'role' => 'admin',
        ]);

        $this->post('/logout');

        $this->post('/login', ['email' => 'ana@viglam.test', 'password' => 'password-secreta'])
            ->assertRedirect(route('2fa.show'));

        $codigo = null;
        Notification::assertSentTo(
            User::where('email', 'ana@viglam.test')->first(),
            TwoFactorCode::class,
            function ($notification) use (&$codigo) {
                $codigo = $notification->codigo;

                return true;
            }
        );

        $this->post('/2fa', ['code' => $codigo])->assertRedirect(route('dashboard', absolute: false));

        $this->get('/gestion')->assertOk()->assertSee('Viglam');
    }

    public function test_professionals_can_be_linked_to_business_users(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);
        $professional = Professional::create(['name' => 'Stefania', 'business_id' => $business->id]);
        $ownUser = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Professional,
        ]);
        $foreignUser = User::factory()->create([
            'business_id' => $otherBusiness->id,
            'role' => UserRole::Professional,
        ]);

        $this->actingAs($admin)
            ->patchJson('/professionals/'.$professional->id.'/user', ['user_id' => $foreignUser->id])
            ->assertStatus(422);

        $this->actingAs($admin)
            ->patchJson('/professionals/'.$professional->id.'/user', ['user_id' => $ownUser->id])
            ->assertStatus(200);

        $this->assertDatabaseHas('professionals', ['id' => $professional->id, 'user_id' => $ownUser->id]);
    }

    public function test_professional_dashboard_shows_only_their_own_agenda(): void
    {
        $businessA = Business::factory()->create();
        $businessB = Business::factory()->create();

        $userA = User::factory()->create(['business_id' => $businessA->id, 'role' => UserRole::Professional]);
        $userB = User::factory()->create(['business_id' => $businessB->id, 'role' => UserRole::Professional]);

        $professionalA = Professional::create(['name' => 'Stefania', 'business_id' => $businessA->id, 'user_id' => $userA->id]);
        $professionalB = Professional::create(['name' => 'Laura', 'business_id' => $businessB->id, 'user_id' => $userB->id]);

        $fecha = now('America/Bogota')->addDays(2)->format('Y-m-d');

        foreach ([[$professionalA, 'Cliente Propio'], [$professionalB, 'Cliente Ajeno']] as [$professional, $cliente]) {
            $service = Service::create([
                'name' => 'Servicio '.$cliente,
                'duration_minutes' => 60,
                'price' => 50000,
                'professional_id' => $professional->id,
                'business_id' => $professional->business_id,
            ]);

            Appointment::forceCreate([
                'professional_id' => $professional->id,
                'service_id' => $service->id,
                'business_id' => $professional->business_id,
                'customer_name' => $cliente,
                'customer_phone' => '300',
                'starts_at' => "{$fecha} 09:00:00",
                'ends_at' => "{$fecha} 10:00:00",
                'status' => 'confirmed',
            ]);
        }

        $this->actingAs($userA)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Cliente Propio')
            ->assertDontSee('Cliente Ajeno');
    }

    public function test_the_profile_page_is_displayed_in_spanish(): void
    {
        $user = User::factory()->create(['role' => UserRole::Professional]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Información del perfil')
            ->assertSee('Actualizar contraseña')
            ->assertSee('Eliminar cuenta');
    }
}
