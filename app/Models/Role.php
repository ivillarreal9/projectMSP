<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

/**
 * Modelo que representa un rol de acceso en el sistema.
 *
 * Implementa un sistema de RBAC (control de acceso basado en roles) dinámico:
 * cada rol define un listado de módulos accesibles almacenado como array JSON
 * en la columna `modulos`. Los usuarios con rol "admin" tienen acceso irrestricto
 * independientemente de este listado.
 *
 * El slug se genera automáticamente a partir del nombre si no se proporciona
 * explícitamente al crear el rol.
 *
 * Tabla: roles
 *
 * Relaciones:
 * - Tiene muchos {@see \App\Models\User} mediante `role_id`.
 *
 * @property int         $id
 * @property string      $nombre      Nombre legible del rol (ej. "Administrador", "Editor").
 * @property string      $slug        Identificador URL-amigable del rol (ej. "admin", "editor").
 * @property string|null $descripcion Descripción opcional del propósito del rol.
 * @property array       $modulos     Array de slugs de módulos permitidos (ej. ["msp_reports", "ventas"]).
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Role extends Model
{
    use HasFactory;

    /**
     * Campos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = ['nombre', 'slug', 'descripcion', 'modulos'];

    /**
     * Conversiones automáticas de atributos.
     *
     * - `modulos`: la columna JSON se deserializa automáticamente como array PHP.
     *
     * @var array<string, string>
     */
    protected $casts = ['modulos' => 'array'];

    /**
     * Relación: el rol tiene muchos usuarios asignados.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<User>
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Verifica si este rol tiene acceso a un módulo específico.
     *
     * Comprueba si el slug del módulo está presente en el array `modulos`.
     * Se usa en {@see \App\Models\User::canAccessModule()} para evaluar permisos
     * de usuarios no-admin.
     *
     * @param  string $slug Slug del módulo a verificar (ej. "msp_reports", "glpi").
     * @return bool         True si el módulo está en la lista de acceso del rol.
     */
    public function hasModule(string $slug): bool
    {
        return in_array($slug, $this->modulos ?? []);
    }

    /**
     * Hook del ciclo de vida Eloquent: auto-genera el slug al crear el rol.
     *
     * Si no se proporciona un slug explícito, se genera automáticamente
     * a partir del campo `nombre` usando {@see \Illuminate\Support\Str::slug()}.
     * Ejemplo: "Super Administrador" → "super-administrador".
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function ($role) {
            if (empty($role->slug)) {
                $role->slug = Str::slug($role->nombre);
            }
        });
    }
}