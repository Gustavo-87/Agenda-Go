<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Membership;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Panel principal con indicadores y accesos rápidos, con
     * alcance limitado al negocio del usuario.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $negocio = $this->contextoNegocio($request);
        $citas = $this->citasDelContexto($request);

        $datos = [
            'negocio' => $negocio,
            'puedeGestionar' => $negocio !== null && $user->can('manage', $negocio),
            'proximas' => (clone $citas)
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('starts_at', '>=', now())
                ->orderBy('starts_at')->limit(5)->get(),
        ];

        if ($user->isSuperAdmin()) {
            $datos += [
                'negociosTotal' => Business::count(),
                'negociosActivos' => Business::where('active', true)->count(),
                'membresiasPorVencer' => Membership::where('status', 'active')
                    ->whereBetween('ends_on', [now()->toDateString(), now()->addDays(5)->toDateString()])
                    ->count(),
                'citasHoy' => (clone $citas)->whereDate('starts_at', now('America/Bogota')->toDateString())->count(),
            ];
        } elseif ($negocio !== null) {
            $datos += [
                'citasHoy' => (clone $citas)->whereDate('starts_at', now('America/Bogota')->toDateString())
                    ->whereIn('status', ['pending', 'confirmed'])->count(),
                'pendientes' => (clone $citas)->where('status', 'pending')->count(),
                'profesionalesActivos' => $negocio->professionals()->where('active', true)->count(),
                'serviciosActivos' => $negocio->services()->where('active', true)->count(),
                'clientes' => $negocio->customers()->count(),
            ];
        }

        return view('dashboard', $datos);
    }
}
