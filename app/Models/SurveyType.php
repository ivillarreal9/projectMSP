<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SurveyType extends Model
{
    protected $fillable = [
        'nombre',
        'slug',
        'campos',
        'token',
        'activo',
    ];

    protected $casts = [
        'campos' => 'array',
        'activo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($type) {
            $type->slug  = Str::slug($type->nombre) . '-' . Str::random(5);
            $type->token = Str::random(60);
        });
    }

    public function surveys()
    {
        return $this->hasMany(Survey::class);
    }

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