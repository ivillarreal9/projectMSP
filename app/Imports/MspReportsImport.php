<?php

namespace App\Imports;

use App\Models\MspReport;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Importador de reportes MSP desde archivos Excel (.xlsx / .xls).
 *
 * Procesa el Excel en lotes de 200 filas ({@see chunkSize()}) para evitar
 * agotamiento de memoria en archivos grandes. Cada fila se sanitiza, valida
 * y persiste mediante upsert por `ticket_number`. Las filas vacías o de tipos
 * excluidos se omiten silenciosamente.
 *
 * Formato esperado del Excel (columnas reconocidas por encabezado):
 * | Columna Excel                        | Campo en BD              | Notas                                              |
 * |--------------------------------------|--------------------------|----------------------------------------------------|
 * | ticket_number                        | ticket_number            | Entero. Clave de upsert.                           |
 * | customername                         | customer_name            | Nombre del cliente. Se crea en msp_clients si nuevo.|
 * | locationname                         | location_name            | Nombre de la sede/ubicación.                       |
 * | tickettitle                          | ticket_title             | Título o descripción corta del ticket.             |
 * | tickettype                           | ticket_type              | Subtipo MSP (puede incluir cancelación/instalación).|
 * | fecha_de_creacion                    | fecha_creacion           | Fecha de apertura. Acepta serial Excel, DateTime o string.|
 * | fecha_de_cierre                      | fecha_cierre             | Fecha de cierre. Mismo tratamiento que fecha_creacion.|
 * | tiempo_de_vida_del_ticket            | tiempo_vida_ticket       | Días como decimal. Si vacío se calcula entre fechas.|
 * | semana                               | semana                   | Número de semana (ej. "S12"). Si es fórmula (=...) se recalcula.|
 * | mes_cierre                           | mes_cierre               | Nombre del mes de cierre. Si es fórmula se recalcula.|
 * | tipo_de_ticket                       | tipo_ticket              | "Incidente" o "Solicitud". Otros tipos se descartan.|
 * | clasificacion_de_eventos             | clasificacion_eventos    | Normalizado a minúsculas para evitar duplicados.   |
 * | causa_de_dano                        | causa_dano               | Causa raíz del problema.                           |
 * | solucion                             | solucion                 | Solución aplicada.                                 |
 * | detalle                              | detalle                  | Información adicional del ticket.                  |
 * | tipo_de_cliente                      | tipo_cliente             | Clasificación del cliente (ej. "Corporativo").     |
 * | ubicacion_hopsa                      | ubicacion_hopsa          | Sede específica para cliente HOPSA.                |
 * | solucion_definitiva_recomendacion    | solucion_definitiva      | Recomendación de solución permanente.              |
 * | tipo_de_reporte                      | tipo_reporte             | "Alarma" o "Reportado".                            |
 * | email_cliente                        | email_cliente            | Correo del cliente (se ignora si vacío).           |
 * | logo_path                            | logo_path                | Ruta al logo en storage.                           |
 * | orden                                | numero_cuenta            | Número de cuenta. Se descarta si es fórmula Excel. |
 *
 * Reglas de filtrado:
 * - Se descartan filas sin ticket_number NI customer_name.
 * - Se descartan filas cuyo `tipo_de_ticket` no sea exactamente "Incidente" o "Solicitud".
 * - Se descartan filas cuyo `tickettype` contenga "cancelaci", "instalaci" o "inspecci".
 *
 * El período del ticket se deriva de la fecha de cierre (`fecha_de_cierre`) cuando
 * está disponible; en caso contrario se usa el período del lote pasado al constructor.
 *
 * Implementa las interfaces del paquete maatwebsite/excel:
 * - ToModel: cada fila se procesa individualmente (retorna null para omitir sin error).
 * - WithHeadingRow: los encabezados de la primera fila se usan como llaves del array $row.
 * - WithChunkReading: lectura en fragmentos para optimizar memoria.
 * - SkipsEmptyRows: omite automáticamente las filas completamente vacías.
 */
class MspReportsImport implements ToModel, WithHeadingRow, WithChunkReading, SkipsEmptyRows
{
    /**
     * Período de reporte del lote (ej. "Marzo 2025").
     * Se usa como fallback si la fila no tiene fecha de cierre.
     *
     * @var string
     */
    private string $periodo;

    /**
     * ID del lote de importación ({@see \App\Models\MspUploadBatch}).
     * Se asigna a cada ticket generado para trazabilidad.
     *
     * @var int
     */
    private int $batchId;

    /**
     * Constructor del importador.
     *
     * @param  string $periodo Período del lote en formato "Mes Año" (ej. "Marzo 2025").
     * @param  int    $batchId ID del registro MspUploadBatch creado antes de importar.
     */
    public function __construct(string $periodo, int $batchId)
    {
        $this->periodo = $periodo;
        $this->batchId = $batchId;
    }

