<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size:12px; color:#333; background:#fff; }

/* ── HEADER ── */
.header {
    background: linear-gradient(90deg, #1a1a2e 0%, #2d1a0e 50%, #d4520a 100%);
    color: white;
    padding: 16px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 70px;
}
.header-left { display:flex; align-items:center; gap:16px; }
.header-logo { max-height:50px; max-width:100px; object-fit:contain; }
.header-title h1 { font-size:18px; font-weight:700; }
.header-title p  { font-size:12px; opacity:.85; margin-top:2px; }

/* ── KPIs ── */
.kpis-section { padding: 20px 28px 10px; }
.kpis-grid { display:grid; grid-template-columns:1fr 1fr 2fr; gap:16px; }
.kpi-box { border:1px solid #e0e0e0; border-radius:8px; padding:14px 18px; background:#fff; }
.kpi-value { font-size:36px; font-weight:900; color:#1a1a2e; line-height:1; }
.kpi-label { font-size:11px; color:#666; margin-top:4px; }
.observacion { border:1px solid #f0e0c8; border-radius:8px; padding:14px 18px; background:#fdf6ee; }
.observacion h4 { font-size:12px; font-weight:700; color:#8b4513; margin-bottom:8px; }
.observacion ul { padding-left:16px; }
.observacion li { font-size:11px; color:#555; margin-bottom:3px; }
.observacion li strong { color:#333; }

/* ── SECTION TITLE ── */
.section-title { font-size:15px; font-weight:800; color:#1a1a2e; margin:20px 28px 12px; text-transform:uppercase; letter-spacing:.5px; }

/* ── CHARTS GRID ── */
.charts-grid { padding:0 28px; display:grid; grid-template-columns:1fr 1fr; gap:18px; }
.chart-card { border:1px solid #e8e8e8; border-radius:8px; padding:18px; background:#fff; }
.chart-card h5 { font-size:12px; font-weight:700; color:#333; margin-bottom:14px; text-align:center; }

/* ── BARRAS HORIZONTALES ── */
.chart-bar-row { display:flex; align-items:center; gap:8px; margin-bottom:8px; }
.chart-bar-label { font-size:10px; color:#555; width:110px; text-align:right; flex-shrink:0; }
.chart-bar-wrap { flex:1; background:#f0f0f0; border-radius:4px; height:18px; overflow:visible; position:relative; }
.chart-bar-fill { height:100%; border-radius:4px; background:#0f8a8a; display:flex; align-items:center; position:relative; }
.chart-bar-num-inside { font-size:10px; color:#fff; padding-left:6px; font-weight:700; }
.chart-bar-num-outside { font-size:10px; color:#333; font-weight:700; margin-left:6px; flex-shrink:0; }

/* ── PIE SVG ── */
.pie-wrap { display:flex; align-items:center; gap:20px; justify-content:center; padding:10px 0; }
.pie-svg { width:140px; height:140px; flex-shrink:0; }
.pie-legend { }
.pie-legend-item { display:flex; align-items:center; gap:8px; font-size:11px; color:#555; margin-bottom:6px; }
.pie-dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; }

/* ── ALARMA vs REPORTADO ── */
.grouped-legend { display:flex; gap:16px; justify-content:center; margin-bottom:12px; }
.legend-item { display:flex; align-items:center; gap:5px; font-size:10px; color:#555; }
.legend-dot { width:12px; height:12px; border-radius:2px; flex-shrink:0; }
.alarma-chart-wrap { display:flex; align-items:flex-end; justify-content:center; gap:10px; padding-bottom:20px; border-bottom:2px solid #e0e0e0; }
.alarma-group { display:flex; flex-direction:column; align-items:center; gap:4px; }
.alarma-bars { display:flex; align-items:flex-end; gap:3px; }
.alarma-label { font-size:9px; color:#666; }

/* ── TABLE ── */
.table-section { padding:0 28px 28px; margin-top:20px; }
.detail-table { width:100%; border-collapse:collapse; font-size:11px; }
.detail-table thead tr { background:#1a1a2e; color:white; }
.detail-table th { padding:9px 10px; text-align:left; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; }
.detail-table td { padding:8px 10px; vertical-align:top; }
.detail-table tbody tr:nth-child(even) { background:#f8f8f8; }
.detail-table tbody tr { border-bottom:1px solid #eee; }
.badge { padding:2px 7px; border-radius:20px; font-size:10px; font-weight:600; }
.badge-inc { background:#fee2e2; color:#dc2626; }
.badge-sol { background:#dbeafe; color:#1d4ed8; }
.page-break { page-break-before:always; }
</style>
</head>
<body>

{{-- HEADER --}}
<div class="header">
    <div class="header-left">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" class="header-logo" alt="{{ $customer }}">
        @else
            <div style="background:rgba(255,255,255,.2);border-radius:8px;padding:8px 12px;font-weight:900;font-size:14px;">
                {{ strtoupper(substr($customer,0,3)) }}
            </div>
        @endif
        <div class="header-title">
            <h1>Informe mensual - {{ $periodo ?? 'Reporte' }}</h1>
            <p>{{ strtoupper($customer) }}</p>
        </div>
    </div>
    <div style="font-size:22px;font-weight:900;letter-spacing:1px;opacity:.95">🔷VNICOM</div>
</div>

{{-- KPIs --}}
<div class="kpis-section">
    <div class="kpis-grid">
        <div style="display:grid;gap:12px;">
            <div class="kpi-box">
                <div class="kpi-value">{{ $stats['cant_incidentes'] }}</div>
                <div class="kpi-label">Cant. Incidentes</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-value">{{ $stats['cant_solicitudes'] }}</div>
                <div class="kpi-label">Cant. de Solicitudes</div>
            </div>
        </div>
        <div style="display:grid;gap:12px;">
            <div class="kpi-box">
                <div class="kpi-value">{{ number_format($stats['tiempo_prom_incidentes'],1) }}</div>
                <div class="kpi-label">Tiempo Promedio de Atención - Incidentes</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-value">{{ number_format($stats['tiempo_prom_solicitudes'],1) }}</div>
                <div class="kpi-label">Tiempo Promedio de Atención - Solicitudes</div>
            </div>
        </div>
        <div class="observacion">
            <h4>Observación:</h4>
            <ul>
                <li><strong>Unidad de tiempo:</strong> Días.</li>
                <li><strong>Gráfico Recuento de tickets:</strong>
                    <ul style="padding-left:12px;margin-top:4px;">
                        <li style="margin-bottom:3px;">○ <strong>Alarma:</strong> Incidente generado proactivamente posterior al análisis de Monitoreo.</li>
                        <li>○ <strong>Reportado:</strong> Incidente generado al momento que el cliente reporta.</li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="section-title">Actividad del Mes</div>

@php
$solUbic = $stats['por_ubicacion_solicitudes'];
$incUbic = $stats['por_ubicacion_incidentes'];
$clasif  = $stats['por_clasificacion'];
$alarmaS = $stats['alarma_vs_reportado_semana'];

$solArr = $solUbic->toArray();
$maxSol = count($solArr) > 0 ? max(array_values($solArr)) : 1;
$maxSol = max($maxSol, 1);

$incArr = $incUbic->toArray();
$maxInc = count($incArr) > 0 ? max(array_values($incArr)) : 1;
$maxInc = max($maxInc, 1);

$maxGrupo = 1;
foreach ($alarmaS as $s) { $maxGrupo = max($maxGrupo, $s['Alarma'] ?? 0, $s['Reportado'] ?? 0); }

$total = $clasif->sum();
$pieColors = ['#d4520a','#0f8a8a','#8b3408','#f0a07a','#6b2d08'];
$startAngle = -90;
$pieSegments = [];
foreach ($clasif as $label => $val) {
    $pct = $total > 0 ? $val / $total : 0;
    $end = $startAngle + $pct * 360;
    $pieSegments[] = ['label'=>$label,'val'=>$val,'pct'=>$pct,'start'=>$startAngle,'end'=>$end,'color'=>current($pieColors)];
    next($pieColors);
    $startAngle = $end;
}
// Después
if (!function_exists('svgArc')) {
    function svgArc($cx,$cy,$r,$startDeg,$endDeg) {
        $s = deg2rad($startDeg); $e = deg2rad($endDeg);
        $x1=round($cx+$r*cos($s),2); $y1=round($cy+$r*sin($s),2);
        $x2=round($cx+$r*cos($e),2); $y2=round($cy+$r*sin($e),2);
        $large = ($endDeg-$startDeg > 180) ? 1 : 0;
        return "M {$cx} {$cy} L {$x1} {$y1} A {$r} {$r} 0 {$large} 1 {$x2} {$y2} Z";
    }
}
$barMaxH = 80;
$semanasKeys = collect($alarmaS)->keys()->sort()->values();
@endphp

<div class="charts-grid">

    {{-- Solicitudes por ubicación --}}
    <div class="chart-card">
        <h5>Solicitudes generadas por Ubicación</h5>
        @foreach($solUbic->take(7) as $loc => $cnt)
        @php $w = max(5, round($cnt/$maxSol*100)); @endphp
        <div class="chart-bar-row">
            <div class="chart-bar-label">{{ $loc }}</div>
            <div class="chart-bar-wrap">
                <div style="display:flex; align-items:center; gap:6px;">
                    <div class="chart-bar-wrap" style="flex:1;">
                        <div class="chart-bar-fill" style="width:{{ $w }}%"></div>
                    </div>
                    <span style="font-size:10px;font-weight:700;color:#333;flex-shrink:0;min-width:16px;">{{ $cnt }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Incidentes por ubicación --}}
    <div class="chart-card">
        <h5>Incidentes generados por Ubicación</h5>
        @foreach($incUbic->take(7) as $loc => $cnt)
        @php $w = max(5, round($cnt/$maxInc*100)); @endphp
        <div class="chart-bar-row">
            <div class="chart-bar-label">{{ $loc }}</div>
            <div class="chart-bar-wrap">
                <div style="display:flex; align-items:center; gap:6px;">
                    <div class="chart-bar-wrap" style="flex:1;">
                        <div class="chart-bar-fill" style="width:{{ $w }}%"></div>
                    </div>
                    <span style="font-size:10px;font-weight:700;color:#333;flex-shrink:0;min-width:16px;">{{ $cnt }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Pie — Clasificación --}}
    <div class="chart-card">
        <h5>Cant. Incidentes por Clasificación de eventos</h5>
        <div class="pie-wrap">
            <svg class="pie-svg" viewBox="0 0 160 160" style="width:160px;height:160px;">
                @foreach($pieSegments as $seg)
                    @if($seg['pct'] > 0)
                    @php
                        $midAngle = ($seg['start'] + $seg['end']) / 2;
                        // Radio para la etiqueta AFUERA del pie
                        $labelR = 60;
                        $lx = round(80 + $labelR * cos(deg2rad($midAngle)), 2);
                        $ly = round(80 + $labelR * sin(deg2rad($midAngle)) + 4, 2);
                        // Línea desde el borde del pie hasta la etiqueta
                        $lineR1 = 46; // borde del pie
                        $lineR2 = 54; // inicio de la línea
                        $l1x = round(80 + $lineR1 * cos(deg2rad($midAngle)), 2);
                        $l1y = round(80 + $lineR1 * sin(deg2rad($midAngle)), 2);
                        $l2x = round(80 + $lineR2 * cos(deg2rad($midAngle)), 2);
                        $l2y = round(80 + $lineR2 * sin(deg2rad($midAngle)), 2);
                    @endphp
                   {{-- Segmento del pie --}}
                    @if(count($pieSegments) === 1)
                        <circle cx="80" cy="80" r="44"
                            fill="{{ $seg['color'] }}" stroke="white" stroke-width="1.5"/>
                        <text x="80" y="34"
                            text-anchor="middle" font-size="10"
                            fill="{{ $seg['color'] }}" font-weight="bold">
                            {{ $seg['val'] }}
                        </text>
                    @else
                        <path d="{{ svgArc(80,80,44,$seg['start'],$seg['end']) }}"
                            fill="{{ $seg['color'] }}" stroke="white" stroke-width="1.5"/>
                        <line x1="{{ $l1x }}" y1="{{ $l1y }}"
                            x2="{{ $l2x }}" y2="{{ $l2y }}"
                            stroke="{{ $seg['color'] }}" stroke-width="1.2"/>
                        <text x="{{ $lx }}" y="{{ $ly }}"
                            text-anchor="middle" font-size="10"
                            fill="{{ $seg['color'] }}" font-weight="bold">
                            {{ $seg['val'] }}
                        </text>
                    @endif
                    @endif
                @endforeach
            </svg>
            <div class="pie-legend">
                @foreach($pieSegments as $seg)
                <div class="pie-legend-item">
                    <div class="pie-dot" style="background:{{ $seg['color'] }}"></div>
                    <span>{{ $seg['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Alarma vs Reportado — centrado --}}
    <div class="chart-card">
        <h5>Recuento de Incidentes - Alarma vs Reportado<br>Alarma vs Reportado por Semana</h5>
        <div class="grouped-legend">
            <div class="legend-item">
                <div class="legend-dot" style="background:#0f8a8a"></div> Alarma
            </div>
            <div class="legend-item">
                <div class="legend-dot" style="background:#d4520a"></div> Reportado
            </div>
        </div>
        <div class="alarma-chart-wrap" style="min-height:{{ $barMaxH + 20 }}px;">
            @foreach($semanasKeys as $sem)
            @php
                $a  = $alarmaS[$sem]['Alarma']    ?? 0;
                $r  = $alarmaS[$sem]['Reportado'] ?? 0;
                $hA = $maxGrupo > 0 ? round($a / $maxGrupo * $barMaxH) : 0;
                $hR = $maxGrupo > 0 ? round($r / $maxGrupo * $barMaxH) : 0;
            @endphp
            <div class="alarma-group">
                {{-- Valores encima --}}
                <div style="display:flex;gap:3px;margin-bottom:2px;">
                    <span style="width:16px;text-align:center;font-size:8px;color:#0f8a8a;font-weight:700;">{{ $a > 0 ? $a : '' }}</span>
                    <span style="width:16px;text-align:center;font-size:8px;color:#d4520a;font-weight:700;">{{ $r > 0 ? $r : '' }}</span>
                </div>
                {{-- Barras --}}
                <div class="alarma-bars" style="height:{{ $barMaxH }}px;align-items:flex-end;">
                    <div style="width:16px;height:{{ max(2,$hA) }}px;background:#0f8a8a;border-radius:3px 3px 0 0;"></div>
                    <div style="width:16px;height:{{ max(2,$hR) }}px;background:#d4520a;border-radius:3px 3px 0 0;"></div>
                </div>
                {{-- Semana --}}
                <div class="alarma-label">{{ $sem }}</div>
            </div>
            @endforeach
        </div>
        <div style="font-size:8px;text-align:center;color:#aaa;margin-top:4px;">0</div>
    </div>

</div>

{{-- PÁGINA 2 --}}
<div class="page-break"></div>

<div class="header">
    <div class="header-left">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" class="header-logo" alt="{{ $customer }}">
        @endif
        <div class="header-title">
            <h1>Informe mensual - {{ $periodo ?? 'Reporte' }}</h1>
            <p>{{ strtoupper($customer) }}</p>
        </div>
    </div>
    <div style="font-size:22px;font-weight:900;letter-spacing:1px;opacity:.95">🔷VNICOM</div>
</div>

<div class="table-section" style="padding-top:24px">
    <div style="font-size:15px;font-weight:800;color:#1a1a2e;margin-bottom:14px;text-transform:uppercase;letter-spacing:.5px;">
        Detalle de Tickets
    </div>
    <table class="detail-table">
        <thead>
            <tr>
                <th style="width:70px">Ticket</th>
                <th style="width:80px">Tipo de Ticket</th>
                <th>Descripción Corta</th>
                <th>Causa de Daño</th>
                <th>Solución</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats['detalle_tickets'] as $t)
            <tr>
                <td style="font-weight:700;font-family:monospace">{{ $t['ticket'] }}</td>
                <td><span class="badge {{ $t['tipo'] === 'Incidente' ? 'badge-inc' : 'badge-sol' }}">{{ $t['tipo'] }}</span></td>
                <td>{{ $t['descripcion'] }}</td>
                <td>{{ $t['causa'] }}</td>
                <td>{{ $t['solucion'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

</body>
</html>