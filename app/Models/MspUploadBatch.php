<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MspUploadBatch extends Model
{
    protected $fillable = [
        'filename', 'periodo', 'total_registros', 'clientes_unicos',
    ];
}
