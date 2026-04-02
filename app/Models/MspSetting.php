<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MspSetting extends Model
{
    protected $fillable = ['key', 'value', 'label', 'is_secret'];

    protected $casts = ['is_secret' => 'boolean'];

    // ─── Obtener un valor por clave ───────────────────────────────────────────
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("msp_setting_{$key}", 3600, function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    // ─── Guardar un valor ─────────────────────────────────────────────────────
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("msp_setting_{$key}");
    }

    // ─── Obtener todos como array key => value ────────────────────────────────
    public static function allAsArray(): array
    {
        return static::all()->pluck('value', 'key')->toArray();
    }
}
