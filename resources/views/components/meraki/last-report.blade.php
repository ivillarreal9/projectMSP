{{--
    Último reporte de un dispositivo Meraki: tiempo relativo + fecha formateada.
    Se pinta en rojo cuando supera el umbral de horas sin reportar (staleHours).

    Uso: <x-meraki.last-report :at="$device['_status']['lastReportedAt'] ?? null" />
--}}
@props(['at' => null, 'staleHours' => 24])
@php
    try {
        $dt = $at ? \Carbon\Carbon::parse($at) : null;
    } catch (\Exception $e) {
        $dt = null;
    }
    $isStale = $dt && $dt->diffInHours(now()) > $staleHours;
@endphp
@if($dt)
<p class="text-xs font-medium {{ $isStale ? 'text-red-500 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
    {{ $dt->diffForHumans() }}
</p>
<p class="text-[10px] text-gray-400 mt-0.5">{{ $dt->format('d/m/Y H:i') }}</p>
@else
<span class="text-xs text-gray-300 dark:text-gray-600">—</span>
@endif
