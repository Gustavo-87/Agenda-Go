<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function show(): View
    {
        return view('2fa');
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'size:6']]);

        $userId = $request->session()->get('two_factor_user_id');
        $esperado = $userId !== null ? Cache::get('two_factor_'.$userId) : null;

        if ($esperado === null || ! hash_equals((string) $esperado, $data['code'])) {
            return back()->withErrors(['code' => 'Código inválido.']);
        }

        Cache::forget('two_factor_'.$userId);

        Auth::login(User::findOrFail($userId));
        $request->session()->regenerate();
        $request->session()->put('two_factor_verified', true);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Habilita o deshabilita el segundo factor del usuario.
     */
    public function enable(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['two_factor_enabled' => ['required', 'boolean']]);

        $request->user()->update([
            'two_factor_enabled' => (bool) $data['two_factor_enabled'],
        ]);

        return $this->respuesta($request, 'Configuración de dos factores actualizada');
    }
}
