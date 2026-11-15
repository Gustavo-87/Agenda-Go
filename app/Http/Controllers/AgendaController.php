<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    public function index(Request $request)
    {
        abort_if($request->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'vista' => ['nullable', 'in:dia,semana'],
            'professional_id' => ['nullable', 'integer', 'exists:professionals,id'],
        ]);

        $date = $data['date'] ?? now('America/Bogota')->toDateString();
        $vista = $data['vista'] ?? 'dia';

        $user = $request->user();
        $business = $this->contextoNegocio($request);
        $puedeGestionar = $business !== null && $user->can('manage', $business);

        $query = $this->citasDelContexto($request);

        if ($puedeGestionar && isset($data['professional_id'])) {
            $query->where('professional_id', (int) $data['professional_id']);
        }

        $comun = [
            'date' => $date,
            'vista' => $vista,
            'puedeGestionar' => $puedeGestionar,
            'profesionales' => $puedeGestionar ? $business->professionals()->orderBy('name')->get() : collect(),
            'filtroProfesional' => $data['professional_id'] ?? null,
        ];

        if ($vista === 'semana') {
            $inicio = Carbon::parse($date)->startOfWeek();

            $dias = collect(range(0, 6))->map(fn ($d) => $inicio->copy()->addDays($d));

            $citas = (clone $query)
                ->whereBetween('starts_at', [
                    $inicio->toDateString().' 00:00:00',
                    $inicio->copy()->addDays(6)->toDateString().' 23:59:59',
                ])
                ->orderBy('starts_at')
                ->get()
                ->groupBy(fn ($cita) => substr($cita->starts_at, 0, 10));

            return view('agenda.index', [
                ...$comun,
                'dias' => $dias,
                'citasPorDia' => $citas,
                'appointments' => collect(),
            ]);
        }

        return view('agenda.index', [
            ...$comun,
            'appointments' => (clone $query)->whereDate('starts_at', $date)->orderBy('starts_at')->get(),
        ]);
    }
}
