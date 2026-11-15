<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Business;
use App\Models\Invitation;
use App\Models\User;
use App\Notifications\InvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    /**
     * Invitación segura por correo: el superadministrador invita
     * administradores y profesionales; la administración de un
     * negocio solo puede invitar profesionales de su propio negocio.
     */
    public function store(Request $request, Business $business)
    {
        $user = $request->user();

        abort_unless($user->isSuperAdmin() || $user->can('manage', $business), 403);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:admin,professional'],
        ]);

        if (! $user->isSuperAdmin() && $data['role'] !== 'professional') {
            abort(403, 'Solo el superadministrador puede invitar administradores.');
        }

        Invitation::where('email', $data['email'])
            ->whereNull('accepted_at')
            ->update(['accepted_at' => now()]);

        $token = Str::random(64);

        $invitation = Invitation::create([
            'business_id' => $business->id,
            'role' => $data['role'],
            'email' => $data['email'],
            'token' => hash('sha256', $token),
            'created_by' => $user->id,
            'expires_at' => now()->addHours(48),
        ]);

        Notification::route('mail', $data['email'])
            ->notify(new InvitationNotification($invitation, $token));

        AppNotification::registrar(
            $business,
            null,
            'invitacion',
            'Invitación enviada a '.$data['email'].' como '.($data['role'] === 'admin' ? 'administrador' : 'profesional').'.'
        );

        return $this->respuesta($request, 'Invitación enviada', ['invitation_id' => $invitation->id], 201);
    }

    public function acceptForm(Request $request)
    {
        return view('invitations.accept', [
            'invitation' => $this->invitationValida($request),
        ]);
    }

    public function accept(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $invitation = $this->invitationValida($request->merge(['token' => $data['token']]));

        User::create([
            'name' => $data['name'],
            'email' => $invitation->email,
            'password' => $data['password'],
            'business_id' => $invitation->business_id,
            'role' => $invitation->role,
        ]);

        $invitation->update(['accepted_at' => now()]);

        return redirect()->route('login')->with('success', 'Cuenta creada. Ya puedes iniciar sesión.');
    }

    /**
     * Reenvío seguro: invalida la invitación anterior y emite una
     * nueva con enlace y vigencia propios.
     */
    public function resend(Request $request, Invitation $invitation)
    {
        $this->puedeGestionarInvitacion($request, $invitation);

        abort_unless($invitation->accepted_at === null, 422);

        $invitation->update(['accepted_at' => now()]);

        $token = Str::random(64);

        $nueva = Invitation::create([
            'business_id' => $invitation->business_id,
            'role' => $invitation->role,
            'email' => $invitation->email,
            'token' => hash('sha256', $token),
            'created_by' => $request->user()->id,
            'expires_at' => now()->addHours(48),
        ]);

        Notification::route('mail', $nueva->email)
            ->notify(new InvitationNotification($nueva, $token));

        return $this->respuesta($request, 'Invitación reenviada', ['invitation_id' => $nueva->id]);
    }

    /**
     * Revocación: la invitación queda sin efecto sin eliminar su
     * registro histórico.
     */
    public function revoke(Request $request, Invitation $invitation)
    {
        $this->puedeGestionarInvitacion($request, $invitation);

        abort_unless($invitation->accepted_at === null, 422);

        $invitation->update(['accepted_at' => now()]);

        return $this->respuesta($request, 'Invitación revocada');
    }

    /**
     * El superadministrador gestiona todas las invitaciones; cada
     * negocio solo las de profesionales de su propio negocio.
     */
    private function puedeGestionarInvitacion(Request $request, Invitation $invitation): void
    {
        $user = $request->user();

        $autorizado = $user->isSuperAdmin()
            || ($invitation->role === 'professional' && $user->can('manage', $invitation->business));

        abort_unless($autorizado, 403);
    }

    /**
     * La invitación debe existir, no haberse usado y no estar vencida.
     */
    private function invitationValida(Request $request): Invitation
    {
        $token = (string) $request->input('token', $request->query('token', ''));

        return Invitation::where('token', hash('sha256', $token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();
    }
}
