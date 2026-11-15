<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\MembershipPayment;
use App\Models\ServicePayment;
use Dompdf\Dompdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

        return response()->json([
            'desde' => $rango[0],
            'hasta' => $rango[1],
            ...$this->resumen($business, $rango[0], $rango[1]),
        ]);
    }

    /**
     * Pantalla de reportes operativos del negocio.
     */
    public function pantalla(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isAdmin(), 403);

        $business = $this->contextoNegocio($request);

        if ($business !== null) {
            abort_unless($user->can('manage', $business), 403);
        }

        $desde = (string) $request->query('from', now()->subDays(30)->toDateString());
        $hasta = (string) $request->query('to', now()->toDateString());
        $request->merge(['from' => $desde, 'to' => $hasta]);

        return view('reports.index', [
            'negocio' => $business,
            'desde' => $desde,
            'hasta' => $hasta,
            'resumen' => $business === null ? null : $this->resumen($business, $desde, $hasta),
            'citas' => $business === null ? collect() : $this->citas($request, $business)->paginate(15)->withQueryString(),
            'pagos' => $business === null ? collect() : ServicePayment::where('business_id', $business->id)
                ->orderByDesc('paid_on')->limit(20)->get(),
            'negocios' => $user->isSuperAdmin() ? Business::orderBy('name')->get() : collect(),
        ]);
    }

    /**
     * Indicadores operativos del periodo.
     *
     * @return array<string, mixed>
     */
    private function resumen(Business $business, string $desde, string $hasta): array
    {
        $citas = $business->appointments()
            ->whereBetween('starts_at', [$desde.' 00:00:00', $hasta.' 23:59:59'])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'citas_por_estado' => $citas,
            'ingresos_servicios' => ServicePayment::where('business_id', $business->id)
                ->whereBetween('paid_on', [$desde, $hasta])
                ->where('verified', true)
                ->sum('amount'),
            'ingresos_membresias' => MembershipPayment::where('business_id', $business->id)
                ->whereBetween('paid_on', [$desde, $hasta])
                ->where('verified', true)
                ->sum('amount'),
        ];
    }

    public function appointmentsCsv(Request $request, Business $business)
    {
        abort_unless($request->user()->can('manage', $business), 403);

        $citas = $this->citas($request, $business)->get();

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
     * Exportación Excel (xlsx) del reporte de citas.
     */
    public function appointmentsXlsx(Request $request, Business $business)
    {
        abort_unless($request->user()->can('manage', $business), 403);

        $citas = $this->citas($request, $business)->get();

        $hoja = new Spreadsheet;
        $activa = $hoja->getActiveSheet();
        $activa->fromArray(['Fecha', 'Profesional', 'Servicio', 'Cliente', 'Teléfono', 'Estado'], null, 'A1');

        $fila = 2;
        foreach ($citas as $cita) {
            $activa->fromArray([
                $cita->starts_at,
                $cita->professional_name,
                $cita->service_name,
                $cita->customer_name,
                $cita->customer_phone,
                $cita->status,
            ], null, 'A'.$fila);
            $fila++;
        }

        $temporal = tempnam(sys_get_temp_dir(), 'citas');
        (new Xlsx($hoja))->save($temporal);
        $contenido = (string) file_get_contents($temporal);
        unlink($temporal);

        return response($contenido, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="citas.xlsx"',
        ]);
    }

    /**
     * Exportación PDF del reporte de citas.
     */
    public function appointmentsPdf(Request $request, Business $business)
    {
        abort_unless($request->user()->can('manage', $business), 403);

        $rango = $this->rango($request);
        $citas = $this->citas($request, $business)->get();

        $html = view('reports.appointments-pdf', [
            'business' => $business,
            'citas' => $citas,
            'desde' => $rango[0],
            'hasta' => $rango[1],
        ])->render();

        $pdf = new Dompdf(['orientation' => 'landscape']);
        $pdf->loadHtml($html);
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="citas.pdf"',
        ]);
    }

    /**
     * Citas del negocio en el rango solicitado, con nombres de
     * profesional y servicio.
     *
     * @return Builder<int, Appointment>
     */
    private function citas(Request $request, Business $business)
    {
        $rango = $this->rango($request);

        return $business->appointments()
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
            ->orderBy('appointments.starts_at');
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
