<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GlpiService;
use Illuminate\Http\Request;
use Exception;

class GlpiController extends Controller
{
    public function __construct(protected GlpiService $glpi) {}

    /**
     * Inicia sesión en GLPI manualmente.
     */
    public function sessionInit()
    {
        try {
            $token = $this->glpi->initSession();
            session(['glpi_session_active' => true]);
            return back()->with('success', 'Sesión GLPI iniciada correctamente. Token: ' . substr($token, 0, 8) . '...');
        } catch (Exception $e) {
            return back()->with('error', 'No se pudo iniciar sesión en GLPI: ' . $e->getMessage());
        }
    }

    /**
     * Cierra la sesión activa en GLPI.
     */
    public function sessionKill()
    {
        try {
            $this->glpi->killSession();
            session()->forget('glpi_session_active');
            return back()->with('success', 'Sesión GLPI cerrada correctamente.');
        } catch (Exception $e) {
            session()->forget('glpi_session_active');
            return back()->with('error', 'Error al cerrar sesión: ' . $e->getMessage());
        }
    }

    /**
     * Dashboard principal del módulo GLPI.
     * Muestra resumen de todos los tipos de activos.
     */
    public function index()
    {
        $assetTypes = config('glpi.asset_types');
        $summary    = [];

        foreach ($assetTypes as $type => $label) {
            try {
                $result = $this->glpi->getAllItems($type, ['range' => '0-0']);
                $summary[$type] = [
                    'label' => $label,
                    'total' => $result['total'],
                ];
            } catch (Exception) {
                $summary[$type] = ['label' => $label, 'total' => 0];
            }
        }

        return view('admin.glpi.index', compact('summary'));
    }

    /**
     * Lista de items de un tipo específico con paginación y búsqueda.
     */
    public function items(Request $request, string $itemtype)
    {
        $assetTypes = config('glpi.asset_types');
        abort_unless(array_key_exists($itemtype, $assetTypes), 404);

        $search  = $request->get('search', '');
        $page    = max(1, (int) $request->get('page', 1));
        $perPage = 20;
        $start   = ($page - 1) * $perPage;

        try {
            if ($search) {
                $result = $this->glpi->searchItems($itemtype, [
                    ['field' => 1, 'searchtype' => 'contains', 'value' => $search],
                ], ['range' => "{$start}-" . ($start + $perPage - 1)]);
            } else {
                $result = $this->glpi->getAllItems($itemtype, [
                    'range' => "{$start}-" . ($start + $perPage - 1),
                ]);
            }

            $items = $result['items'];
            $total = $result['total'];
        } catch (Exception $e) {
            $items = [];
            $total = 0;
            session()->flash('error', 'Error al conectar con GLPI: ' . $e->getMessage());
        }

        $totalPages = $total > 0 ? ceil($total / $perPage) : 1;
        $label      = $assetTypes[$itemtype];

        return view('admin.glpi.items', compact(
            'items', 'total', 'itemtype', 'label',
            'search', 'page', 'totalPages', 'perPage'
        ));
    }

    /**
     * Detalle de un activo específico.
     */
    public function show(string $itemtype, int $id)
    {
        $assetTypes = config('glpi.asset_types');
        abort_unless(array_key_exists($itemtype, $assetTypes), 404);

        try {
            $item  = $this->glpi->getItem($itemtype, $id);
            $label = $assetTypes[$itemtype];
        } catch (Exception $e) {
            abort(404, 'Activo no encontrado: ' . $e->getMessage());
        }

        return view('admin.glpi.show', compact('item', 'itemtype', 'label'));
    }

    /**
     * Formulario para crear un nuevo activo.
     */
    public function create(string $itemtype)
    {
        $assetTypes = config('glpi.asset_types');
        abort_unless(array_key_exists($itemtype, $assetTypes), 404);

        $label = $assetTypes[$itemtype];

        try {
            $entities = $this->glpi->getEntities();
        } catch (Exception) {
            $entities = [];
        }

        return view('admin.glpi.create', compact('itemtype', 'label', 'entities'));
    }

    /**
     * Guarda un nuevo activo en GLPI.
     */
    public function store(Request $request, string $itemtype)
    {
        $assetTypes = config('glpi.asset_types');
        abort_unless(array_key_exists($itemtype, $assetTypes), 404);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'serial'      => 'nullable|string|max:255',
            'otherserial' => 'nullable|string|max:255',
            'comment'     => 'nullable|string',
            'entities_id' => 'nullable|integer',
        ]);

        try {
            $this->glpi->addItem($itemtype, array_filter($validated));
            return redirect()
                ->route('admin.glpi.items', $itemtype)
                ->with('success', 'Activo creado correctamente en GLPI.');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Error al crear el activo: ' . $e->getMessage());
        }
    }

    /**
     * Formulario de edición.
     */
    public function edit(string $itemtype, int $id)
    {
        $assetTypes = config('glpi.asset_types');
        abort_unless(array_key_exists($itemtype, $assetTypes), 404);

        try {
            $item     = $this->glpi->getItem($itemtype, $id);
            $label    = $assetTypes[$itemtype];
            $entities = $this->glpi->getEntities();
        } catch (Exception $e) {
            abort(404, $e->getMessage());
        }

        return view('admin.glpi.edit', compact('item', 'itemtype', 'label', 'entities'));
    }

    /**
     * Actualiza un activo en GLPI.
     */
    public function update(Request $request, string $itemtype, int $id)
    {
        $assetTypes = config('glpi.asset_types');
        abort_unless(array_key_exists($itemtype, $assetTypes), 404);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'serial'      => 'nullable|string|max:255',
            'otherserial' => 'nullable|string|max:255',
            'comment'     => 'nullable|string',
            'entities_id' => 'nullable|integer',
        ]);

        try {
            $this->glpi->updateItem($itemtype, $id, array_filter($validated));
            return redirect()
                ->route('admin.glpi.show', [$itemtype, $id])
                ->with('success', 'Activo actualizado correctamente.');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }
}