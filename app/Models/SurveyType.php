<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Modelo que define un tipo de encuesta y su esquema de campos.
 *
 * Cada tipo genera automáticamente al crearse un slug único y un token secreto
 * de 60 caracteres que autentica el webhook público `POST /api/surveys/{token}`.
 * El array `campos` define qué claves se esperan en el JSON de cada respuesta.
 *
 * Tabla: survey_types
 *
 * Relaciones:
 * - Tiene muchas {@see \App\Models\Survey} mediante `survey_type_id`.
 *
 * @property int         $id
 * @property string      $nombre Nombre legible del tipo de encuesta.
 * @property string      $slug   Identificador URL-amigable (auto-generado, con sufijo aleatorio).
 * @property array       $campos Lista de campos esperados en cada respuesta.
 * @property string      $token  Token secreto del webhook (oculto en serialización).
 * @property bool        $activo Indica si el tipo acepta nuevas respuestas.
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class SurveyType extends Model
{
    /**
     * Campos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'slug',
        'campos',
        'token',
        'activo',
    ];

    /**
     * Atributos excluidos de la serialización JSON/array.
     *
     * El token autentica el webhook público — no debe filtrarse si el modelo
     * se serializa en una respuesta. Los flujos que lo muestran (snippet,
     * generación de token) lo acceden explícitamente.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'token',
    ];

    /**
     * Conversiones automáticas de atributos.
     *
     * - `campos`: la columna JSON se deserializa automáticamente como array PHP.
     * - `activo`: se trata como booleano.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'campos' => 'array',
        'activo' => 'boolean',
    ];

    /**
     * Hook del ciclo de vida Eloquent: genera slug y token al crear el tipo.
     *
     * El slug lleva un sufijo aleatorio de 5 caracteres para evitar colisiones
     * entre tipos con el mismo nombre. El token de 60 caracteres autentica el
     * webhook público de recepción de respuestas.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function ($type) {
            $type->slug  = Str::slug($type->nombre) . '-' . Str::random(5);
            $type->token = Str::random(60);
        });
    }

    /**
     * Relación: el tipo tiene muchas respuestas de encuesta.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Survey>
     */
    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    /**
     * Genera el snippet cURL de ejemplo para integrar el webhook de este tipo.
     *
     * Construye un comando listo para copiar con la URL del webhook (incluye el
     * token) y el cuerpo JSON con los campos definidos como placeholders.
     *
     * @return string Comando cURL multilínea.
     */
    public function snippet(): string
    {
        $webhook = url("/api/surveys/{$this->token}");

        $campos = collect($this->campos)
            ->map(fn($c) => "    \"{$c}\": \"{{{$c}}}\"")
            ->implode(",\n");

        return <<<CURL
    curl -X POST "{$webhook}" \\
    -H "Authorization: Bearer {API_TOKEN}" \\
    -H "Content-Type: application/json" \\
    -d '{
    {$campos}
    }'
    CURL;
    }
}
