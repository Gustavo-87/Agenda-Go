<?php

namespace App\Services;

use App\Http\Controllers\AppointmentController;
use App\Jobs\EnviarNotificacionWhatsApp;
use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\Business;
use App\Support\FormatoHora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReservaWhatsAppService
{
    public function procesar(string $telefono, string $mensaje, ?int $businessId = null): string
    {
        $businessId ??= app()->isProduction() ? null : Business::bookingChannelId();
        $clave = 'reserva_whatsapp_'.$businessId.'_'.hash('sha256', $telefono);
        $estado = Cache::get($clave);
        $mensaje = trim($mensaje);
        $palabra = mb_strtolower($mensaje);
        $paso = is_array($estado) ? (string) ($estado['paso'] ?? '') : '';

        if (in_array($paso, ['cancelar_eleccion', 'cancelar_confirmar'], true)) {
            return $this->gestionarCancelacion($mensaje, $palabra, $businessId, $estado, $clave);
        }

        $enReserva = in_array($paso, ['profesional', 'servicio', 'fecha', 'hora', 'nombre', 'confirmar'], true);

        // Cancelación: opción numérica del menú o expresiones naturales.
        if ($this->quiereCancelar($palabra, $paso === 'menu')) {
            return $this->iniciarCancelacion($telefono, $businessId, $clave);
        }

        // Volver al menú principal en cualquier momento.
        if (in_array($palabra, ['menu', 'menú', '0', 'volver', 'atras', 'atrás', 'hola', 'reiniciar'], true)) {
            return $this->menuPrincipal($businessId, $clave);
        }

        if (! $enReserva) {
            $respuestaCita = $this->gestionarRespuestaCita($telefono, $mensaje, $businessId);

            if ($respuestaCita !== null) {
                return $respuestaCita;
            }

            if ($this->quiereAgendar($palabra)) {
                $estado = ['paso' => 'profesional', 'business_id' => $businessId];
                Cache::put($clave, $estado, now()->addHours(2));

                return $this->listaProfesionales($businessId);
            }

            return $this->menuPrincipal($businessId, $clave);
        }

        switch ($estado['paso']) {
            case 'profesional':
                $items = DB::table('professionals')->where('active', true)
                    ->where('business_id', $businessId)
                    ->orderBy('id')->get();
                $seleccion = $this->elegir($items, $mensaje);
                if (! $seleccion) {
                    return $this->listaProfesionales($businessId);
                }

                $estado['profesional_id'] = $seleccion->id;
                $estado['paso'] = 'servicio';
                $respuesta = $this->menuServicios($seleccion->id);
                break;

            case 'servicio':
                $items = DB::table('services')
                    ->where('professional_id', $estado['profesional_id'])
                    ->where('active', true)->orderBy('id')->get();

                $seleccion = $this->elegir($items, $mensaje);
                if (! $seleccion) {
                    return $this->menuServicios($estado['profesional_id']);
                }

                $estado['servicio_id'] = $seleccion->id;
                $estado['paso'] = 'fecha';
                $respuesta = 'Escribe la fecha de tu cita (AAAA-MM-DD). Ejemplo: 2026-11-16';
                break;

            case 'fecha':
                $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', $mensaje);
                if (! $fecha || $fecha->format('Y-m-d') !== $mensaje
                    || $mensaje < now('America/Bogota')->toDateString()) {
                    return 'Fecha inválida o pasada. Escribe una fecha en formato AAAA-MM-DD.';
                }

                $horas = $this->horarios($estado, $mensaje);
                if (! $horas) {
                    return 'No hay horarios disponibles ese día. Elige otra fecha.';
                }

                $estado['fecha'] = $mensaje;
                $estado['horas'] = $horas;
                $estado['paso'] = 'hora';
                $respuesta = "Horarios disponibles:\n"
                    .$this->numerar(array_map(fn ($h) => FormatoHora::hora($h), $horas))
                    ."\nResponde con el número del horario.";
                break;

            case 'hora':
                $indice = ctype_digit($mensaje) ? (int) $mensaje - 1 : -1;
                if (! isset($estado['horas'][$indice])) {
                    return "Selecciona un horario válido:\n".$this->numerar(array_map(fn ($h) => FormatoHora::hora($h), $estado['horas']));
                }

                $estado['hora'] = $estado['horas'][$indice];
                $estado['paso'] = 'nombre';
                $respuesta = '¿Cuál es tu nombre completo?';
                break;

            case 'nombre':
                if (mb_strlen($mensaje) < 2 || mb_strlen($mensaje) > 120) {
                    return 'Escribe un nombre válido (entre 2 y 120 caracteres).';
                }

                $estado['nombre'] = $mensaje;
                $estado['paso'] = 'confirmar';
                $respuesta = "Confirma tu cita:\n"
                    ."Fecha: {$estado['fecha']}\n"
                    .'Hora: '.FormatoHora::hora($estado['hora'])."\n"
                    ."Nombre: {$estado['nombre']}\n\n"
                    ."1. Confirmar\n2. Cancelar";
                break;

            case 'confirmar':
                if ($mensaje === '2') {
                    Cache::forget($clave);

                    return 'Reserva cancelada. Escribe HOLA para comenzar nuevamente.';
                }
                if ($mensaje !== '1') {
                    return 'Responde 1 para confirmar o 2 para cancelar.';
                }

                try {
                    $respuestaApi = app(AppointmentController::class)->store(
                        Request::create('/api/appointments', 'POST', [
                            'business_id' => $businessId,
                            'professional_id' => $estado['profesional_id'],
                            'service_id' => $estado['servicio_id'],
                            'customer_name' => $estado['nombre'],
                            'customer_phone' => $telefono,
                            'starts_at' => $estado['fecha'].' '.$estado['hora'].':00',
                        ])
                    );

                    $id = $respuestaApi->getData(true)['appointment_id'];
                    Cache::forget($clave);

                    return "¡Reserva confirmada! Tu número de cita es {$id}. "
                        .'Fecha: '.$estado['fecha'].', hora: '.FormatoHora::hora($estado['hora']).'.';
                } catch (ValidationException $e) {
                    $estado['paso'] = 'fecha';
                    Cache::put($clave, $estado, now()->addHours(2));

                    return 'Ese horario ya no está disponible. Escribe otra fecha (AAAA-MM-DD).';
                } catch (Throwable $e) {
                    report($e);

                    return 'No pudimos confirmar la cita. Responde 1 para reintentar.';
                }

            default:
                Cache::forget($clave);

                return 'Escribe HOLA para comenzar.';
        }

        Cache::put($clave, $estado, now()->addHours(2));

        return $respuesta;
    }

    /**
     * Confirmación de asistencia o cancelación por respuesta del
     * cliente, respetando las políticas del negocio (solicitudes
     * vigentes o citas confirmadas, antes del inicio).
     */
    private function gestionarRespuestaCita(string $telefono, string $mensaje, ?int $businessId): ?string
    {
        $palabra = mb_strtolower(trim($mensaje));

        $confirmar = in_array($palabra, ['confirmar', 'confirmo', 'si', 'sí', 'asistire', 'asistiré'], true);

        if (! $confirmar) {
            return null;
        }

        $cita = Appointment::where('customer_phone', $telefono)
            ->where('business_id', $businessId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('starts_at', '>', now('America/Bogota'))
            ->orderBy('starts_at')
            ->first();

        if ($cita === null) {
            return 'No encontramos citas próximas para confirmar o cancelar.';
        }

        if ($confirmar) {
            if ($cita->status !== 'pending') {
                return 'Tu cita del '.FormatoHora::fechaHora($cita->starts_at).' ya está confirmada. ¡Te esperamos!';
            }

            $cita->update(['status' => 'confirmed', 'pending_expires_at' => null]);

            return '¡Cita confirmada! Te esperamos el '.FormatoHora::fechaHora($cita->starts_at).'.';
        }

        $cita->update(['status' => 'cancelled', 'pending_expires_at' => null]);

        return 'Tu cita del '.FormatoHora::fechaHora($cita->starts_at).' fue cancelada.';
    }

    /**
     * Menú principal dinámico del negocio asociado al número.
     */
    private function menuPrincipal(?int $businessId, string $clave): string
    {
        Cache::put($clave, ['paso' => 'menu', 'business_id' => $businessId], now()->addHours(2));

        $negocio = Business::find($businessId);
        $saludo = $negocio !== null ? '¡Bienvenida a '.$negocio->name.'!' : '¡Bienvenida!';

        return $saludo."\n1. Agendar cita\n2. Cancelar cita";
    }

    /**
     * Detección de intención de agendamiento.
     */
    private function quiereAgendar(string $palabra): bool
    {
        return $palabra === '1'
            || str_contains($palabra, 'agendar')
            || str_contains($palabra, 'reserv');
    }

    /**
     * Detección de intención de cancelación: opción del menú o
     * expresiones naturales (p. ej. «quiero cancelar mi cita»).
     */
    private function quiereCancelar(string $palabra, bool $enMenu = false): bool
    {
        return str_contains($palabra, 'cancel') || ($enMenu && $palabra === '2');
    }

    /**
     * Paso 1: identifica al cliente por teléfono y muestra sus próximas
     * citas para que seleccione cuál cancelar.
     */
    private function iniciarCancelacion(string $telefono, ?int $businessId, string $clave): string
    {
        $citas = Appointment::where('customer_phone', $telefono)
            ->where('business_id', $businessId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('starts_at', '>', now('America/Bogota'))
            ->orderBy('starts_at')
            ->get();

        if ($citas->isEmpty()) {
            return 'No encontramos citas próximas para cancelar.';
        }

        Cache::put($clave, [
            'paso' => 'cancelar_eleccion',
            'citas' => $citas->pluck('id')->all(),
        ], now()->addHours(2));

        $lineas = $citas->map(
            fn ($cita) => FormatoHora::fechaHora($cita->starts_at).' — '.($cita->service?->name ?? 'Servicio')
        )->values();

        return "Selecciona la cita que deseas cancelar:\n".$this->numerar($lineas)
            ."\n0. Volver al menú principal";
    }

    /**
     * Pasos 2 y 3: selección de la cita y confirmación expresa; el
     * estado se actualiza y la disponibilidad se libera únicamente
     * tras confirmar (políticas de cancelación del negocio: solicitudes
     * vigentes o citas confirmadas, antes del inicio).
     */
    private function gestionarCancelacion(string $mensaje, string $palabra, ?int $businessId, array $estado, string $clave): string
    {
        if ($mensaje === '0' || in_array($palabra, ['menu', 'menú', 'volver', 'atras', 'atrás'], true)) {
            return $this->menuPrincipal($businessId, $clave);
        }

        if (($estado['paso'] ?? '') === 'cancelar_eleccion') {
            $indice = ctype_digit($mensaje) ? (int) $mensaje - 1 : -1;
            $ids = $estado['citas'] ?? [];

            if (! isset($ids[$indice])) {
                return 'Selecciona un número válido de la lista o 0 para volver al menú principal.';
            }

            Cache::put($clave, [
                'paso' => 'cancelar_confirmar',
                'cita_id' => $ids[$indice],
            ], now()->addHours(2));

            $cita = Appointment::find($ids[$indice]);

            return 'Cita seleccionada: '.FormatoHora::fechaHora($cita->starts_at)
                .' — '.($cita->service?->name ?? 'Servicio')
                ."\n1. Confirmar cancelación\n2. Volver al menú principal";
        }

        if ($mensaje === '2') {
            return $this->menuPrincipal($businessId, $clave);
        }

        if ($mensaje !== '1') {
            return "1. Confirmar cancelación\n2. Volver al menú principal";
        }

        $cita = Appointment::find($estado['cita_id'] ?? 0);
        Cache::forget($clave);

        if ($cita === null || ! in_array($cita->status, ['pending', 'confirmed'], true)) {
            return 'La cita ya no está disponible para cancelar.';
        }

        $cita->update(['status' => 'cancelled', 'pending_expires_at' => null]);

        $resumen = FormatoHora::fechaHora($cita->starts_at).' — '.($cita->service?->name ?? 'Servicio');

        AppNotification::registrar(
            $cita->business,
            null,
            'cita',
            'Cita cancelada por el cliente: '.$resumen.'.'
        );

        $profesional = $cita->professional;

        if ($profesional?->phone !== null) {
            EnviarNotificacionWhatsApp::dispatch(
                $profesional->phone,
                'Cita cancelada por el cliente: '.$resumen.'.',
                $cita->business_id
            );
        }

        return 'Tu cita del '.FormatoHora::fechaHora($cita->starts_at).' fue cancelada.';
    }

    private function elegir($items, string $mensaje)
    {
        $indice = ctype_digit($mensaje) ? (int) $mensaje - 1 : -1;

        return $items->values()->get($indice);
    }

    private function numerar($items): string
    {
        return collect($items)->values()->map(
            fn ($item, $i) => ($i + 1).'. '.$item
        )->implode("\n");
    }

    private function listaProfesionales(?int $businessId): string
    {
        $items = DB::table('professionals')->where('active', true)
            ->where('business_id', $businessId)
            ->orderBy('id')->pluck('name');

        $negocio = Business::find($businessId);
        $saludo = $negocio !== null ? '¡Bienvenida a '.$negocio->name.'!' : '¡Bienvenida!';

        return $items->isEmpty()
            ? 'No hay profesionales disponibles.'
            : "Elige una profesional:\n".$this->numerar($items)."\n0. Volver al menú principal";
    }

    private function menuServicios(int $profesionalId): string
    {
        $items = DB::table('services')->where('professional_id', $profesionalId)
            ->where('active', true)->orderBy('id')->get();

        if ($items->isEmpty()) {
            return 'Esta profesional no tiene servicios disponibles. Escribe MENÚ.';
        }

        return "Elige un servicio:\n".$this->numerar(
            $items->map(fn ($s) => "{$s->name} - $".number_format($s->price, 0, ',', '.'))
        );
    }

    private function horarios(array $estado, string $fecha): array
    {
        $respuesta = app(AppointmentController::class)->availability(
            Request::create('/api/availability', 'GET', [
                'business_id' => $estado['business_id'] ?? null,
                'professional_id' => $estado['profesional_id'],
                'service_id' => $estado['servicio_id'],
                'date' => $fecha,
            ])
        );

        return $respuesta->getData(true)['available'] ?? [];
    }
}
