<?php

namespace App\Imports;

use App\Models\MspReport;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class MspReportsImport implements ToModel, WithHeadingRow, WithChunkReading, SkipsEmptyRows
{
    private string $periodo;
    private int $batchId;

    public function __construct(string $periodo, int $batchId)
    {
        $this->periodo = $periodo;
        $this->batchId = $batchId;
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function model(array $row): ?MspReport
    {
        if (empty($row['customername']) && empty($row['ticket_number'])) {
            return null;
        }

        // Resolver fechas — pueden venir como datetime object, número serial Excel, o string
        $fechaCreacion = $this->resolveDate($row['fecha_de_creacion'] ?? null);
        $fechaCierre   = $this->resolveDate($row['fecha_de_cierre'] ?? null);

        // Tiempo de vida — puede ser fórmula string o número
        $tiempoVida = null;
        $rawTiempo = $row['tiempo_de_vida_del_ticket'] ?? null;
        if (is_numeric($rawTiempo)) {
            $tiempoVida = round((float)$rawTiempo, 4);
        } elseif ($fechaCreacion && $fechaCierre) {
            $tiempoVida = round($fechaCreacion->diffInSeconds($fechaCierre) / 86400, 4);
        }

        // Semana — puede ser fórmula string o valor calculado
        $semana = $row['semana'] ?? null;
        if ($semana && str_starts_with((string)$semana, '=')) {
            $semana = $fechaCierre ? 'S' . $fechaCierre->weekOfYear : null;
        }

        // Mes cierre
        $mesCierre = $row['mes_cierre'] ?? null;
        if ($mesCierre && str_starts_with((string)$mesCierre, '=')) {
            $mesCierre = $fechaCierre ? ucfirst($fechaCierre->translatedFormat('F')) : null;
        }

        return new MspReport([
            'ticket_number'       => $row['ticket_number'] ?? null,
            'customer_name'       => isset($row['customername']) ? trim($row['customername']) : null,
            'location_name'       => isset($row['locationname']) ? trim($row['locationname']) : null,
            'ticket_title'        => $row['tickettitle'] ?? null,
            'ticket_type'         => $row['tickettype'] ?? null,
            'fecha_creacion'      => $fechaCreacion,
            'fecha_cierre'        => $fechaCierre,
            'tiempo_vida_ticket'  => $tiempoVida,
            'semana'              => $semana,
            'mes_cierre'          => $mesCierre,
            'tipo_ticket'         => isset($row['tipo_de_ticket']) ? trim($row['tipo_de_ticket']) : null,
            'clasificacion_eventos' => isset($row['clasificacion_de_eventos']) ? trim($row['clasificacion_de_eventos']) : null,
            'causa_dano'          => isset($row['causa_de_dano']) ? trim($row['causa_de_dano']) : null,
            'solucion'            => isset($row['solucion']) ? trim($row['solucion']) : null,
            'detalle'             => $row['detalle'] ?? null,
            'tipo_cliente'        => isset($row['tipo_de_cliente']) ? trim($row['tipo_de_cliente']) : null,
            'ubicacion_hopsa'     => $row['ubicacion_hopsa'] ?? null,
            'solucion_definitiva' => $row['solucion_definitiva_recomendacion'] ?? null,
            'tipo_reporte'        => $row['tipo_de_reporte'] ?? null,
            'email_cliente'       => $row['email_cliente'] ?? null,
            'logo_path'           => $row['logo_path'] ?? null,
            'periodo'             => $this->periodo,
        ]);
    }

    private function resolveDate(mixed $value): ?\Carbon\Carbon
    {
        if (!$value) return null;

        // Si ya es un objeto Carbon/DateTime
        if ($value instanceof \DateTime) {
            return \Carbon\Carbon::instance($value);
        }

        // Si es número serial de Excel
        if (is_numeric($value)) {
            try {
                $date = ExcelDate::excelToDateTimeObject((float)$value);
                return \Carbon\Carbon::instance($date);
            } catch (\Throwable) {}
        }

        // Si es string de fecha
        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
