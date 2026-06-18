<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Circuito de enlace carrier importado desde Excel.
 *
 * @property int         $id
 * @property int|null    $batch_id
 * @property string      $cliente
 * @property string|null $ubicacion
 * @property string|null $pais
 * @property string|null $carrier
 * @property string      $estado          activo | incidente | mantenimiento
 * @property string|null $so_ref          orden de servicio / referencia
 * @property string|null $id_circuito
 * @property int|null    $capacidad       en MB
 * @property string|null $gateway
 * @property string|null $ip_disponible
 * @property string|null $mascara
 * @property string|null $dns             DNS primario
 * @property string|null $dns_secundario
 * @property string|null $contacto_nombre
 * @property string|null $contacto_telefono
 * @property string|null $contacto_email
 * @property string|null $notas
 */
class EnlaceCarrier extends Model
{
    protected $table = 'enlaces_carrier';

    protected $fillable = [
        'batch_id',
        'cliente',
        'ubicacion',
        'pais',
        'carrier',
        'estado',
        'so_ref',
        'id_circuito',
        'capacidad',
        'gateway',
        'ip_disponible',
        'mascara',
        'dns',
        'dns_secundario',
        'contacto_nombre',
        'contacto_telefono',
        'contacto_email',
        'notas',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(EnlaceBatch::class, 'batch_id');
    }

    public function isActivo(): bool
    {
        return $this->estado === 'activo';
    }

    public function capacidadLabel(): string
    {
        if (!$this->capacidad) return '—';
        return $this->capacidad >= 1024
            ? round($this->capacidad / 1024, 2) . ' GB'
            : $this->capacidad . ' MB';
    }
}
