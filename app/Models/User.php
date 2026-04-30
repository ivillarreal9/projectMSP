<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',      // campo string legacy (para el middleware role:admin,editor,etc)
        'role_id',   // FK al nuevo sistema dinámico
        'two_factor_secret',
        'two_factor_confirmed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'two_factor_confirmed' => 'boolean',
        ];
    }

    // ── Relación con rol dinámico ─────────────────────────────────────────
    public function roleModel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // ── Verificar acceso a módulo dinámico ───────────────────────────────
    public function canAccessModule(string $slug): bool
    {
        if ($this->isAdmin()) return true;
        return $this->roleModel?->hasModule($slug) ?? false;
    }

    // ── Métodos de rol estáticos (compatibilidad con middleware existente) ─
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEditor(): bool
    {
        return $this->role === 'editor';
    }

    public function isVentas(): bool
    {
        return $this->role === 'ventas';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    // ── Módulos accesibles según rol dinámico ────────────────────────────
    public function modulosAccesibles(): array
    {
        if ($this->isAdmin()) {
            return ['msp_reports', 'api_msp', 'meta2', 'encuestas', 'usuarios', 'sales'];
        }
        return $this->roleModel?->modulos ?? [];
    }
}