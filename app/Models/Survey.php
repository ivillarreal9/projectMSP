<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo que representa una respuesta de encuesta recibida vía webhook.
 *
 * Cada registro llega por el endpoint público `POST /api/surveys/{token}` y se
 * asocia a un {@see \App\Models\SurveyType} que define los campos esperados.
 * Las respuestas dinámicas se guardan en la columna JSON `data`.
 *
 * Nota: `fecha` es string (no cast a date) porque los webhooks externos envían
 * formatos arbitrarios — las vistas la parsean con rescue() como salvaguarda.
 *
 * Tabla: surveys
 *
 * Relaciones:
 * - Pertenece a {@see \App\Models\SurveyType} mediante `survey_type_id`.
 *
 * @property int         $id
 * @property int         $survey_type_id  FK hacia survey_types.
 * @property string|null $fecha           Fecha reportada por el webhook (formato libre).
 * @property string|null $numero_whatsapp Número de WhatsApp del encuestado.
 * @property string|null $nombre          Nombre del encuestado.
 * @property array|null  $data            Respuestas dinámicas {"campo": "valor"}.
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Survey extends Model
{
    /**
     * Campos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'survey_type_id',
        'fecha',
        'numero_whatsapp',
        'nombre',
        'data',
    ];

    /**
     * Conversiones automáticas de atributos.
     *
     * - `data`: la columna JSON se deserializa automáticamente como array PHP.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Relación: la respuesta pertenece a un tipo de encuesta.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<SurveyType, Survey>
     */
    public function surveyType(): BelongsTo
    {
        return $this->belongsTo(SurveyType::class);
    }

    /**
     * Accede a un campo dinámico de la respuesta de forma segura.
     *
     * @param  string $key Nombre del campo (clave dentro de `data`).
     * @return mixed       Valor del campo o null si no existe.
     */
    public function field(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }
}
