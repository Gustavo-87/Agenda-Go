<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Membership;
use App\Models\MembershipPayment;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Panel principal por rol: plataforma para el superadministrador,
     * negocio para el administrador y agenda personal para el
     * profesional.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return $this->panelPlataforma();
        }

        $negocio = $this->contextoNegocio($request);
        $citas = $this->citasDelContexto($request);
        $puedeGestionar = $negocio !== null && $user->can('manage', $negocio);

        $datos = [
            'panelPlataforma' => false,
            'negocio' => $negocio,
            'puedeGestionar' => $puedeGestionar,
            'proximas' => (clone $citas)
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('starts_at', '>=', now())
                ->orderBy('starts_at')->limit(5)->get(),
        ];

        if ($puedeGestionar) {
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

    /**
     * Indicadores de la plataforma para el propietario de AgendaGo.
     */
    private function panelPlataforma()
    {
        $hoy = now()->toDateString();

        return view('dashboard', [
            'panelPlataforma' => true,
            'negocio' => null,
            'puedeGestionar' => false,
            'proximas' => collect(),
            'negociosTotal' => Business::count(),
            'negociosActivos' => Business::where('active', true)->count(),
            'membresiasActivas' => Membership::where('status', 'active')->count(),
            'membresiasPorVencer' => Membership::where('status', 'active')
                ->whereBetween('ends_on', [$hoy, now()->addDays(5)->toDateString()])->count(),
            'ingresosMembresias' => (int) MembershipPayment::where('verified', true)
                ->whereBetween('paid_on', [now()->startOfMonth()->toDateString(), $hoy])->sum('amount'),
            'vencimientos' => Membership::where('status', 'active')
                ->whereBetween('ends_on', [$hoy, now()->addDays(5)->toDateString()])
                ->with('business')->orderBy('ends_on')->get(),
            'negociosRecientes' => Business::latest()->limit(5)->get(),
        ]);
    }
}
