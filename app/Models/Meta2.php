<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meta2 extends Model
{
    protected $table = 'meta2';

    protected $fillable = [
        'nombre',
        'descripcion',
        'valor',
        'estado',
    ];
}
