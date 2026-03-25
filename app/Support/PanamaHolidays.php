<?php

namespace App\Support;

use Carbon\Carbon;

class PanamaHolidays
{
    /**
     * Feriados fijos de Panamá (mes-día)
     */
    protected static array $fixed = [
        '01-01', // Año Nuevo
        '01-09', // Día de los Mártires
        '05-01', // Día del Trabajo
        '11-03', // Separación de Panamá de Colombia
        '11-04', // Día de la Bandera
        '11-05', // Colón
        '11-10', // Primer Grito de Independencia
        '11-28', // Independencia de España
        '12-08', // Día de la Madre
        '12-25', // Navidad
    ];

    public static function isHoliday(Carbon $date): bool
    {
        return in_array($date->format('m-d'), self::$fixed);
    }

    public static function isWorkingDay(Carbon $date): bool
    {
        // Domingo o feriado = no hábil
        // Sábado SÍ cuenta como hábil
        if ($date->isSunday()) return false;
        if (self::isHoliday($date)) return false;
        return true;
    }

    /**
     * Calcular horas hábiles entre dos fechas
     */
    public static function workingHoursBetween(Carbon $start, Carbon $end): float
    {
        if ($end->lessThanOrEqualTo($start)) return 0;

        $hours   = 0.0;
        $current = $start->copy();

        while ($current->lessThan($end)) {
            if (self::isWorkingDay($current)) {
                $nextHour = $current->copy()->addHour();
                $segment  = $nextHour->lessThan($end)
                    ? 1
                    : $end->diffInMinutes($current) / 60;
                $hours += $segment;
            }
            $current->addHour();
        }

        return $hours;
    }
}