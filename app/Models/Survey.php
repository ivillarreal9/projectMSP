<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    protected $fillable = [
        'survey_type_id',
        'fecha',
        'numero_whatsapp',
        'nombre',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function surveyType()
    {
        return $this->belongsTo(SurveyType::class);
    }

    // Acceder a un campo dinámico fácilmente
    public function field(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }
}