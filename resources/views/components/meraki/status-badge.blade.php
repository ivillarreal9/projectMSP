{{--
    Badge de estado de un dispositivo Meraki (punto de color + etiqueta).

    Uso: <x-meraki.status-badge :status="$device['_status']['status'] ?? null" />
--}}
@props(['status' => null])
@php
    $map = [
        'online'   => ['dot' => 'bg-green-400',  'text' => 'text-green-600 dark:text-green-400',   'label' => 'Online'],
        'offline'  => ['dot' => 'bg-red-400',    'text' => 'text-red-600 dark:text-red-400',       'label' => 'Offline'],
        'alerting' => ['dot' => 'bg-yellow-400', 'text' => 'text-yellow-600 dark:text-yellow-400', 'label' => 'Alerting'],
        'dormant'  => ['dot' => 'bg-gray-400',   'text' => 'text-gray-500',                        'label' => 'Dormant'],
    ];
    $c = $map[$status] ?? ['dot' => 'bg-gray-300', 'text' => 'text-gray-400', 'label' => 'N/A'];
@endphp
<div {{ $attributes->merge(['class' => 'flex items-center gap-1.5']) }}>
    <span class="w-2 h-2 rounded-full {{ $c['dot'] }} shrink-0 {{ $status === 'alerting' ? 'animate-pulse' : '' }}"></span>
    <span class="text-xs font-medium {{ $c['text'] }}">{{ $c['label'] }}</span>
</div>
