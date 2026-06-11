<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de configuración del módulo META 2 (Telefonía).
 *
 * Almacena pares nombre/valor con estado activo/inactivo. Actualmente el módulo
 * META 2 consume los tickets directamente desde la API de MSP Manager con caché,
 * por lo que este modelo no tiene usos activos en el código — se conserva porque
 * la tabla `meta2` existe en producción.
 *
 * Tabla: meta2
 *
 * @property int         $id
 * @property string      $nombre      Nombre/clave del registro.
 * @property string|null $descripcion Descripción opcional del registro.
 * @property string      $valor       Valor almacenado (texto libre).
 * @property string      $estado      Estado del registro: "activo" o "inactivo".
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Meta2 extends Model
{
    /**
     * Nombre de la tabla (no sigue la convención plural de Eloquent).
     *
     * @var string
     */
    protected $table = 'meta2';

    /**
     * Campos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'valor',
        'estado',
    ];
}
