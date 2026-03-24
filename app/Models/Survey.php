<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Survey extends Model
{
        protected $fillable = [
        'fecha',
        'numero_whatsapp',
        'nombre',
        'satisfaccion',
        'recomendacion',
    ];
}
