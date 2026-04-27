<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Role extends Model
{
    use HasFactory;
    protected $fillable = ['nombre', 'slug', 'descripcion', 'modulos'];

    protected $casts = ['modulos' => 'array'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function hasModule(string $slug): bool
    {
        return in_array($slug, $this->modulos ?? []);
    }

    protected static function booted(): void
    {
        static::creating(function ($role) {
            if (empty($role->slug)) {
                $role->slug = Str::slug($role->nombre);
            }
        });
    }
}