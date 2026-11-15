<?php

namespace App\Http\Controllers;

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
