<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo del módulo Control de Enlaces.
 *
 * @property int         $id
 * @property string      $nombre
 * @property string      $url
 * @property string|null $descripcion
 * @property string|null $categoria
 * @property string      $estado         activo | inactivo
 * @property int         $created_by
 */
class Enlace extends Model
{
    protected $fillable = [
        'nombre',
        'url',
        'descripcion',
        'categoria',
        'estado',
        'created_by',
    ];

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActivo(): bool
    {
        return $this->estado === 'activo';
    }
}
