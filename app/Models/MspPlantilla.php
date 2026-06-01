<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa una plantilla de correo electrónico para el envío de reportes MSP.
 *
 * Las plantillas definen el asunto y cuerpo de los correos que se envían a los
 * clientes junto con sus reportes PDF. Pueden incluir una imagen de cabecera que
 * se embebe como base64. Solo puede haber una plantilla marcada como predeterminada;
 * esta se selecciona automáticamente en el flujo de envío masivo.
 *
 * Tabla: msp_plantillas
 *
 * @property int         $id                Identificador único de la plantilla.
 * @property string      $nombre            Nombre descriptivo de la plantilla (uso interno).
 * @property string      $asunto            Asunto del correo electrónico.
 * @property string      $mensaje           Cuerpo del correo en texto o HTML.
 * @property string|null $imagen_path       Ruta relativa en storage/app/public hacia la imagen de cabecera.
 * @property bool        $es_predeterminada Indica si esta es la plantilla por defecto en envíos masivos.
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MspPlantilla extends Model
{
    /**
     * Campos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'asunto',
        'mensaje',
        'imagen_path',
        'es_predeterminada',
    ];

    /**
     * Conversiones automáticas de atributos.
     *
     * - `es_predeterminada`: se trata como booleano para comparaciones directas.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'es_predeterminada' => 'boolean',
    ];

    /**
     * Accesor: devuelve la imagen de la plantilla codificada en base64 como data URI.
     *
     * Lee el archivo desde `storage/app/public/{imagen_path}`, detecta el tipo MIME
     * y construye un data URI apto para embeber en HTML/PDF sin dependencias externas.
     * Retorna null si no hay imagen configurada o si el archivo no existe en disco.
     *
     * Acceso: $plantilla->imagen_url
     *
     * @return string|null Data URI "data:{mime};base64,{contenido}" o null si no disponible.
     */
    public function getImagenUrlAttribute(): ?string
    {
        if (!$this->imagen_path) return null;
        $path = storage_path('app/public/' . $this->imagen_path);
        if (!file_exists($path)) return null;
        $mime   = mime_content_type($path);
        $base64 = base64_encode(file_get_contents($path));
        return "data:{$mime};base64,{$base64}";
    }
}