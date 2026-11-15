<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleCalendarController extends Controller
{
    public function redirect(Request $request, Professional $professional)
    {
        abort_unless($this->puedeGestionar($request->user(), $professional), 403);

        $state = Str::random(40);

        $request->session()->put('google_oauth', [
            'state' => $state,
            'professional_id' => $professional->id,
        ]);

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?'.
            http_build_query([
                'client_id' => config('services.google_calendar.client_id'),
                'redirect_uri' => config('services.google_calendar.redirect_uri'),
                'response_type' => 'code',
                'scope' => 'https://www.googleapis.com/auth/calendar.events https://www.googleapis.com/auth/calendar.freebusy',
                'access_type' => 'offline',
                'prompt' => 'consent',
                'state' => $state,
            ]);

        return redirect()->away($url);
    }

    public function callback(Request $request)
    {
        $oauth = $request->session()->pull('google_oauth');

        abort_unless(
            $oauth &&
            isset($oauth['state'], $oauth['professional_id']) &&
            is_string($request->query('state')) &&
            hash_equals($oauth['state'], $request->query('state')),
            403,
            'Autorización inválida'
        );

        $professional = Professional::findOrFail($oauth['professional_id']);

        abort_unless($this->puedeGestionar($request->user(), $professional), 403);

        $code = $request->query('code');

        abort_unless(is_string($code) && $code !== '', 400);

        $response = Http::asForm()->post(
            'https://oauth2.googleapis.com/token',
            [
                'client_id' => config('services.google_calendar.client_id'),
                'client_secret' => config('services.google_calendar.client_secret'),
                'redirect_uri' => config('services.google_calendar.redirect_uri'),
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]
        );

        if ($response->failed()) {
            return redirect('/dashboard')
                ->with('error', 'No se pudo conectar Google Calendar.');
        }

        $tokens = $response->json();

        $professional->google_access_token = $tokens['access_token'];
        $professional->google_refresh_token =
            $tokens['refresh_token']
            ?? $professional->google_refresh_token;

        $professional->google_token_expires_at =
            now()->addSeconds($tokens['expires_in'] ?? 3600);

        $professional->google_calendar_id = 'primary';
        $professional->save();

        return redirect('/dashboard')
            ->with('success', 'Google Calendar conectado correctamente.');
    }

    /**
     * The professional's own user, or a user allowed to manage
     * the professional's business (admin or super admin).
     */
    private function puedeGestionar(User $user, Professional $professional): bool
    {
        return $professional->user_id === $user->id
            || ($professional->business !== null && $user->can('manage', $professional->business));
    }
}
