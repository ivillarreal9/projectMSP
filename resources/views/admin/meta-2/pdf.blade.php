<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page { size: legal landscape; margin: 10mm; }
        body  { font-family: Arial, sans-serif; background: white; }

        .summary-th { background-color: #CC0000; color: white; }

        .detail-th  { background-color: #1a1a1a; color: white; }

        tr:nth-child(even) td { background-color: #f9fafb; }
    </style>
</head>
<body class="p-6 text-xs text-gray-900">

    {{-- Encabezado --}}
    <p class="text-sm font-bold mb-1">Informe mensual para la medición de la Meta No.2</p>
    <p class="text-sm font-bold mb-4 text-gray-700">
        Averías Reparadas durante el mes de {{ ucfirst($month) }} {{ $year }}
    </p>

    {{-- Tabla resumen --}}
    <table class="border-collapse mb-6" style="width:62%">
        <thead>
            <tr>
                <th class="summary-th border border-gray-400 px-3 py-2 text-center text-xs leading-tight">
                    Lugar de Cobertura de<br>Área Geográfica
                </th>
                <th class="summary-th border border-gray-400 px-3 py-2 text-center text-xs leading-tight">
                    Total de Averías<br>Pendiente
                </th>
                <th class="summary-th border border-gray-400 px-3 py-2 text-center text-xs leading-tight">
                    Total de Averías Reparadas<br>del mes de {{ ucfirst($month) }}
                </th>
                <th class="summary-th border border-gray-400 px-3 py-2 text-center text-xs leading-tight">
                    % de averías reparadas en 48<br>horas hábiles por Provincia
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary as $row)
            <tr>
                <td class="border border-gray-300 px-3 py-1.5 text-center">{{ $row['provincia'] }}</td>
                <td class="border border-gray-300 px-3 py-1.5 text-center">{{ $row['pendientes'] }}</td>
                <td class="border border-gray-300 px-3 py-1.5 text-center">{{ $row['reparados'] }}</td>
                <td class="border border-gray-300 px-3 py-1.5 text-center font-semibold">{{ $row['porcentaje'] }}</td>
            </tr>
            @endforeach

            @for($i = count($summary); $i < 5; $i++)
            <tr>
                <td class="border border-gray-300 px-3 py-1.5">&nbsp;</td>
                <td class="border border-gray-300 px-3 py-1.5 text-center">0</td>
                <td class="border border-gray-300 px-3 py-1.5 text-center">0</td>
                <td class="border border-gray-300 px-3 py-1.5 text-center">0%</td>
            </tr>
            @endfor
        </tbody>
    </table>

    {{-- Detalle por provincia --}}
    @foreach($summary as $row)
        @if(!empty($row['tickets']))
        <div class="mb-6">

            {{-- Título provincia --}}
            <div class="flex items-center gap-2 mb-2">
                <div class="w-1 h-4 bg-red-600 rounded"></div>
                <p class="text-xs font-bold text-gray-700">{{ $row['provincia'] }}</p>
            </div>

            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="detail-th border border-gray-600 px-2 py-1.5 text-center text-xs">Provincias</th>
                        <th class="detail-th border border-gray-600 px-2 py-1.5 text-center text-xs">Orden</th>
                        <th class="detail-th border border-gray-600 px-2 py-1.5 text-center text-xs">Teléfono</th>
                        <th class="detail-th border border-gray-600 px-2 py-1.5 text-center text-xs">Reporte 1</th>
                        <th class="detail-th border border-gray-600 px-2 py-1.5 text-center text-xs">Reporte 2</th>
                        <th class="detail-th border border-gray-600 px-2 py-1.5 text-center text-xs">Ubicación</th>
                        <th class="detail-th border border-gray-600 px-2 py-1.5 text-center text-xs">Causa</th>
                        <th class="detail-th border border-gray-600 px-2 py-1.5 text-center text-xs">F_Reparación</th>
                        <th class="detail-th border border-gray-600 px-2 py-1.5 text-center text-xs">H_Reparación</th>
                        <th class="detail-th border border-gray-600 px-2 py-1.5 text-center text-xs">F_Reporte</th>
                        <th class="detail-th border border-gray-600 px-2 py-1.5 text-center text-xs">H_Reporte</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($row['tickets'] as $ticket)
                    @php
                        $cf          = $ticket['customFields'] ?? [];
                        $completedDt = !empty($ticket['CompletedDate'])
                            ? \Carbon\Carbon::parse($ticket['CompletedDate'])->subHours(5)
                            : null;
                        $createdDt   = !empty($ticket['CreatedDate'])
                            ? \Carbon\Carbon::parse($ticket['CreatedDate'])->subHours(5)
                            : null;
                    @endphp
                    <tr>
                        <td class="border border-gray-200 px-2 py-1 text-center">{{ $cf['Provincia'] ?? $row['provincia'] }}</td>
                        <td class="border border-gray-200 px-2 py-1 text-center font-mono">{{ $ticket['TicketNumber'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-2 py-1 text-center">{{ $cf['Teléfono'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-2 py-1 text-center">
                            {{ $cf['Reporte 1'] ? strtok(trim($cf['Reporte 1']), ' ') : '—' }}
                        </td>
                        <td class="border border-gray-200 px-2 py-1 text-center">
                            {{ $cf['Detalle - Reporte 2'] ? strtok(trim($cf['Detalle - Reporte 2']), ' ') : '—' }}
                        </td>
                        <td class="border border-gray-200 px-2 py-1 text-center">
                            @php
                                $ubicVal   = trim($cf['Ubicación Cierre'] ?? $cf['Ubicación'] ?? '');
                                $ubicParts = explode(' ', $ubicVal);
                                $ubicCode  = isset($ubicParts[1]) && strlen($ubicParts[1]) <= 2
                                    ? $ubicParts[0] . ' ' . $ubicParts[1]
                                    : ($ubicParts[0] ?? '—');
                            @endphp
                            {{ $ubicCode ?: '—' }}
                        </td>
                        <td class="border border-gray-200 px-2 py-1 text-center">
                            {{ $cf['Causa'] ? strtok(trim($cf['Causa']), ' ') : '—' }}
                        </td>
                        <td class="border border-gray-200 px-2 py-1 text-center">{{ $completedDt ? $completedDt->format('d/m/y') : '—' }}</td>
                        <td class="border border-gray-200 px-2 py-1 text-center">{{ $completedDt ? $completedDt->format('H:i') : '—' }}</td>
                        <td class="border border-gray-200 px-2 py-1 text-center">{{ $createdDt ? $createdDt->format('d/m/y') : '—' }}</td>
                        <td class="border border-gray-200 px-2 py-1 text-center">{{ $createdDt ? $createdDt->format('H:i') : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    @endforeach

    {{-- Footer --}}
    <div class="fixed bottom-4 right-6 text-gray-400" style="font-size:7px">
        Generado el {{ now()->subHours(5)->format('d/m/Y H:i') }}
    </div>

</body>
</html>