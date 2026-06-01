<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Controlador del sistema de Roles dinámicos.
 *
 * Gestiona los roles de acceso que controlan qué módulos del sistema puede
 * ver cada usuario. Los roles son dinámicos: en lugar de permisos fijos,
 * cada rol almacena en su campo `modulos` (JSON) los slugs de los módulos
 * habilitados para ese rol.
 *
 * Sistema de autorización:
 *  - Cada usuario tiene un `role_id` que apunta a un registro de la tabla `roles`.
 *  - El rol tiene un array JSON `modulos` con los slugs de los módulos habilitados.
 *  - El middleware de autenticación verifica si el módulo al que intenta acceder
 *    el usuario está en el array `modulos` de su rol.
 *  - Los módulos disponibles se definen en config/modules.php (slug → nombre).
 *
 * Vista:
 *   - admin.roles.index → Listado de roles con conteo de usuarios y formularios inline
 *                         (crear, editar, eliminar en la misma página)
 *
 * Rutas principales (prefijo /admin/roles):
 *   GET    /          → index()
 *   POST   /          → store()
 *   PUT    /{role}    → update()
 *   DELETE /{role}    → destroy()
 *
 * @see config/modules.php  Definición de módulos disponibles (slug → nombre)
 */
class RoleController extends Controller
{

    /**
     * Lista todos los roles con conteo de usuarios asignados y módulos disponibles.
     *
     * Usa withCount('users') para mostrar cuántos usuarios tiene cada rol sin
     * cargar los modelos de usuario. Los módulos se pasan desde config/modules.php
     * para construir los checkboxes de selección en el formulario de edición.
     *
     * @return \Illuminate\View\View  Vista admin.roles.index con: roles (con users_count), modulos
     */
    public function index()
    {
        $roles   = Role::withCount('users')->orderBy('nombre')->get();
        $modulos = config('modules');
        return view('admin.roles.index', compact('roles', 'modulos'));
    }

    /**
     * Crea un nuevo rol con los módulos habilitados seleccionados.
     *
     * El slug se genera automáticamente a partir del nombre usando Str::slug()
     * para garantizar URLs amigables y consistentes.
     * La validación del campo `modulos.*` verifica que cada slug enviado exista
     * en config/modules.php, evitando que se asignen módulos inventados.
     * Si no se selecciona ningún módulo, se almacena un array vacío [].
     *
     * @param  \Illuminate\Http\Request  $request  Campos: nombre, descripcion (opcional), modulos[] (opcional)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre'      => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'modulos'     => 'nullable|array',
            'modulos.*'   => 'string|in:' . implode(',', array_keys(config('modules'))),
        ]);

        Role::create([
            'nombre'      => $request->nombre,
            'slug'        => Str::slug($request->nombre),
            'descripcion' => $request->descripcion,
            'modulos'     => $request->modulos ?? [],
        ]);

        return back()->with('success', '✅ Rol creado correctamente.');
    }

    /**
     * Actualiza el nombre, descripción y módulos habilitados de un rol existente.
     *
     * Regenera el slug automáticamente si el nombre cambió. Esto puede afectar
     * referencias al slug en otras partes del sistema si se usa para routing.
     * Los módulos no incluidos en el envío se eliminan del rol (el array se reemplaza completo).
     *
     * @param  \Illuminate\Http\Request  $request  Campos: nombre, descripcion (opcional), modulos[] (opcional)
     * @param  \App\Models\Role          $role     Rol a actualizar (model binding)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'nombre'      => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'modulos'     => 'nullable|array',
            'modulos.*'   => 'string|in:' . implode(',', array_keys(config('modules'))),
        ]);

        $role->update([
            'nombre'      => $request->nombre,
            'slug'        => Str::slug($request->nombre),
            'descripcion' => $request->descripcion,
            'modulos'     => $request->modulos ?? [],
        ]);

        return back()->with('success', '✅ Rol actualizado correctamente.');
    }

    /**
     * Elimina un rol del sistema.
     *
     * Protección de integridad referencial: no se permite eliminar un rol que
     * tiene usuarios asignados, ya que dejaría esos usuarios sin rol válido
     * y rompería el sistema de autorización.
     *
     * @param  \App\Models\Role  $role  Rol a eliminar (model binding)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Role $role)
    {
        if ($role->users()->count() > 0) {
            return back()->with('error', '❌ No puedes eliminar un rol con usuarios asignados.');
        }

        $role->delete();
        return back()->with('success', '✅ Rol eliminado correctamente.');
    }

    /**
     * Retorna el array de módulos disponibles desde la configuración.
     *
     * Método utilitario de conveniencia que expone el mapa slug → nombre de config/modules.php.
     * Puede ser usado por otros controladores o servicios que necesiten el listado de módulos.
     *
     * @return array  Mapa de módulos: ['slug' => 'Nombre del módulo', ...]
     */
    public function modulosDisponibles(): array
    {
        return config('modules');
    }
}