<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Notifications\TwoFactorCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if ($user->two_factor_enabled || in_array($user->role?->value, ['admin', 'super_admin'], true)) {
            $codigo = (string) random_int(100000, 999999);

            Cache::put('two_factor_'.$user->id, $codigo, now()->addMinutes(10));
            $request->session()->put('two_factor_user_id', $user->id);

            Auth::guard('web')->logout();
            $request->session()->regenerate();

            $user->notify(new TwoFactorCode($codigo));

            return redirect()->route('2fa.show');
        }

        $request->session()->put('two_factor_verified', true);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
