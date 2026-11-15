<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Professional;
use App\Models\Service;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * Cálculo centralizado de disponibilidad: jornadas, descansos,
 * vacaciones, bloqueos, festivos, excepciones, anticipación y
 * ocupación por citas confirmadas o solicitudes vigentes.
 */
class DisponibilidadService
{
    private const INTERVALO_MINUTOS = 30;

    private const VENTANA_POR_DEFECTO = [['09:00', '18:00']];

    /**
     * Horarios disponibles (H:i) de un servicio en una fecha.
     *
     * @return array<int, string>
     */
    public function slots(Professional $professional, Service $service, string $date): array
    {
        $business = $professional->business;
        $dia = CarbonImmutable::parse($date, 'America/Bogota')->startOfDay();
        $ahora = now('America/Bogota');

        if ($business === null
            || $dia->lessThan($ahora->copy()->startOfDay())
            || $dia->greaterThan($ahora->copy()->addDays((int) $business->booking_max_advance_days))) {
            return [];
        }

        $anticipacionMinima = $ahora->copy()->addMinutes((int) $business->booking_min_notice_minutes);
        $slots = [];

        foreach ($this->ventanas($professional, $business, $dia) as [$inicio, $fin]) {
            $cursor = Carbon::parse("{$date} {$inicio}:00", 'America/Bogota');
            $limite = Carbon::parse("{$date} {$fin}:00", 'America/Bogota');

            while ($cursor->copy()->addMinutes((int) $service->duration_minutes)->lessThanOrEqualTo($limite)) {
                if ($cursor->greaterThanOrEqualTo($anticipacionMinima)
                    && ! $this->estaOcupado(
                        $professional,
                        $cursor,
                        $cursor->copy()->addMinutes((int) $service->duration_minutes)
                    )) {
                    $slots[] = $cursor->format('H:i');
                }

                $cursor->addMinutes(self::INTERVALO_MINUTOS);
            }
        }

        sort($slots);

        return array_values(array_unique($slots));
    }

    /**
     * Whether the time range overlaps an active appointment or a
     * pending request that has not expired yet (RN-022).
     */
    public function estaOcupado(Professional $professional, Carbon $inicio, Carbon $fin, ?int $exceptId = null): bool
    {
        return Appointment::where('professional_id', $professional->id)
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->whereIn('status', [
                AppointmentStatus::Confirmed->value,
                AppointmentStatus::Pending->value,
            ])
            ->where('starts_at', '<', $fin->format('Y-m-d H:i:s'))
            ->where('ends_at', '>', $inicio->format('Y-m-d H:i:s'))
            ->where(function ($query) {
                $query->where('status', AppointmentStatus::Confirmed->value)
                    ->orWhere('pending_expires_at', '>', now('America/Bogota'));
            })
            ->lockForUpdate()
            ->exists();
    }

    /**
     * Ventanas de trabajo (H:i) del día: jornadas divididas menos
     * descansos, más horarios extra. Vacaciones, bloqueos y festivos
     * cierran el día. Sin jornadas configuradas se conserva la
     * ventana del MVP (09:00–18:00).
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function ventanas(Professional $professional, Business $business, CarbonImmutable $dia): array
    {
        $fecha = $dia->toDateString();

        $cierra = $professional->scheduleExceptions()
            ->where('starts_on', '<=', $fecha)
            ->where(function ($query) use ($fecha) {
                $query->whereNull('ends_on')->where('starts_on', $fecha)
                    ->orWhere('ends_on', '>=', $fecha);
            })
            ->whereIn('kind', ['vacation', 'block'])
            ->exists();

        $festivo = $business->holidays()->where('date', $fecha)->exists();

        $extra = $professional->scheduleExceptions()
            ->where('kind', 'extra')
            ->where('starts_on', '<=', $fecha)
            ->where(function ($query) use ($fecha) {
                $query->whereNull('ends_on')->where('starts_on', $fecha)
                    ->orWhere('ends_on', '>=', $fecha);
            })
            ->get()
            ->map(fn ($ex) => [substr($ex->start_time, 0, 5), substr($ex->end_time, 0, 5)])
            ->all();

        if ($cierra || $festivo) {
            return $extra;
        }

        $jornadas = $professional->workSchedules()
            ->where('weekday', $dia->dayOfWeekIso - 1)
            ->get();

        if ($jornadas->isEmpty()) {
            return array_merge(self::VENTANA_POR_DEFECTO, $extra);
        }

        $trabajo = $jornadas->where('is_break', false)
            ->map(fn ($row) => [substr($row->start_time, 0, 5), substr($row->end_time, 0, 5)])
            ->all();

        $descansos = $jornadas->where('is_break', true)
            ->map(fn ($row) => [substr($row->start_time, 0, 5), substr($row->end_time, 0, 5)])
            ->all();

        foreach ($descansos as $descanso) {
            $trabajo = $this->restarIntervalo($trabajo, $descanso);
        }

        return array_merge($trabajo, $extra);
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $ventanas
     * @param  array{0: string, 1: string}  $descanso
     * @return array<int, array{0: string, 1: string}>
     */
    private function restarIntervalo(array $ventanas, array $descanso): array
    {
        $resultado = [];

        foreach ($ventanas as [$inicio, $fin]) {
            if ($descanso[1] <= $inicio || $descanso[0] >= $fin) {
                $resultado[] = [$inicio, $fin];

                continue;
            }

            if ($descanso[0] > $inicio) {
                $resultado[] = [$inicio, $descanso[0]];
            }

            if ($descanso[1] < $fin) {
                $resultado[] = [$descanso[1], $fin];
            }
        }

        return $resultado;
    }
}
