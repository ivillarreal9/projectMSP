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
        // ─── Sanitizar TODOS los campos antes de usarlos ───────────────────────

        // Enteros
        $ticketNumber = $this->toInt($row['ticket_number'] ?? null);

        // Strings
        $customerName        = $this->toStr($row['customername'] ?? null);
        $locationName        = $this->toStr($row['locationname'] ?? null);
        $ticketTitle         = $this->toStr($row['tickettitle'] ?? null);
        $ticketType          = $this->toStr($row['tickettype'] ?? null);
        $semanaRaw           = $this->toStr($row['semana'] ?? null);
        $mesCierreRaw        = $this->toStr($row['mes_cierre'] ?? null);
        $tipoTicket          = $this->toStr($row['tipo_de_ticket'] ?? null);
        $causaDano           = $this->toStr($row['causa_de_dano'] ?? null);
        $solucion            = $this->toStr($row['solucion'] ?? null);
        $detalle             = $this->toStr($row['detalle'] ?? null);
        $tipoCliente         = $this->toStr($row['tipo_de_cliente'] ?? null);
        $ubicacionHopsa      = $this->toStr($row['ubicacion_hopsa'] ?? null);
        $solucionDefinitiva  = $this->toStr($row['solucion_definitiva_recomendacion'] ?? null);
        $tipoReporte         = $this->toStr($row['tipo_de_reporte'] ?? null);
        $emailCliente        = $this->toStr($row['email_cliente'] ?? null);
        $logoPath            = $this->toStr($row['logo_path'] ?? null);
        $numeroCuenta        = $this->toStr($row['orden'] ?? null);

        // Normalizado (lowercase + trim)
        $clasificacionEventos = $this->normalizeText($row['clasificacion_de_eventos'] ?? null);

        // Saltar fila si no tiene datos mínimos
        if (!$ticketNumber && empty($customerName)) {
            return null;
        }

        // ─── Fechas ────────────────────────────────────────────────────────────
        $fechaCreacion = $this->resolveDate($row['fecha_de_creacion'] ?? null);
        $fechaCierre   = $this->resolveDate($row['fecha_de_cierre'] ?? null);

        // ─── Tiempo de vida ────────────────────────────────────────────────────
        $tiempoVida = null;
        $rawTiempo  = $row['tiempo_de_vida_del_ticket'] ?? null;
        if (is_numeric($rawTiempo)) {
            $tiempoVida = round((float) $rawTiempo, 4);
        } elseif ($fechaCreacion && $fechaCierre) {
            $tiempoVida = round($fechaCreacion->diffInSeconds($fechaCierre) / 86400, 4);
        }

        // ─── Semana ────────────────────────────────────────────────────────────
        $semana = $semanaRaw;
        if ($semana && str_starts_with($semana, '=')) {
            $semana = $fechaCierre ? 'S' . $fechaCierre->weekOfYear : null;
        }

        // ─── Mes cierre ────────────────────────────────────────────────────────
        $mesCierre = $mesCierreRaw;
        if ($mesCierre && str_starts_with($mesCierre, '=')) {
            $mesCierre = $fechaCierre ? ucfirst($fechaCierre->translatedFormat('F')) : null;
        }

        // ─── Período derivado de fecha de cierre ───────────────────────────────
        $periodo = $fechaCierre
            ? ucfirst($fechaCierre->translatedFormat('F Y'))
            : $this->periodo;

        // ─── Crear cliente si no existe ────────────────────────────────────────
        if ($customerName) {
            \App\Models\MspClient::firstOrCreate(
                ['customer_name' => $customerName]
            );
        }

        // ─── Upsert ────────────────────────────────────────────────────────────
        MspReport::updateOrCreate(
            ['ticket_number' => $ticketNumber],
            [
                'customer_name'         => $customerName,
                'location_name'         => $locationName,
                'ticket_title'          => $ticketTitle,
                'ticket_type'           => $ticketType,
                'fecha_creacion'        => $fechaCreacion,
                'fecha_cierre'          => $fechaCierre,
                'tiempo_vida_ticket'    => $tiempoVida,
                'semana'                => $semana,
                'mes_cierre'            => $mesCierre,
                'tipo_ticket'           => $tipoTicket,
                'clasificacion_eventos' => $clasificacionEventos,
                'causa_dano'            => $causaDano,
                'solucion'              => $solucion,
                'detalle'               => $detalle,
                'tipo_cliente'          => $tipoCliente,
                'ubicacion_hopsa'       => $ubicacionHopsa,
                'solucion_definitiva'   => $solucionDefinitiva,
                'tipo_reporte'          => $tipoReporte,
                'email_cliente'         => $emailCliente,
                'logo_path'             => $logoPath,
                'numero_cuenta'         => $numeroCuenta,
                'batch_id'              => $this->batchId,
                'periodo'               => $periodo,
            ]
        );

        return null;
    }

    // ─── Helpers de sanitización ───────────────────────────────────────────────

    /**
     * Convierte a string limpio, retorna null si está vacío.
     */
    private function toStr(mixed $value): ?string
    {
        if ($value === null) return null;
        $clean = trim((string) $value);
        return $clean !== '' ? $clean : null;
    }

    /**
     * Convierte a entero, retorna null si no es numérico.
     */
    private function toInt(mixed $value): ?int
    {
        if ($value === null) return null;
        $clean = trim((string) $value);
        return ($clean !== '' && is_numeric($clean)) ? (int) $clean : null;
    }

    /**
     * Normaliza texto a minúsculas limpias para evitar duplicados por
     * diferencias de capitalización ("No Imputable" vs "no imputable").
     */
    private function normalizeText(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        return preg_replace('/\s+/', ' ', trim(mb_strtolower((string) $value)));
    }

    private function resolveDate(mixed $value): ?\Carbon\Carbon
    {
        if (!$value) return null;

        if ($value instanceof \DateTime) {
            return \Carbon\Carbon::instance($value);
        }

        if (is_numeric($value)) {
            try {
                $date = ExcelDate::excelToDateTimeObject((float) $value);
                return \Carbon\Carbon::instance($date);
            } catch (\Throwable) {}
        }

        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}