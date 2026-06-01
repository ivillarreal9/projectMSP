<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo de usuario autenticable del sistema ProjectMSP.
 *
 * Gestiona autenticación con soporte para 2FA (Google Authenticator),
 * inicio de sesión vía Microsoft SSO y un sistema de roles dinámico
 * basado en la tabla `roles`. Los permisos de acceso a módulos se derivan
 * del rol asignado; el rol "admin" tiene acceso irrestricto.
 *
 * Tabla: users
 *
 * Traits:
 * - HasApiTokens: permite generar tokens Sanctum para la API móvil.
 * - Notifiable: habilita el envío de notificaciones Laravel.
 *
 * Relaciones:
 * - Pertenece a {@see \App\Models\Role} mediante `role_id`.
 *
 * @property int         $id
 * @property string      $name                  Nombre completo del usuario.
 * @property string      $email                 Correo electrónico (único, usado para login y SSO).
 * @property string      $password              Hash bcrypt de la contraseña (oculto en serialización).
 * @property int|null    $role_id               FK hacia la tabla roles.
 * @property string|null $role                  Slug del rol (columna redundante, sincronizada via booted).
 * @property int|null    $odoo_user_id          ID del usuario en Odoo (para filtrado de comisiones).
 * @property string|null $two_factor_secret     Secreto TOTP para Google Authenticator.
 * @property bool        $two_factor_confirmed  Indica si el usuario completó el registro 2FA.
 * @property string|null $remember_token        Token de "recordarme" (oculto en serialización).
 * @property \Carbon\Carbon|null $email_verified_at Fecha de verificación de email.
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Campos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'odoo_user_id',
        'two_factor_secret',
        'two_factor_confirmed',
    ];

    /**
     * Atributos excluidos de la serialización JSON/array.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversiones automáticas de atributos.
     *
     * - `email_verified_at`: instancia Carbon.
     * - `password`: se hashea automáticamente al asignar.
     * - `two_factor_confirmed`: se trata como booleano.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'two_factor_confirmed' => 'boolean',
        ];
    }

    // ── Relación con rol dinámico ─────────────────────────────────────────

    /**
     * Relación: el usuario pertenece a un rol dinámico.
     *
     * El método se llama `roleModel` (y no `role`) para evitar conflicto con
     * la columna `role` que almacena el slug redundante en la tabla `users`.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Role, User>
     */
    public function roleModel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // ── Verificar acceso a módulo dinámico ───────────────────────────────

    /**
     * Verifica si el usuario puede acceder a un módulo por su slug.
     *
     * Los administradores siempre tienen acceso. Para otros roles, se
     * consulta el array `modulos` del rol asociado.
     *
     * @param  string $slug Slug del módulo (ej. "msp_reports", "ventas").
     * @return bool         True si el usuario tiene permiso de acceso.
     */
    public function canAccessModule(string $slug): bool
    {
        if ($this->isAdmin()) return true;

        $this->loadMissing('roleModel');

        return $this->roleModel?->hasModule($slug) ?? false;
    }

    // ── Métodos de rol (basados en relación dinámica) ────────────────────

    /**
     * Verifica si el usuario tiene el rol "admin".
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->roleModel?->slug === 'admin';
    }

    /**
     * Verifica si el usuario tiene el rol "editor".
     *
     * @return bool
     */
    public function isEditor(): bool
    {
        return $this->roleModel?->slug === 'editor';
    }

    /**
     * Verifica si el usuario tiene el rol "ventas".
     *
     * @return bool
     */
    public function isVentas(): bool
    {
        return $this->roleModel?->slug === 'ventas';
    }

    /**
     * Verifica si el usuario tiene un rol específico por su slug.
     *
     * @param  string $role Slug del rol a comprobar (ej. "editor", "ventas").
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return $this->roleModel?->slug === $role;
    }

    /**
     * Verifica si el usuario puede ver las comisiones de un vendedor Odoo.
     *
     * Devuelve true si el usuario es administrador o si su `odoo_user_id`
     * coincide con el ID del vendedor solicitado (el vendedor ve solo las suyas).
     *
     * @param  int  $odooUserId ID del vendedor en Odoo.
     * @return bool
     */
    public function canViewVendedorCommissions(int $odooUserId): bool
    {
        return $this->isAdmin() || (int) $this->odoo_user_id === $odooUserId;
    }

    // ── Módulos accesibles según rol dinámico ────────────────────────────

    /**
     * Devuelve la lista de slugs de módulos accesibles para el usuario.
     *
     * Carga el rol si no estaba previamente cargado y retorna el array
     * `modulos` del rol. Retorna un array vacío si el usuario no tiene rol.
     *
     * @return array<int, string> Lista de slugs de módulos (ej. ["msp_reports", "ventas"]).
     */
    public function modulosAccesibles(): array
    {
        $this->loadMissing('roleModel');
        return $this->roleModel?->modulos ?? [];
    }

    /**
     * Hook del ciclo de vida Eloquent: sincroniza la columna `role` con el slug del rol.
     *
     * Cada vez que se guarda el usuario y el campo `role_id` ha cambiado,
     * se actualiza automáticamente la columna redundante `role` con el slug
     * correspondiente. Esto mantiene compatibilidad con código legado que
     * consulta `$user->role` directamente.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::saving(function ($user) {
            if ($user->isDirty('role_id')) {
                if ($user->role_id) {
                    $role = \App\Models\Role::find($user->role_id);
                    $user->role = $role?->slug;
                } else {
                    $user->role = null;
                }
            }
        });
    }
}