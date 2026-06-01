<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

/**
 * Controlador del módulo de Usuarios y Roles.
 *
 * Gestiona el CRUD completo de usuarios del sistema, incluyendo asignación de roles,
 * filtros de búsqueda y cambio seguro de contraseñas.
 *
 * Restricción de seguridad: un usuario no puede eliminarse a sí mismo.
 * Las contraseñas se almacenan siempre como hash bcrypt mediante Laravel\Hash.
 *
 * Vistas:
 *   - admin.users.index  → Listado paginado con filtros
 *   - admin.users.create → Formulario de creación
 *   - admin.users.edit   → Formulario de edición
 *   - admin.users.show   → Detalle del usuario (incluye opción cambiar contraseña)
 *
 * Rutas principales (prefijo /admin/users, Resource controller):
 *   GET    /               → index()
 *   GET    /create         → create()
 *   POST   /               → store()
 *   GET    /{user}         → show()
 *   GET    /{user}/edit    → edit()
 *   PUT    /{user}         → update()
 *   DELETE /{user}         → destroy()
 *   POST   /{user}/password → changePassword()
 */
class UserController extends Controller
{
    /**
     * Listado paginado de usuarios con filtros de búsqueda y rol.
     *
     * Filtros disponibles:
     *  - `search`: filtra por nombre o email (LIKE insensible a mayúsculas).
     *  - `role_id`: filtra por rol asignado.
     *
     * Carga el rol relacionado con eager loading (with('roleModel')) para evitar
     * el problema N+1 al mostrar el nombre del rol en la tabla.
     *
     * @param  \Illuminate\Http\Request  $request  Parámetros opcionales: search, role_id
     * @return \Illuminate\View\View               Vista admin.users.index con: users (paginado), roles
     */
    public function index(Request $request)
    {
        $roles = Role::orderBy('nombre')->get(); // ← esta línea faltaba

        $users = User::with('roleModel')
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
            )
            ->when($request->role_id, fn($q) =>
                $q->where('role_id', $request->role_id)
            )
            ->latest()
            ->paginate(10);

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Formulario de creación de nuevo usuario.
     *
     * @return \Illuminate\View\View  Vista admin.users.create con: roles (lista completa)
     */
    public function create()
    {
        $roles = Role::orderBy('nombre')->get();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Persiste un nuevo usuario en la base de datos.
     *
     * Validaciones:
     *  - Email único en la tabla users.
     *  - Contraseña confirmada (password_confirmation) y con las reglas de complejidad
     *    definidas en Rules\Password::defaults() (configuradas en AppServiceProvider).
     *  - role_id debe existir en la tabla roles.
     *
     * La contraseña se almacena como hash bcrypt, nunca en texto plano.
     *
     * @param  \Illuminate\Http\Request  $request  Campos: name, email, role_id, password, password_confirmation
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users'],
            'role_id'  => ['required', 'exists:roles,id'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'role_id'  => $request->role_id,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.users.index')
                         ->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Muestra el perfil de un usuario específico.
     *
     * @param  \App\Models\User  $user  Usuario resuelto por model binding
     * @return \Illuminate\View\View    Vista admin.users.show con: user
     */
    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    /**
     * Formulario de edición de un usuario existente.
     *
     * @param  \App\Models\User  $user  Usuario resuelto por model binding
     * @return \Illuminate\View\View    Vista admin.users.edit con: user, roles
     */
    public function edit(User $user)
    {
        $roles = Role::orderBy('nombre')->get();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Actualiza nombre, email y rol de un usuario existente.
     *
     * La validación de email usa la regla `unique:users,email,{id}` para permitir
     * que el usuario conserve su propio email sin que falle la validación de unicidad.
     * La contraseña NO se cambia en este método; usar changePassword() para eso.
     *
     * @param  \Illuminate\Http\Request  $request  Campos: name, email, role_id
     * @param  \App\Models\User          $user     Usuario a actualizar (model binding)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'unique:users,email,' . $user->id],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $user->update([
            'name'    => $request->name,
            'email'   => $request->email,
            'role_id' => $request->role_id,
        ]);

        return redirect()->route('admin.users.index')
                         ->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Elimina un usuario del sistema.
     *
     * Protección: impide que el usuario autenticado se elimine a sí mismo,
     * lo que dejaría la sesión activa sin un usuario válido en la base de datos.
     *
     * @param  \App\Models\User  $user  Usuario a eliminar (model binding)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminarte a ti mismo.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
                         ->with('success', 'Usuario eliminado correctamente.');
    }

    /**
     * Cambia la contraseña de un usuario de forma segura.
     *
     * Requiere confirmación de contraseña (password_confirmation) y aplica las reglas
     * de complejidad por defecto de Laravel. La nueva contraseña se almacena como hash
     * bcrypt sin exponer la anterior. No requiere conocer la contraseña actual.
     *
     * @param  \Illuminate\Http\Request  $request  Campos: password, password_confirmation
     * @param  \App\Models\User          $user     Usuario cuya contraseña se cambia (model binding)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changePassword(Request $request, User $user)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.users.show', $user)
                         ->with('success', 'Contraseña actualizada correctamente.');
    }
}