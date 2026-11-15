<?php

namespace App\Services;

use App\Http\Controllers\AppointmentController;
use App\Models\Appointment;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReservaWhatsAppService
{
    public function procesar(string $telefono, string $mensaje, ?int $businessId = null): string
    {
        $businessId ??= Business::bookingChannelId();
        $clave = 'reserva_whatsapp_'.$businessId.'_'.hash('sha256', $telefono);
        $estado = Cache::get($clave);
        $mensaje = trim($mensaje);

        if (! $estado) {
            $respuestaCita = $this->gestionarRespuestaCita($telefono, $mensaje, $businessId);

            if ($respuestaCita !== null) {
                return $respuestaCita;
            }
        }

        if (! $estado || in_array(mb_strtolower($mensaje), ['hola', 'menu', 'menú', 'reiniciar', 'cancelar'])) {
            $estado = ['paso' => 'profesional'];
            Cache::put($clave, $estado, now()->addHours(2));

            return $this->menuProfesionales($businessId);
        }

        switch ($estado['paso']) {
            case 'profesional':
                $items = DB::table('professionals')->where('active', true)
                    ->where('business_id', $businessId)
                    ->orderBy('id')->get();
                $seleccion = $this->elegir($items, $mensaje);
                if (! $seleccion) {
                    return $this->menuProfesionales($businessId);
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
                    .$this->numerar($horas)
                    ."\nResponde con el número del horario.";
                break;

            case 'hora':
                $indice = ctype_digit($mensaje) ? (int) $mensaje - 1 : -1;
                if (! isset($estado['horas'][$indice])) {
                    return "Selecciona un horario válido:\n".$this->numerar($estado['horas']);
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
                    ."Hora: {$estado['hora']}\n"
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
                        ."Fecha: {$estado['fecha']}, hora: {$estado['hora']}.";
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
        $cancelar = in_array($palabra, ['cancelar', 'cancelo', 'no'], true);

        if (! $confirmar && ! $cancelar) {
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
                return 'Tu cita del '.$cita->starts_at.' ya está confirmada. ¡Te esperamos!';
            }

            $cita->update(['status' => 'confirmed', 'pending_expires_at' => null]);

            return '¡Cita confirmada! Te esperamos el '.$cita->starts_at.'.';
        }

        $cita->update(['status' => 'cancelled', 'pending_expires_at' => null]);

        return 'Tu cita del '.$cita->starts_at.' fue cancelada.';
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

    private function menuProfesionales(?int $businessId): string
    {
        $items = DB::table('professionals')->where('active', true)
            ->where('business_id', $businessId)
            ->orderBy('id')->pluck('name');

        return $items->isEmpty()
            ? 'No hay profesionales disponibles.'
            : "¡Bienvenida a Viglam!\nElige una profesional:\n".$this->numerar($items);
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
                'professional_id' => $estado['profesional_id'],
                'service_id' => $estado['servicio_id'],
                'date' => $fecha,
            ])
        );

        return $respuesta->getData(true)['available'] ?? [];
    }
}
