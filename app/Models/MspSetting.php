<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Modelo de configuración clave/valor del módulo MSP.
 *
 * Almacena ajustes dinámicos del sistema (credenciales de API, URLs, parámetros)
 * sin necesidad de tocar el archivo .env. Los valores se cachean 1 hora por clave
 * y el caché se invalida automáticamente al guardar.
 *
 * Tabla: msp_settings
 *
 * @property int         $id
 * @property string      $key       Clave única del ajuste (ej. "msp_username").
 * @property string|null $value     Valor almacenado.
 * @property string|null $label     Etiqueta legible para mostrar en UI.
 * @property bool        $is_secret Indica si el valor es sensible (no mostrar en claro).
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MspSetting extends Model
{
    /**
     * Campos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = ['key', 'value', 'label', 'is_secret'];

    /**
     * Conversiones automáticas de atributos.
     *
     * - `is_secret`: se trata como booleano.
     *
     * @var array<string, string>
     */
    protected $casts = ['is_secret' => 'boolean'];

    // ─── Obtener un valor por clave ───────────────────────────────────────────

    /**
     * Obtiene el valor de un ajuste por su clave, con caché de 1 hora.
     *
     * @param  string $key     Clave del ajuste.
     * @param  mixed  $default Valor por defecto si la clave no existe.
     * @return mixed           Valor almacenado o el default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("msp_setting_{$key}", 3600, function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    // ─── Guardar un valor ─────────────────────────────────────────────────────

    /**
     * Crea o actualiza un ajuste e invalida su caché.
     *
     * @param  string $key   Clave del ajuste.
     * @param  mixed  $value Nuevo valor a almacenar.
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("msp_setting_{$key}");
    }

    // ─── Obtener todos como array key => value ────────────────────────────────

    /**
     * Devuelve todos los ajustes como un mapa clave → valor (sin caché).
     *
     * @return array<string, mixed>
     */
    public static function allAsArray(): array
    {
        return static::all()->pluck('value', 'key')->toArray();
    }
}
