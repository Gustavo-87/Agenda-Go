<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Invitation;
use App\Models\User;
use App\Notifications\InvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InvitationsTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => UserRole::SuperAdmin]);
    }

    public function test_an_invitation_creates_the_account_after_setting_the_password(): void
    {
        Notification::fake();
        $business = Business::factory()->create();
        $super = $this->superAdmin();

        $this->actingAs($super)
            ->postJson("/businesses/{$business->id}/invitations", [
                'email' => 'ana@viglam.test',
                'role' => 'admin',
            ])
            ->assertStatus(201);

        $token = null;
        Notification::assertSentOnDemand(InvitationNotification::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->post('/logout');

        $this->get('/invitations/accept?token='.$token)->assertOk()->assertSee('ana@viglam.test');

        $this->post('/invitations/accept', [
            'token' => $token,
            'name' => 'Ana Admin',
            'password' => 'password-secreta',
            'password_confirmation' => 'password-secreta',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', [
            'email' => 'ana@viglam.test',
            'name' => 'Ana Admin',
            'business_id' => $business->id,
            'role' => 'admin',
        ]);

        $invitacion = Invitation::where('email', 'ana@viglam.test')->firstOrFail();
        $this->assertNotNull($invitacion->accepted_at);
    }

    public function test_invitation_tokens_are_temporary_and_single_use(): void
    {
        $business = Business::factory()->create();
        $token = 'token-unico';

        Invitation::create([
            'business_id' => $business->id,
            'role' => 'professional',
            'email' => 'pro@viglam.test',
            'token' => hash('sha256', $token),
            'expires_at' => now()->subHour(),
        ]);

        $this->get('/invitations/accept?token='.$token)->assertStatus(404);

        $vigente = Invitation::create([
            'business_id' => $business->id,
            'role' => 'professional',
            'email' => 'pro2@viglam.test',
            'token' => hash('sha256', 'token-vigente'),
            'expires_at' => now()->addDay(),
        ]);

        $payload = [
            'token' => 'token-vigente',
            'name' => 'Pro',
            'password' => 'password-secreta',
            'password_confirmation' => 'password-secreta',
        ];

        $this->post('/invitations/accept', $payload)->assertRedirect(route('login'));
        $this->post('/invitations/accept', $payload)->assertStatus(404);

        $this->assertNotNull($vigente->fresh()->accepted_at);
    }

    public function test_business_admins_can_only_invite_professionals_to_their_own_business(): void
    {
        Notification::fake();
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        $admin = User::factory()->create([
            'business_id' => $business->id,
            'role' => UserRole::Admin,
        ]);
        $foreign = User::factory()->create([
            'business_id' => $otherBusiness->id,
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($admin)
            ->postJson("/businesses/{$business->id}/invitations", ['email' => 'admin@viglam.test', 'role' => 'admin'])
            ->assertStatus(403);

        $this->actingAs($foreign)
            ->postJson("/businesses/{$business->id}/invitations", ['email' => 'intrusa@viglam.test', 'role' => 'professional'])
            ->assertStatus(403);

        $this->actingAs($admin)
            ->postJson("/businesses/{$business->id}/invitations", ['email' => 'pro@viglam.test', 'role' => 'professional'])
            ->assertStatus(201);

        $this->assertDatabaseHas('invitations', ['email' => 'pro@viglam.test', 'role' => 'professional']);
    }

    public function test_invitations_can_be_resent_and_revoked(): void
    {
        Notification::fake();
        $business = Business::factory()->create();
        $super = $this->superAdmin();

        $invitationId = $this->actingAs($super)
            ->postJson("/businesses/{$business->id}/invitations", ['email' => 'pro@viglam.test', 'role' => 'professional'])
            ->assertStatus(201)
            ->json('invitation_id');

        $tokens = [];
        Notification::assertSentOnDemand(InvitationNotification::class, function ($notification) use (&$tokens) {
            $tokens[] = $notification->token;

            return true;
        });

        $this->actingAs($super)
            ->postJson("/invitations/{$invitationId}/resend")
            ->assertStatus(200);

        Notification::assertSentOnDemandTimes(InvitationNotification::class, 2);
        Notification::assertSentOnDemand(InvitationNotification::class, function ($notification) use (&$tokens) {
            if (! in_array($notification->token, $tokens, true)) {
                $tokens[] = $notification->token;
            }

            return true;
        });

        $this->post('/logout');

        $this->get('/invitations/accept?token='.$tokens[0])->assertStatus(404);
        $this->get('/invitations/accept?token='.$tokens[1])->assertOk();

        $segunda = $this->actingAs($super)
            ->postJson("/businesses/{$business->id}/invitations", ['email' => 'otra@viglam.test', 'role' => 'professional'])
            ->json('invitation_id');

        $this->actingAs($super)->deleteJson("/invitations/{$segunda}")->assertStatus(200);

        $this->post('/logout');

        $this->get('/invitations/accept?token='.Invitation::find($segunda)->token)->assertStatus(404);
    }

    public function test_invitation_management_is_authorized(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        $admin = User::factory()->create(['business_id' => $business->id, 'role' => UserRole::Admin]);
        $foreign = User::factory()->create(['business_id' => $otherBusiness->id, 'role' => UserRole::Admin]);

        $profesional = Invitation::create([
            'business_id' => $business->id,
            'role' => 'professional',
            'email' => 'pro@viglam.test',
            'token' => hash('sha256', 'tok-1'),
            'expires_at' => now()->addDay(),
        ]);
        $deAdmin = Invitation::create([
            'business_id' => $business->id,
            'role' => 'admin',
            'email' => 'jefe@viglam.test',
            'token' => hash('sha256', 'tok-2'),
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($foreign)->deleteJson("/invitations/{$profesional->id}")->assertStatus(403);
        $this->actingAs($admin)->deleteJson("/invitations/{$deAdmin->id}")->assertStatus(403);
        $this->actingAs($admin)->deleteJson("/invitations/{$profesional->id}")->assertStatus(200);

        $this->assertNotNull($profesional->fresh()->accepted_at);
        $this->assertNull($deAdmin->fresh()->accepted_at);
    }

    public function test_expired_invitations_cannot_be_accepted(): void
    {
        $business = Business::factory()->create();

        Invitation::create([
            'business_id' => $business->id,
            'role' => 'professional',
            'email' => 'tardia@viglam.test',
            'token' => hash('sha256', 'tok-vencido'),
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->travel(10)->minutes();

        $this->post('/invitations/accept', [
            'token' => 'tok-vencido',
            'name' => 'Tardía',
            'password' => 'password-secreta',
            'password_confirmation' => 'password-secreta',
        ])->assertStatus(404);

        $this->assertDatabaseMissing('users', ['email' => 'tardia@viglam.test']);
    }

    public function test_existing_users_cannot_be_invited_again(): void
    {
        $business = Business::factory()->create();
        User::factory()->create(['email' => 'ana@viglam.test']);

        $this->actingAs($this->superAdmin())
            ->postJson("/businesses/{$business->id}/invitations", ['email' => 'ana@viglam.test', 'role' => 'admin'])
            ->assertStatus(422);
    }
}
