<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\MembershipPayment;
use App\Models\ServicePayment;
use Illuminate\Http\Request;

/**
 * Indicadores operativos y reportes filtrables por negocio,
 * con exportación CSV compatible con hojas de cálculo.
 */
class ReportController extends Controller
{
    public function summary(Request $request, Business $business)
    {
        abort_unless($request->user()->can('manage', $business), 403);

        $rango = $this->rango($request);

        $citas = $business->appointments()
            ->whereBetween('starts_at', [$rango[0].' 00:00:00', $rango[1].' 23:59:59'])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'desde' => $request->query('from'),
            'hasta' => $request->query('to'),
            'citas_por_estado' => $citas,
            'ingresos_servicios' => ServicePayment::where('business_id', $business->id)
                ->whereBetween('paid_on', [$request->query('from'), $request->query('to')])
                ->where('verified', true)
                ->sum('amount'),
            'ingresos_membresias' => MembershipPayment::where('business_id', $business->id)
                ->whereBetween('paid_on', [$request->query('from'), $request->query('to')])
                ->where('verified', true)
                ->sum('amount'),
        ]);
    }

    public function appointmentsCsv(Request $request, Business $business)
    {
        abort_unless($request->user()->can('manage', $business), 403);

        $rango = $this->rango($request);

        $citas = $business->appointments()
            ->join('professionals', 'appointments.professional_id', '=', 'professionals.id')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->whereBetween('appointments.starts_at', [$rango[0].' 00:00:00', $rango[1].' 23:59:59'])
            ->select(
                'appointments.starts_at',
                'appointments.customer_name',
                'appointments.customer_phone',
                'appointments.status',
                'professionals.name as professional_name',
                'services.name as service_name'
            )
            ->orderBy('appointments.starts_at')
            ->get();

        $lineas = ['fecha,profesional,servicio,cliente,telefono,estado'];

        foreach ($citas as $cita) {
            $lineas[] = implode(',', [
                $cita->starts_at,
                '"'.$cita->professional_name.'"',
                '"'.$cita->service_name.'"',
                '"'.$cita->customer_name.'"',
                $cita->customer_phone,
                $cita->status,
            ]);
        }

        return response(implode("\n", $lineas), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="citas.csv"',
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function rango(Request $request): array
    {
        $data = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        return [$data['from'], $data['to']];
    }
}
