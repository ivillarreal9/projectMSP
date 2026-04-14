{{-- resources/views/admin/reports/msp/cliente_detalle.blade.php --}}
@extends('admin.reports.msp.layouts.app')
@section('title', $customer)

@section('content')
<div class="space-y-6 fade-in">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.msp.clientes') }}?periodo={{ $periodo }}"
               class="text-gray-400 hover:text-gray-700 transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">{{ $customer }}</h2>
                @if($periodo)
                    <span class="text-sm text-gray-500">Período: <strong>{{ $periodo }}</strong></span>
                @endif
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.msp.pdf.preview', urlencode($customer)) }}?periodo={{ $periodo }}"
               target="_blank"
               class="flex items-center gap-2 px-4 py-2 border rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                <i class="fa-solid fa-eye"></i> Ver PDF
            </a>
            <a href="{{ route('admin.msp.pdf.download', urlencode($customer)) }}?periodo={{ $periodo }}"
               class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white"
               style="background:var(--ovni-orange)">
                <i class="fa-solid fa-download"></i> Descargar PDF
            </a>
        </div>
    </div>

    {{-- ══ PERFIL DEL CLIENTE ══ --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b dark:border-gray-700 flex items-center gap-2">
            <i class="fa-solid fa-user-tie text-gray-400"></i>
            <h3 class="font-semibold text-gray-700 dark:text-gray-200">Información del cliente</h3>
        </div>

        <form action="{{ route('admin.msp.clientes.update', urlencode($customer)) }}"
              method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            @method('POST')
            <input type="hidden" name="periodo" value="{{ $periodo }}">

            <div class="flex flex-col md:flex-row gap-6">

                {{-- Logo del cliente --}}
                <div class="flex flex-col items-center gap-3 flex-shrink-0">
                    <div id="logoPreview"
                        class="w-24 h-24 rounded-2xl border-2 border-dashed border-gray-300 dark:border-gray-600
                                flex items-center justify-center overflow-hidden bg-gray-50 dark:bg-gray-700 cursor-pointer"
                        onclick="document.getElementById('logoInput').click()"
                        style="width:96px; height:96px; min-width:96px;">

                        @if($clienteInfo?->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($clienteInfo->logo_path))
                            <img src="{{ Storage::url($clienteInfo->logo_path) }}" id="logoImg"
                                style="width:100%; height:100%; object-fit:contain; padding:8px;">
                        @else
                            <div id="logoPlaceholder" class="text-center text-gray-400">
                                <i class="fa-solid fa-image text-2xl mb-1"></i>
                                <p class="text-xs">Logo</p>
                            </div>
                        @endif
                    </div>
                    <input type="file" name="logo" id="logoInput" accept="image/*" class="hidden"
                           onchange="previewLogo(event)">
                    <button type="button" onclick="document.getElementById('logoInput').click()"
                            class="text-xs text-blue-600 hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-upload text-xs"></i> Subir logo
                    </button>
                </div>

                {{-- Campos del cliente --}}
                <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">
                            Nombre del cliente
                        </label>
                        <input type="text" name="customer_name" value="{{ $customer }}"
                               class="w-full border dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm
                                      focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">
                            Correo electrónico
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="email" name="email_cliente"
                                   value="{{ $clienteInfo?->email_cliente }}"
                                   placeholder="cliente@empresa.com"
                                   class="w-full border dark:border-gray-600 rounded-xl pl-9 pr-4 py-2.5 text-sm
                                          focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">
                            Número de cuenta / Contrato
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-hashtag absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="text" name="numero_cuenta"
                                   value="{{ $clienteInfo?->numero_cuenta }}"
                                   placeholder="Ej: CTR-2026-001"
                                   class="w-full border dark:border-gray-600 rounded-xl pl-9 pr-4 py-2.5 text-sm
                                          focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Botón guardar --}}
            <div class="flex justify-end mt-5 pt-5 border-t dark:border-gray-700">
                <button type="submit"
                        class="flex items-center gap-2 px-6 py-2.5 rounded-xl text-white text-sm font-semibold
                               hover:opacity-90 active:scale-95 transition"
                        style="background: var(--ovni-orange)">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar información
                </button>
            </div>
        </form>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @php
        $kpis = [
            ['label'=>'Cant. Incidentes',    'value'=> $stats['cant_incidentes'],    'icon'=>'fa-triangle-exclamation','color'=>'text-red-600',  'bg'=>'bg-red-50'],
            ['label'=>'T. Prom. Incidentes', 'value'=> round($stats['tiempo_prom_incidentes'],3), 'icon'=>'fa-clock','color'=>'text-orange-600','bg'=>'bg-orange-50', 'suffix'=>' días'],
            ['label'=>'Cant. Solicitudes',   'value'=> $stats['cant_solicitudes'],   'icon'=>'fa-clipboard-list',      'color'=>'text-blue-600', 'bg'=>'bg-blue-50'],
            ['label'=>'T. Prom. Solicitudes','value'=> round($stats['tiempo_prom_solicitudes'],3),'icon'=>'fa-clock','color'=>'text-teal-600',  'bg'=>'bg-teal-50',   'suffix'=>' días'],
        ];
        @endphp
        @foreach($kpis as $kpi)
        <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-5">
            <div class="flex items-start justify-between mb-3">
                <div class="w-10 h-10 rounded-xl {{ $kpi['bg'] }} flex items-center justify-center">
                    <i class="fa-solid {{ $kpi['icon'] }} {{ $kpi['color'] }}"></i>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-800 dark:text-white">{{ $kpi['value'] }}{{ $kpi['suffix'] ?? '' }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ $kpi['label'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Observación --}}
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
        <h4 class="font-semibold text-amber-800 mb-2"><i class="fa-solid fa-lightbulb mr-2"></i>Observación</h4>
        <ul class="text-sm text-amber-700 space-y-1 list-disc list-inside">
            <li><strong>Unidad de tiempo:</strong> Días.</li>
            <li><strong>Gráfico Recuento de tickets:</strong></li>
            <ul class="ml-4 space-y-1">
                <li>○ <strong>Alarma:</strong> Incidente generado proactivamente posterior al análisis de Monitoreo.</li>
                <li>○ <strong>Reportado:</strong> Incidente generado al momento que el cliente reporta.</li>
            </ul>
        </ul>
    </div>

    {{-- Gráficos --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-5">
            <h4 class="font-semibold text-gray-700 dark:text-gray-200 mb-4">Solicitudes generadas por Ubicación</h4>
            <canvas id="chartSolUbicacion" height="200"></canvas>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-5">
            <h4 class="font-semibold text-gray-700 dark:text-gray-200 mb-4">Incidentes generados por Ubicación</h4>
            <canvas id="chartIncUbicacion" height="200"></canvas>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-5">
            <h4 class="font-semibold text-gray-700 dark:text-gray-200 mb-4">Cant. Incidentes por Clasificación de eventos</h4>
            <div class="flex justify-center">
                <canvas id="chartClasif" style="max-height:220px;max-width:220px"></canvas>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-5">
            <h4 class="font-semibold text-gray-700 dark:text-gray-200 mb-1">Recuento de Incidentes - Alarma vs Reportado</h4>
            <p class="text-xs text-gray-400 mb-4">Alarma vs Reportado por Semana</p>
            <canvas id="chartAlarmaReportado" height="200"></canvas>
        </div>
    </div>

    {{-- Tabla detalle --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="p-5 border-b dark:border-gray-700">
            <h4 class="font-semibold text-gray-700 dark:text-gray-200">Detalle de Tickets</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase tracking-wide text-gray-500 font-semibold">
                    <tr>
                        <th class="text-left px-5 py-3">Ticket</th>
                        <th class="text-left px-5 py-3">Tipo</th>
                        <th class="text-left px-5 py-3">Descripción</th>
                        <th class="text-left px-5 py-3">Causa de Daño</th>
                        <th class="text-left px-5 py-3">Solución</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                    @foreach($stats['detalle_tickets'] as $ticket)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-5 py-3 font-mono font-semibold text-gray-700 dark:text-gray-300">{{ $ticket['ticket'] }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-1 rounded text-xs font-medium {{ $ticket['tipo'] === 'Incidente' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ $ticket['tipo'] }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-400 max-w-xs truncate" title="{{ $ticket['descripcion'] }}">
                            {{ $ticket['descripcion'] }}
                        </td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-400">{{ $ticket['causa'] }}</td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-400">{{ $ticket['solucion'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Preview del logo antes de subir
function previewLogo(event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('logoPreview');
        preview.innerHTML = `<img src="${e.target.result}" 
            style="width:100%;height:100%;object-fit:contain;padding:8px;">`;
    };
    reader.readAsDataURL(file);
}

// Charts
const TEAL   = '#0f8a8a';
const ORANGE = '#d4520a';

const solUbic = @json($stats['por_ubicacion_solicitudes']);
new Chart(document.getElementById('chartSolUbicacion'), {
    type: 'bar',
    data: { labels: Object.keys(solUbic), datasets: [{ data: Object.values(solUbic), backgroundColor: TEAL, borderRadius: 4 }] },
    options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

const incUbic = @json($stats['por_ubicacion_incidentes']);
new Chart(document.getElementById('chartIncUbicacion'), {
    type: 'bar',
    data: { labels: Object.keys(incUbic), datasets: [{ data: Object.values(incUbic), backgroundColor: TEAL, borderRadius: 4 }] },
    options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

const clasif = @json($stats['por_clasificacion']);
new Chart(document.getElementById('chartClasif'), {
    type: 'pie',
    data: { labels: Object.keys(clasif), datasets: [{ data: Object.values(clasif), backgroundColor: [ORANGE, '#d4885a', '#e5a87c', '#b84a0a'] }] },
    options: { plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } }
});

const semanas = @json($stats['alarma_vs_reportado_semana']);
const semanasLabels = Object.keys(semanas).sort();
new Chart(document.getElementById('chartAlarmaReportado'), {
    type: 'bar',
    data: {
        labels: semanasLabels,
        datasets: [
            { label: 'Alarma',    data: semanasLabels.map(s => semanas[s]?.Alarma    || 0), backgroundColor: TEAL,   borderRadius: 3 },
            { label: 'Reportado', data: semanasLabels.map(s => semanas[s]?.Reportado || 0), backgroundColor: ORANGE, borderRadius: 3 },
        ]
    },
    options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }, plugins: { legend: { position: 'top' } } }
});
</script>
@endpush