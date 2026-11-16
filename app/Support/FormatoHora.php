<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Formato de presentación de horas en 12 horas con a. m./p. m.
 * El almacenamiento y los cálculos internos permanecen en 24 horas.
 */
final class FormatoHora
{
    public static function hora(?string $hora): string
    {
        if ($hora === null || $hora === '') {
            return '—';
        }

        $momento = Carbon::parse($hora);
        $sufijo = $momento->hour < 12 ? 'a. m.' : 'p. m.';

        return $momento->format('g:i').' '.$sufijo;
    }

    public static function fechaHora(?string $momento): string
    {
        if ($momento === null || $momento === '') {
            return '—';
        }

        return Carbon::parse($momento)->format('d/m/Y').', '.self::hora($momento);
    }
}
