<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MspPlantilla extends Model
{
    protected $fillable = [
        'nombre',
        'asunto',
        'mensaje',
        'imagen_path',
        'es_predeterminada',
    ];

    protected $casts = [
        'es_predeterminada' => 'boolean',
    ];

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