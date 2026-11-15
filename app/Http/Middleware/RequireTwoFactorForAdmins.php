<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RN-043: los administradores usan autenticación de dos factores.
 * El inicio de sesión siempre marca la verificación; con 2FA
 * habilitado solo se marca tras validar el código.
 */
class RequireTwoFactorForAdmins
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $requerido = $user !== null && in_array($user->role?->value, ['admin', 'super_admin'], true);

        if ($requerido && ! $request->session()->get('two_factor_verified')) {
            abort(403, 'Se requiere verificación de dos factores.');
        }

        return $next($request);
    }
}
