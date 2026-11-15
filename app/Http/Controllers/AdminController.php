<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\MembershipPayment;
use App\Models\ScheduleException;
use App\Models\ServicePayment;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Panel del superadministrador: altas, activación, membresías,
 * pagos y renovaciones (Sprint 5).
 */
class AdminController extends Controller
{
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

    public function pantalla(Request $request)
    {
        $this->assertSuperAdmin($request);

        $q = trim((string) $request->query('q', ''));

        return view('admin.index', [
            'q' => $q,
            'negocios' => Business::with('memberships')
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->where('name', 'like', "%{$q}%")
                            ->orWhere('slug', 'like', "%{$q}%");
                    });
                })
                ->orderBy('name')->paginate(10)->withQueryString(),
        ]);
    }

    /**
     * Detalle del negocio: edición, administradores y membresías.
     */
    public function showBusiness(Request $request, Business $business)
    {
        $this->assertSuperAdmin($request);

        return view('admin.show', [
            'negocio' => $business->load('memberships'),
            'usuarios' => $business->users()->orderBy('name')->get(),
            'invitaciones' => Invitation::where('business_id', $business->id)
                ->whereNull('accepted_at')->orderByDesc('id')->get(),
            'pagos' => MembershipPayment::where('business_id', $business->id)
                ->orderByDesc('id')->get(),
        ]);
    }

    public function updateBusiness(Request $request, Business $business)
    {
        $this->assertSuperAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', Rule::unique('businesses', 'slug')->ignore($business->id)],
            'sector' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $business->update($data);
        AuditLog::registrar($request->user(), 'negocio.editado', $business);

        return $this->respuesta($request, 'Negocio actualizado');
    }

    /**
     * Membresías de la plataforma con estados y vencimientos.
     */
    public function pantallaMembresias(Request $request)
    {
        $this->assertSuperAdmin($request);

        $hoy = now()->toDateString();

        return view('admin.memberships', [
            'membresias' => Membership::with('business')
                ->orderByDesc('ends_on')->paginate(15),
            'vencidas' => Membership::where('status', 'active')
                ->where('ends_on', '<', $hoy)->count(),
            'negocios' => Business::orderBy('name')->get(),
            'pagosPendientes' => MembershipPayment::where('verified', false)
                ->with('business')->orderByDesc('id')->limit(10)->get(),
        ]);
    }

    /**
     * Alta segura de usuarios del negocio (incluye el primer
     * administrador).
     */
    public function storeUser(Request $request, Business $business)
    {
        $this->assertSuperAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,professional'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'business_id' => $business->id,
            'role' => $data['role'],
        ]);

        AuditLog::registrar($request->user(), 'usuario.asignado', $user, ['business_id' => $business->id]);

        return $this->respuesta($request, 'Usuario asignado al negocio', ['user_id' => $user->id], 201);
    }

    public function updateUser(Request $request, User $user)
    {
        $this->assertSuperAdmin($request);

        $data = $request->validate([
            'role' => ['required', 'in:admin,professional'],
            'business_id' => ['sometimes', 'nullable', 'integer', 'exists:businesses,id'],
        ]);

        $campos = ['role' => $data['role']];

        if (array_key_exists('business_id', $data)) {
            $campos['business_id'] = $data['business_id'];
        }

        $user->update($campos);

        AuditLog::registrar($request->user(), 'usuario.editado', $user);

        return $this->respuesta($request, 'Usuario actualizado');
    }

    public function detachUser(Request $request, User $user)
    {
        $this->assertSuperAdmin($request);

        $user->update(['business_id' => null]);
        AuditLog::registrar($request->user(), 'usuario.desvinculado', $user);

        return $this->respuesta($request, 'Usuario desvinculado del negocio');
    }

    /**
     * RF-MEM-006: cancelación sin permanencia mínima; el servicio se
     * conserva hasta finalizar el período pagado (con gracia).
     */
    public function cancelMembership(Request $request, Membership $membership)
    {
        $this->assertSuperAdmin($request);

        abort_unless($membership->status === 'active', 422);

        $membership->update(['status' => 'cancelled']);
        AuditLog::registrar($request->user(), 'membresia.cancelada', $membership);

        return $this->respuesta(
            $request,
            'Membresía cancelada. El servicio se conserva hasta finalizar el período pagado.'
        );
    }

    /**
     * RF-MEN-001: cuota de mensajería por autorización expresa del
     * superadministrador (sin valores comerciales inventados).
     */
    public function updateMessaging(Request $request, Business $business)
    {
        $this->assertSuperAdmin($request);

        $data = $request->validate([
            'messaging_included' => ['nullable', 'integer', 'min:0'],
            'messaging_overage_authorized' => ['nullable', 'integer', 'min:0'],
        ]);

        $business->update([
            'messaging_included' => $data['messaging_included'] ?? null,
            'messaging_overage_authorized' => $data['messaging_overage_authorized'] ?? null,
        ]);

        AuditLog::registrar($request->user(), 'mensajeria.cuota.autorizada', $business, $data);

        return $this->respuesta($request, 'Cuota de mensajería actualizada');
    }

    /**
     * Exportación de datos del negocio bajo solicitud del
     * superadministrador (Dec-15), con auditoría y archivo seguro.
     */
    public function exportBusiness(Request $request, Business $business)
    {
        $this->assertSuperAdmin($request);

        $professionalIds = $business->professionals()->pluck('id');

        $datos = [
            'exportado_en' => now()->toIso8601String(),
            'negocio' => $business->toArray(),
            'usuarios' => $business->users()->get()->toArray(),
            'profesionales' => $business->professionals()->get()->toArray(),
            'servicios' => $business->services()->get()->toArray(),
            'horarios' => WorkSchedule::whereIn('professional_id', $professionalIds)->get()->toArray(),
            'excepciones' => ScheduleException::whereIn('professional_id', $professionalIds)->get()->toArray(),
            'festivos' => $business->holidays()->get()->toArray(),
            'clientes' => $business->customers()->get()->toArray(),
            'citas' => $business->appointments()->get()->toArray(),
            'membresias' => $business->memberships()->get()->toArray(),
            'pagos_membresia' => MembershipPayment::where('business_id', $business->id)->get()->toArray(),
            'pagos_servicios' => ServicePayment::where('business_id', $business->id)->get()->toArray(),
            'mensajes_whatsapp' => WhatsAppMessage::where('business_id', $business->id)->get()->toArray(),
        ];

        $archivo = 'export_'.$business->slug.'_'.now()->format('Ymd_His').'_'.Str::random(8).'.json';
        File::ensureDirectoryExists(storage_path('app/exports'));
        File::put(
            storage_path('app/exports/'.$archivo),
            json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        @chmod(storage_path('app/exports/'.$archivo), 0600);

        AuditLog::registrar($request->user(), 'negocio.exportado', $business, ['archivo' => $archivo]);

        return $this->respuesta($request, 'Exportación generada de forma segura', ['archivo' => $archivo], 201);
    }

    public function downloadExport(Request $request, string $archivo)
    {
        $this->assertSuperAdmin($request);

        abort_unless((bool) preg_match('~^export_[a-z0-9\-]+_\d{8}_\d{6}_[a-zA-Z0-9]+\.json$~', $archivo), 404);

        $ruta = storage_path('app/exports/'.$archivo);
        abort_unless(is_file($ruta), 404);

        AuditLog::registrar($request->user(), 'negocio.exportacion.descargada', null, ['archivo' => $archivo]);

        return response()->download($ruta);
    }

    /**
     * Revisión administrativa de retención (90 días) con exclusiones
     * legales; la purga se ejecuta solo con autorización explícita.
     */
    public function retentionReview(Request $request)
    {
        $this->assertSuperAdmin($request);

        $limite = now()->subDays(90)->toDateString();

        $elegibles = Business::whereHas('memberships', function ($query) use ($limite) {
            $query->where('status', 'cancelled')->where('ends_on', '<', $limite);
        })
            ->whereDoesntHave('memberships', fn ($query) => $query->where('status', 'active'))
            ->with('memberships')
            ->get()
            ->map(function (Business $business) {
                return [
                    'negocio' => $business,
                    'fin_periodo' => $business->memberships->max('ends_on'),
                    'clientes' => $business->customers()->count(),
                    'citas' => $business->appointments()->count(),
                    'mensajes' => WhatsAppMessage::where('business_id', $business->id)->count(),
                ];
            });

        return view('admin.retention', ['elegibles' => $elegibles]);
    }

    public function legalHold(Request $request, Business $business)
    {
        $this->assertSuperAdmin($request);

        $data = $request->validate(['legal_hold' => ['required', 'boolean']]);

        $business->update(['legal_hold' => (bool) $data['legal_hold']]);
        AuditLog::registrar($request->user(), 'negocio.retencion.legal', $business, $data);

        return $this->respuesta($request, 'Retención legal actualizada');
    }

    private function assertSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
    }
}
