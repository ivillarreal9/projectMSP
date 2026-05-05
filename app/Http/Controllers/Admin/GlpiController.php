<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GlpiService;
use Illuminate\Http\Request;
use Exception;

class GlpiController extends Controller
{
    public function __construct(protected GlpiService $glpi) {}

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

    public function index()
    {
        $assetTypes = config('glpi.asset_types');
        $summary    = [];

        foreach ($assetTypes as $type => $label) {
            try {
                $result = $this->glpi->getAllItems($type, ['range' => '0-1']);
                $summary[$type] = ['label' => $label, 'total' => $result['total']];
            } catch (Exception) {
                $summary[$type] = ['label' => $label, 'total' => 0];
            }
        }

        return view('admin.glpi.index', compact('summary'));
    }

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

    public function create(string $itemtype)
    {
        $assetTypes = config('glpi.asset_types');
        abort_unless(array_key_exists($itemtype, $assetTypes), 404);
        $label = $assetTypes[$itemtype];
        try { $entities = $this->glpi->getMyEntities(); } catch (Exception) { $entities = []; }
        return view('admin.glpi.create', compact('itemtype', 'label', 'entities'));
    }

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
            return redirect()->route('admin.glpi.items', $itemtype)->with('success', 'Activo creado correctamente.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Error al crear: ' . $e->getMessage());
        }
    }

    public function edit(string $itemtype, int $id)
    {
        $assetTypes = config('glpi.asset_types');
        abort_unless(array_key_exists($itemtype, $assetTypes), 404);
        try {
            $item     = $this->glpi->getItem($itemtype, $id);
            $label    = $assetTypes[$itemtype];
            $entities = $this->glpi->getMyEntities();
        } catch (Exception $e) { abort(404, $e->getMessage()); }
        return view('admin.glpi.edit', compact('item', 'itemtype', 'label', 'entities'));
    }

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
            return redirect()->route('admin.glpi.show', [$itemtype, $id])->with('success', 'Activo actualizado.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    public function items(Request $request, string $itemtype)
    {
        $assetTypes = config('glpi.asset_types');
        abort_unless(array_key_exists($itemtype, $assetTypes), 404);

        $search = $request->get('search', '');
        $label  = $assetTypes[$itemtype];

        $typeField = match($itemtype) {
            'NetworkEquipment' => 'networkequipmenttypes_id',
            'Computer'         => 'computertypes_id',
            'Printer'          => 'printertypes_id',
            'Phone'            => 'phonetypes_id',
            'Monitor'          => 'monitortypes_id',
            'Peripheral'       => 'peripheraltypes_id',
            default            => null,
        };

        $modelField = match($itemtype) {
            'NetworkEquipment' => 'networkequipmentmodels_id',
            'Computer'         => 'computermodels_id',
            'Printer'          => 'printermodels_id',
            'Phone'            => 'phonemodels_id',
            'Monitor'          => 'monitormodels_id',
            'Peripheral'       => 'peripheralmodels_id',
            default            => null,
        };

        try {
            $all      = $this->glpi->getAllItems($itemtype, [
                'range'            => '0-499',
                'expand_dropdowns' => true,
                'get_hateoas'      => false,
            ]);

            $allItems = $all['items'] ?? [];
            $total    = $all['total'] ?? count($allItems);

            if ($search) {
                $allItems = array_values(array_filter($allItems, fn($i) =>
                    str_contains(strtolower($i['name'] ?? ''), strtolower($search))
                ));
            }

            // Agrupar: tipo → modelo → { total, deposito, items[] }
            $grouped = [];

            foreach ($allItems as $item) {
                $tipo = '';
                if ($typeField && isset($item[$typeField])) {
                    $v    = $item[$typeField];
                    $tipo = is_array($v) ? ($v['name'] ?? '') : ($v ?: '');
                }
                $tipo = $tipo ?: 'Sin tipo';

                $modelo = '';
                if ($modelField && isset($item[$modelField])) {
                    $v      = $item[$modelField];
                    $modelo = is_array($v) ? ($v['name'] ?? '') : ($v ?: '');
                }
                $modelo = $modelo ?: 'Sin modelo';

                if (!isset($grouped[$tipo][$modelo])) {
                    $grouped[$tipo][$modelo] = ['total' => 0, 'deposito' => 0, 'items' => []];
                }

                $grouped[$tipo][$modelo]['total']++;
                $grouped[$tipo][$modelo]['items'][] = $item;

                $estado       = $item['states_id'] ?? null;
                $estadoNombre = strtolower(is_array($estado) ? ($estado['name'] ?? '') : ($estado ?? ''));
                if (str_contains($estadoNombre, 'dep')) {
                    $grouped[$tipo][$modelo]['deposito']++;
                }
            }

            // Ordenar por total desc
            foreach ($grouped as $tipo => &$modelos) {
                uasort($modelos, fn($a, $b) => $b['total'] - $a['total']);
            }
            uasort($grouped, fn($a, $b) =>
                array_sum(array_column($b, 'total')) - array_sum(array_column($a, 'total'))
            );

        } catch (Exception $e) {
            $grouped = [];
            $total   = 0;
            session()->flash('error', 'Error al conectar con GLPI: ' . $e->getMessage());
        }

        return view('admin.glpi.items', compact(
            'grouped', 'total', 'itemtype', 'label', 'search'
        ));
    }
}