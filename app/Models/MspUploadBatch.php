<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que registra cada lote (batch) de importación de reportes MSP.
 *
 * Cada vez que se sube un archivo Excel con tickets MSP, se crea un registro
 * de este modelo para llevar trazabilidad de qué archivo fue procesado, en qué
 * período y cuántos registros resultaron. Además se almacena el ID del ítem en
 * SharePoint si el archivo fue subido exitosamente a la nube.
 *
 * Los registros de {@see \App\Models\MspReport} generados por una importación
 * referencian a este batch mediante la columna `batch_id`.
 *
 * Tabla: msp_upload_batches
 *
 * @property int         $id                 Identificador único del lote.
 * @property string      $filename           Nombre original del archivo Excel importado.
 * @property string      $periodo            Período de reporte del lote (ej. "Marzo 2025").
 * @property int         $total_registros    Número de tickets insertados/actualizados en este lote.
 * @property int         $clientes_unicos    Número de clientes distintos encontrados en el lote.
 * @property string|null $sharepoint_item_id ID del ítem en SharePoint si el archivo fue archivado.
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MspUploadBatch extends Model
{
    /**
     * Campos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'filename', 'periodo', 'total_registros', 'clientes_unicos', 'sharepoint_item_id',
    ];
}