    /**
     * Número de filas procesadas por fragmento (chunk).
     *
     * Se procesan 200 filas a la vez para mantener el uso de memoria bajo
     * en archivos con miles de tickets.
     *
     * @return int
     */
    public function chunkSize(): int
    {
        return 200;
    }

    /**
     * Procesa una fila del Excel y la persiste como ticket MSP.
     *
     * Flujo por fila:
     * 1. Sanitiza todos los campos (toStr, toInt, normalizeText, cleanFormula).
     * 2. Descarta la fila si no tiene ticket_number ni customer_name.
     * 3. Resuelve las fechas (serial Excel, DateTime o string ISO).
     * 4. Calcula tiempo de vida en días si no viene en la columna.
     * 5. Resuelve semana y mes de cierre (recalcula si la celda tenía fórmula Excel).
     * 6. Deriva el período desde la fecha de cierre o usa el período del lote.
     * 7. Descarta la fila si tipo_ticket no es "Incidente"/"Solicitud" o si
     *    ticket_type contiene cancelación/instalación/inspección.
     * 8. Crea el cliente en msp_clients si no existe (firstOrCreate).
     * 9. Hace upsert del ticket en msp_reports por ticket_number.
     *
     * Retorna siempre null (el upsert se hace manualmente con updateOrCreate).
     *
     * @param  array<string, mixed> $row Fila del Excel con encabezados como llaves.
     * @return MspReport|null            Siempre null (upsert manual).
     */
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
        $numeroCuenta = $this->cleanFormula($row['orden'] ?? null);

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

        // ─── Filtro de tipos válidos ───────────────────────────────────────────────
        // Solo Incidente y Solicitud pura (excluir Cancelación, Instalación, Inspección)
        $tipoTicketNorm = mb_strtolower(trim($tipoTicket ?? ''));
        $tipoTicketNorm2 = mb_strtolower(trim($ticketType ?? ''));

        $tiposExcluidos = ['cancelación', 'cancelacion', 'instalación', 'instalacion', 'inspección', 'inspeccion'];

        $esValido = in_array($tipoTicketNorm, ['incidente', 'solicitud'])
            && !collect($tiposExcluidos)->contains(fn($ex) => str_contains($tipoTicketNorm2, $ex));

        if (!$esValido) {
            return null; // ← salta la fila
        }

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
     * Convierte un valor a string limpio. Retorna null si el valor es vacío tras trim.
     *
     * @param  mixed       $value Valor de la celda Excel.
     * @return string|null        String limpio o null.
     */
    private function toStr(mixed $value): ?string
    {
        if ($value === null) return null;
        $clean = trim((string) $value);
        return $clean !== '' ? $clean : null;
    }

    /**
     * Convierte un valor a entero. Retorna null si el valor no es numérico.
     *
     * @param  mixed    $value Valor de la celda Excel.
     * @return int|null        Entero o null.
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
     *
     * Aplica: mb_strtolower + trim + colapso de espacios múltiples.
     *
     * @param  mixed       $value Valor de la celda Excel.
     * @return string|null        Texto normalizado o null si está vacío.
     */
    private function normalizeText(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        return preg_replace('/\s+/', ' ', trim(mb_strtolower((string) $value)));
    }

    /**
     * Convierte un valor de celda Excel a instancia Carbon.
     *
     * Maneja tres formatos posibles en orden de prioridad:
     * 1. Objeto DateTime (cuando PhpSpreadsheet ya interpretó la celda).
     * 2. Número serial de fecha Excel (ej. 45678.5 → fecha + hora).
     * 3. String con fecha en cualquier formato reconocible por Carbon::parse().
     *
     * @param  mixed                $value Valor crudo de la celda.
     * @return \Carbon\Carbon|null         Instancia Carbon o null si no se puede parsear.
     */
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

    /**
     * Limpia un valor de celda descartando fórmulas Excel no resueltas.
     *
     * Si el valor comienza con "=" (fórmula sin calcular), retorna null
     * para evitar guardar texto como "=A1+B1" en la base de datos.
     * También trunca el resultado a 100 caracteres como medida preventiva.
     *
     * @param  mixed       $value Valor de la celda Excel (campo "orden"/numero_cuenta).
     * @return string|null        Valor limpio y truncado, o null si es fórmula o vacío.
     */
    private function cleanFormula(mixed $value): ?string
    {
        $clean = $this->toStr($value);
        if ($clean === null) return null;

        // Si es fórmula Excel sin resolver → descartar
        if (str_starts_with($clean, '=')) return null;

        // Truncar por si acaso
        return substr($clean, 0, 100);
    }
}