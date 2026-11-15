<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\ServicePayment;
use Illuminate\Http\Request;

/**
 * Pagos de servicios: recaudo directo por negocio (RN-036/037),
 * verificación y comprobante digital. Los pagos de servicios se
 * registran separados de las membresías (RN-042).
 */
class PaymentController extends Controller
{
    public function store(Request $request, Business $business)
    {
        abort_unless($request->user()->can('manage', $business), 403);

        $data = $request->validate([
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'amount' => ['required', 'integer', 'min:0'],
            'paid_on' => ['required', 'date_format:Y-m-d'],
            'method' => ['nullable', 'string', 'max:60'],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        if (isset($data['appointment_id'])) {
            $pertenece = Appointment::whereKey($data['appointment_id'])
                ->where('business_id', $business->id)
                ->exists();

            if (! $pertenece) {
                abort(403);
            }
        }

        $payment = ServicePayment::create([
            ...$data,
            'business_id' => $business->id,
            'created_by' => $request->user()->id,
        ]);

        return $this->respuesta($request, 'Pago registrado', ['payment_id' => $payment->id], 201);
    }

    public function verify(Request $request, ServicePayment $payment)
    {
        abort_unless($payment->business !== null && $request->user()->can('manage', $payment->business), 403);

        $payment->update(['verified' => true]);
        AuditLog::registrar($request->user(), 'pago.servicio.verificado', $payment);

        return $this->respuesta($request, 'Pago verificado');
    }

    public function receipt(Request $request, ServicePayment $payment)
    {
        abort_unless($payment->business !== null && $request->user()->can('view', $payment->business), 403);

        return response()->json([
            'comprobante' => [
                'negocio' => $payment->business?->name,
                'referencia' => $payment->reference,
                'concepto' => 'Pago de servicios',
                'monto' => $payment->amount,
                'fecha' => $payment->paid_on,
                'metodo' => $payment->method,
                'verificado' => $payment->verified,
            ],
        ]);
    }
}
