<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
@php
    $capLabel = function ($mb) {
        if (!$mb) return '—';
        return $mb >= 1024 ? rtrim(rtrim(number_format($mb / 1024, 2), '0'), '.') . ' GB' : number_format($mb) . ' MB';
    };
    $capTotal = $stats['capacidad'] ?? 0;
    $estadoColors = [
        'activo'        => ['#16a34a', '#dcfce7'],
        'incidente'     => ['#dc2626', '#fee2e2'],
        'mantenimiento' => ['#ca8a04', '#fef9c3'],
    ];
@endphp
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        color: #1f2937;
        font-size: 11px;
        line-height: 1.4;
    }

    /* ── Encabezado ── */
    .header {
        display: flex; align-items: center; justify-content: space-between;
        border-bottom: 3px solid #f97316;
        padding-bottom: 12px; margin-bottom: 16px;
    }
    .header .brand { display: flex; align-items: center; gap: 12px; }
    .header img { height: 42px; }
    .header h1 { font-size: 20px; font-weight: 800; color: #111827; }
    .header .sub { font-size: 10px; color: #6b7280; margin-top: 2px; }
    .header .meta { text-align: right; font-size: 10px; color: #6b7280; }
    .header .meta .ref {
        display: inline-block; font-family: monospace; font-weight: 700;
        color: #1d4ed8; background: #eff6ff; border: 1px solid #dbeafe;
        padding: 2px 8px; border-radius: 4px; margin-bottom: 4px;
    }

    /* ── Resumen ── */
    .summary {
        display: flex; flex-wrap: wrap; gap: 8px;
        background: #111827; border-radius: 8px;
        padding: 12px 16px; margin-bottom: 18px; color: #fff;
    }
    .summary .box { text-align: center; padding: 0 12px; border-right: 1px solid #374151; }
    .summary .box:last-child { border-right: none; }
    .summary .num { font-size: 18px; font-weight: 800; }
    .summary .lbl { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; }
    .summary .dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 4px; }

    /* ── País ── */
    .pais-title {
        font-size: 13px; font-weight: 800; color: #f97316;
        text-transform: uppercase; letter-spacing: 1px;
        margin: 14px 0 8px; padding-bottom: 4px; border-bottom: 1px solid #fed7aa;
    }

    /* ── Tarjetas ── */
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .card {
        border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px;
        page-break-inside: avoid; background: #fff;
    }
    .card .top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px; }
    .card .cliente { font-size: 12px; font-weight: 700; color: #111827; }
    .badge { font-size: 8px; font-weight: 700; text-transform: uppercase; padding: 2px 7px; border-radius: 10px; white-space: nowrap; }
    .tags { margin-bottom: 8px; }
    .tag { font-size: 8px; font-weight: 600; padding: 2px 6px; border-radius: 4px; margin-right: 4px; }
    .tag.pais { background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }
    .tag.carrier { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }

    .fields { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 12px; margin-bottom: 6px; }
    .field .k { font-size: 7.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; }
    .field .v { font-size: 10px; color: #1f2937; font-family: monospace; word-break: break-word; }
    .field.full { grid-column: 1 / -1; }
    .field.full .v { font-family: 'Segoe UI', Arial, sans-serif; }

    .contacto { border-top: 1px solid #f3f4f6; padding-top: 6px; margin-top: 2px; font-size: 9.5px; }
    .contacto .nombre { font-weight: 700; color: #374151; }
    .contacto .det { color: #6b7280; }
    .notas { border-top: 1px solid #f3f4f6; padding-top: 6px; margin-top: 6px; font-size: 9px; color: #6b7280; }
    .notas .k { font-size: 7.5px; font-weight: 700; text-transform: uppercase; color: #9ca3af; }

    .foot { margin-top: 18px; text-align: center; font-size: 8px; color: #9ca3af; }
</style>
</head>
<body>

    {{-- ── Encabezado ── --}}
    <div class="header">
        <div class="brand">
            @if($logo)<img src="{{ $logo }}" alt="Ovnicom">@endif
            <div>
                <h1>Control de Enlaces Carrier</h1>
                <div class="sub">Circuitos de red por país y carrier</div>
            </div>
        </div>
        <div class="meta">
            @if($lastBatch?->referencia_tecnica)<div class="ref">{{ $lastBatch->referencia_tecnica }}</div><br>@endif
            Generado: {{ now()->isoFormat('D [de] MMMM [de] YYYY, HH:mm') }}
        </div>
    </div>

    {{-- ── Resumen ── --}}
    <div class="summary">
        <div class="box">
            <div class="num">{{ number_format($stats['total']) }}</div>
            <div class="lbl">Total</div>
        </div>
        @foreach($stats['paises'] as $pais => $count)
        <div class="box">
            <div class="num">{{ $count }}</div>
            <div class="lbl">{{ $pais }}</div>
        </div>
        @endforeach
        <div class="box" style="text-align:left">
            <div style="font-size:10px;margin-bottom:2px"><span class="dot" style="background:#16a34a"></span>{{ $stats['activos'] }} activos</div>
            <div style="font-size:10px;margin-bottom:2px"><span class="dot" style="background:#dc2626"></span>{{ $stats['incidentes'] }} incidentes</div>
            <div style="font-size:10px"><span class="dot" style="background:#ca8a04"></span>{{ $stats['mantenimiento'] }} mantenimiento</div>
        </div>
        <div class="box">
            <div class="num">{{ $capLabel($capTotal) }}</div>
            <div class="lbl">Capacidad total</div>
        </div>
    </div>

    {{-- ── Secciones por país ── --}}
    @foreach($grouped as $pais => $circuitos)
    <div class="pais-title">{{ $pais }} &middot; {{ $circuitos->count() }} {{ Str::plural('circuito', $circuitos->count()) }}</div>
    <div class="grid">
        @foreach($circuitos as $e)
        @php [$txt, $bg] = $estadoColors[$e->estado] ?? ['#374151', '#f3f4f6']; @endphp
        <div class="card">
            <div class="top">
                <div class="cliente">{{ $e->cliente }}</div>
                <span class="badge" style="color:{{ $txt }};background:{{ $bg }}">{{ $e->estado }}</span>
            </div>
            <div class="tags">
                @if($e->carrier)<span class="tag carrier">{{ $e->carrier }}</span>@endif
                @if($e->id_circuito)<span class="tag pais">{{ $e->id_circuito }}</span>@endif
            </div>

            <div class="fields">
                @if($e->so_ref)<div class="field"><div class="k">SO / Ref.</div><div class="v">{{ $e->so_ref }}</div></div>@endif
                <div class="field"><div class="k">Capacidad</div><div class="v">{{ $capLabel($e->capacidad) }}</div></div>
                @if($e->gateway)<div class="field"><div class="k">Gateway</div><div class="v">{{ $e->gateway }}</div></div>@endif
                @if($e->ip_disponible)<div class="field"><div class="k">IP Disponible</div><div class="v">{{ $e->ip_disponible }}</div></div>@endif
                @if($e->mascara)<div class="field"><div class="k">Máscara</div><div class="v">{{ $e->mascara }}</div></div>@endif
                @if($e->dns)<div class="field"><div class="k">DNS Primario</div><div class="v">{{ $e->dns }}</div></div>@endif
                @if($e->dns_secundario)<div class="field"><div class="k">DNS Secundario</div><div class="v">{{ $e->dns_secundario }}</div></div>@endif
                @if($e->ubicacion)<div class="field full"><div class="k">Dirección</div><div class="v">{{ $e->ubicacion }}</div></div>@endif
            </div>

            @if($e->contacto_nombre || $e->contacto_telefono || $e->contacto_email)
            <div class="contacto">
                @if($e->contacto_nombre)<span class="nombre">{{ $e->contacto_nombre }}</span>@endif
                <span class="det">
                    @if($e->contacto_telefono) &middot; {{ $e->contacto_telefono }}@endif
                    @if($e->contacto_email) &middot; {{ $e->contacto_email }}@endif
                </span>
            </div>
            @endif

            @if($e->notas)
            <div class="notas"><div class="k">Notas</div>{{ $e->notas }}</div>
            @endif
        </div>
        @endforeach
    </div>
    @endforeach

    <div class="foot">
        Ovnicom &mdash; Control de Enlaces Carrier &middot; {{ number_format($stats['total']) }} circuitos &middot; Documento confidencial
    </div>

</body>
</html>
