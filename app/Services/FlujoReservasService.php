<?php

namespace App\Services;

use App\Http\Controllers\AppointmentController;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class FlujoReservasService
{
    public function procesar(array $solicitud): array
    {
        if (($solicitud['action'] ?? '') === 'ping') {
            return ['data' => ['status' => 'active']];
        }

        $datos = $solicitud['data'] ?? [];
        $accion = $datos['accion'] ?? '';

        if (in_array($solicitud['action'] ?? '', ['INIT', 'BACK'])) {
            return $this->agenda();
        }

        $profesional = (int) ($datos['profesional'] ?? 0);
        $servicio = (int) ($datos['servicio'] ?? 0);
        $fecha = (string) ($datos['fecha'] ?? '');
        $hora = (string) ($datos['hora'] ?? '');

        if (in_array($accion, ['profesional', 'servicio', 'fecha'])) {
            return $this->agenda($profesional, $servicio, $fecha);
        }

        if (in_array($accion, ['revisar', 'confirmar'])) {
            $nombre = trim((string) ($datos['nombre'] ?? ''));
            $telefono = trim((string) ($datos['telefono'] ?? ''));

            $prof = DB::table('professionals')
                ->where('id', $profesional)->where('active', true)->first();

            $serv = DB::table('services')
                ->where('id', $servicio)
                ->where('professional_id', $profesional)
                ->where('active', true)->first();

            if (!$prof || !$serv || mb_strlen($nombre) < 2 ||
                mb_strlen($nombre) > 120 ||
                !preg_match('/^[0-9+ ()-]{7,30}$/', $telefono) ||
                !in_array($hora, $this->horarios($profesional, $servicio, $fecha), true)) {
                return $this->agenda($profesional, $servicio, $fecha,
                    'Datos inválidos o horario ocupado. Selecciona nuevamente.');
            }

            $resumen = "{$prof->name} - {$serv->name}\n"
                . "{$fecha} a las {$hora}\n"
                . "Cliente: {$nombre}";

            $campos = [
                'resumen' => $resumen,
                'profesional' => (string) $profesional,
                'servicio' => (string) $servicio,
                'fecha' => $fecha,
                'hora' => $hora,
                'nombre' => $nombre,
                'telefono' => $telefono,
            ];

            if ($accion === 'revisar') {
                return $this->respuesta('SUMMARY', $campos);
            }

            try {
                $respuesta = app(AppointmentController::class)->store(
                    Request::create('/api/appointments', 'POST', [
                        'professional_id' => $profesional,
                        'service_id' => $servicio,
                        'customer_name' => $nombre,
                        'customer_phone' => $telefono,
                        'starts_at' => "{$fecha} {$hora}:00",
                    ])
                );

                $id = $respuesta->getData(true)['appointment_id'];

                return $this->respuesta('SUCCESS', [
                    'mensaje' => "Reserva #{$id} confirmada para {$fecha} a las {$hora}.",
                ]);
            } catch (ValidationException $e) {
                return $this->respuesta('SUMMARY', [
                    ...$campos,
                    'resumen' => 'El horario ya no está disponible. Regresa y selecciona otro.',
                ]);
            } catch (Throwable $e) {
                report($e);
                return $this->respuesta('SUMMARY', [
                    ...$campos,
                    'resumen' => 'No se pudo confirmar. Intenta nuevamente.',
                ]);
            }
        }

        return $this->agenda();
    }

    private function respuesta(string $pantalla, array $datos): array
    {
        return [
            'version' => '3.0',
            'screen' => $pantalla,
            'data' => $datos,
        ];
    }

    private function horarios(int $profesional, int $servicio, string $fecha): array
    {
        try {
            $respuesta = app(AppointmentController::class)->availability(
                Request::create('/api/availability', 'GET', [
                    'professional_id' => $profesional,
                    'service_id' => $servicio,
                    'date' => $fecha,
                ])
            );

            return $respuesta->getData(true)['available'] ?? [];
        } catch (Throwable $e) {
            report($e);
            return [];
        }
    }

    private function agenda(
        int $profesional = 0,
        int $servicio = 0,
        string $fecha = '',
        string $aviso = ''
    ): array {
        $profesionales = DB::table('professionals')
            ->where('active', true)->orderBy('id')->get();

        $profesionalValido = $profesionales->contains('id', $profesional);

        $servicios = $profesionalValido
            ? DB::table('services')->where('professional_id', $profesional)
                ->where('active', true)->orderBy('id')->get()
            : collect();

        $servicioValido = $servicios->contains('id', $servicio);
        $inicio = CarbonImmutable::today('America/Bogota');
        $fin = $inicio->addDays(14);

        $bloqueadas = [];
        $horarios = [];

        if ($servicioValido) {
            for ($dia = 0; $dia <= 14; $dia++) {
                $fechaDia = $inicio->addDays($dia)->format('Y-m-d');

                if (!$this->horarios($profesional, $servicio, $fechaDia)) {
                    $bloqueadas[] = $fechaDia;
                }
            }

            if ($fecha && !in_array($fecha, $bloqueadas, true)
                && $fecha >= $inicio->format('Y-m-d')
                && $fecha <= $fin->format('Y-m-d')) {
                $horarios = $this->horarios($profesional, $servicio, $fecha);
            }
        }

        return $this->respuesta('APPOINTMENT', [
            'profesionales' => $profesionales->map(fn ($p) => [
                'id' => (string) $p->id, 'title' => $p->name,
            ])->values()->all(),
            'servicios' => $servicios->map(fn ($s) => [
                'id' => (string) $s->id,
                'title' => $s->name,
            ])->values()->all(),
            'habilitar_servicios' => $profesionalValido,
            'habilitar_fecha' => $servicioValido,
            'fecha_minima' => $inicio->format('Y-m-d'),
            'fecha_maxima' => $fin->format('Y-m-d'),
            'fechas_no_disponibles' => $bloqueadas,
            'horarios' => array_map(
                fn ($h) => ['id' => $h, 'title' => $h], $horarios
            ),
            'habilitar_horarios' => count($horarios) > 0,
            'aviso' => $aviso ?: 'Selecciona una profesional y un servicio.',
        ]);
    }
}
