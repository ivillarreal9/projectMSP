<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    const MODULOS = [
        'msp_reports' => [
            'nombre' => 'MSP Reports',
            'descripcion' => 'Reportes y correos',
            'color' => 'orange',
            'icon' => 'chart-bar',
        ],
        'api_msp' => [
            'nombre' => 'API MSP',
            'descripcion' => 'Consulta de la API',
            'color' => 'purple',
            'icon' => 'code',
        ],
        'meta2' => [
            'nombre' => 'META 2',
            'descripcion' => 'Metas y objetivos',
            'color' => 'green',
            'icon' => 'lightning-bolt',
        ],
        'encuestas' => [
            'nombre' => 'Encuestas',
            'descripcion' => 'Satisfacción clientes',
            'color' => 'blue',
            'icon' => 'clipboard-list',
        ],
        'usuarios' => [
            'nombre' => 'Usuarios',
            'descripcion' => 'Gestión de accesos',
            'color' => 'pink',
            'icon' => 'users',
        ],
        'glpi' => [
            'nombre'      => 'GLPI',
            'descripcion' => 'Inventario de activos',
            'color'       => 'cyan',
            'icon'        => 'server',
        ],
        'sales' => [
            'nombre' => 'Sales',
            'descripcion' => 'Dashboard de ventas',
            'color' => 'teal',
            'icon' => 'trending-up',
        ],
    ];

    public function index()
    {
        $roles = Role::withCount('users')->orderBy('nombre')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'    => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'modulos'   => 'nullable|array',
            'modulos.*' => 'string|in:' . implode(',', array_keys(self::MODULOS)),
        ]);

        Role::create([
            'nombre'      => $request->nombre,
            'slug'        => Str::slug($request->nombre),
            'descripcion' => $request->descripcion,
            'modulos'     => $request->modulos ?? [],
        ]);

        return back()->with('success', '✅ Rol creado correctamente.');
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'nombre'      => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'modulos'     => 'nullable|array',
            'modulos.*'   => 'string|in:' . implode(',', array_keys(self::MODULOS)),
        ]);

        $role->update([
            'nombre'      => $request->nombre,
            'slug'        => Str::slug($request->nombre),
            'descripcion' => $request->descripcion,
            'modulos'     => $request->modulos ?? [],
        ]);

        return back()->with('success', '✅ Rol actualizado correctamente.');
    }

    public function destroy(Role $role)
    {
        if ($role->users()->count() > 0) {
            return back()->with('error', '❌ No puedes eliminar un rol con usuarios asignados.');
        }

        $role->delete();
        return back()->with('success', '✅ Rol eliminado correctamente.');
    }

    public function modulosDisponibles(): array
    {
        return self::MODULOS;
    }
}