<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Membership;
use App\Models\MembershipPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Panel del superadministrador: altas, activación, membresías,
 * pagos y renovaciones (Sprint 5).
 */
class AdminController extends Controller
{
    /**
     * Panel del superadministrador: negocios, membresías y pagos.
     */
    public function pantalla(Request $request)
    {
        $this->assertSuperAdmin($request);

        return view('admin.index', [
            'negocios' => Business::with('memberships')->orderBy('id')->get(),
            'pagosPendientes' => MembershipPayment::where('verified', false)
                ->with('business')->orderByDesc('id')->limit(10)->get(),
        ]);
    }

    public function businesses(Request $request)
    {
        $this->assertSuperAdmin($request);

        return response()->json([
            'data' => Business::with('memberships')->orderBy('id')->get(),
        ]);
    }

    public function storeBusiness(Request $request)
    {
        $this->assertSuperAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'unique:businesses,slug'],
            'sector' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $business = Business::create($data);

        return $this->respuesta($request, 'Negocio registrado', ['business_id' => $business->id], 201);
    }

    public function activate(Request $request, Business $business)
    {
        $this->assertSuperAdmin($request);

        $business->update(['active' => true]);
        AuditLog::registrar($request->user(), 'negocio.activado', $business);

        return $this->respuesta($request, 'Negocio activado');
    }

    public function suspend(Request $request, Business $business)
    {
        $this->assertSuperAdmin($request);

        $business->update(['active' => false]);
        $business->memberships()->update(['status' => 'suspended']);
        AuditLog::registrar($request->user(), 'negocio.suspendido', $business);

        return $this->respuesta($request, 'Negocio suspendido');
    }

    public function registerMembership(Request $request, Business $business)
    {
        $this->assertSuperAdmin($request);

        $data = $request->validate([
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after:starts_on'],
            'amount' => ['required', 'integer', 'min:0'],
            'paid_on' => ['required', 'date_format:Y-m-d'],
            'method' => ['nullable', 'string', 'max:60'],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        $membership = Membership::create([
            'business_id' => $business->id,
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'amount' => $data['amount'],
            'status' => 'active',
        ]);

        $payment = MembershipPayment::create([
            'business_id' => $business->id,
            'membership_id' => $membership->id,
            'amount' => $data['amount'],
            'paid_on' => $data['paid_on'],
            'method' => $data['method'] ?? null,
            'reference' => $data['reference'] ?? null,
        ]);

        return $this->respuesta($request, 'Membresía registrada', [
            'membership_id' => $membership->id,
            'payment_id' => $payment->id,
        ], 201);
    }

    public function renew(Request $request, Membership $membership)
    {
        $this->assertSuperAdmin($request);

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:0'],
            'paid_on' => ['required', 'date_format:Y-m-d'],
            'method' => ['nullable', 'string', 'max:60'],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        $inicio = Carbon::parse($membership->ends_on)->addDay()->toDateString();
        $fin = Carbon::parse($inicio)->addMonth()->subDay()->toDateString();

        $nueva = Membership::create([
            'business_id' => $membership->business_id,
            'starts_on' => $inicio,
            'ends_on' => $fin,
            'amount' => $data['amount'],
            'status' => 'active',
        ]);

        $payment = MembershipPayment::create([
            'business_id' => $membership->business_id,
            'membership_id' => $nueva->id,
            'amount' => $data['amount'],
            'paid_on' => $data['paid_on'],
            'method' => $data['method'] ?? null,
            'reference' => $data['reference'] ?? null,
        ]);

        return $this->respuesta($request, 'Membresía renovada', [
            'membership_id' => $nueva->id,
            'payment_id' => $payment->id,
        ], 201);
    }

    public function verifyPayment(Request $request, MembershipPayment $payment)
    {
        $this->assertSuperAdmin($request);

        $payment->update(['verified' => true]);
        $payment->membership->update(['status' => 'active']);
        $payment->business->update(['active' => true]);
        AuditLog::registrar($request->user(), 'pago.membresia.verificado', $payment);

        return $this->respuesta($request, 'Pago verificado y servicio habilitado');
    }

    private function assertSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
    }
}
