<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\EnlacesCarrierImport;
use App\Models\EnlaceBatch;
use App\Models\EnlaceCarrier;
use App\Services\SharePointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Browsershot\Browsershot;

/**
 * Controlador del módulo Control de Enlaces Carrier.
 *
 * Importa circuitos de red desde Excel en SharePoint (sitio EnlacesInternacionales)
 * y los muestra con filtros, vista tarjetas y vista tabla.
 *
 * Configuración .env requerida:
 *   SHAREPOINT_ENLACES_SITE_URL    = https://ovnicom0.sharepoint.com/sites/EnlacesInternacionales
 *   SHAREPOINT_ENLACES_FOLDER_PATH = listado de enlaces- internacionales
 *
 * Rutas (prefijo /admin/enlaces):
 *   GET  /                      → index()
 *   POST /sharepoint/import     → sharepointImport()
 *   POST /batch/{batch}/refresh → refreshBatch()
 */
class EnlaceController extends Controller
{
    private string $siteUrl;
    private string $folderPath;

    public function __construct()
    {
        $this->siteUrl    = (string) config('services.sharepoint.enlaces_site_url', '');
        $this->folderPath = (string) config('services.sharepoint.enlaces_folder_path', '');
    }

    /**
     * Vista principal. En AJAX devuelve JSON con archivos Excel del site de enlaces.
     */
    public function index(Request $request)
    {
        $sp = app(SharePointService::class);

        if ($request->ajax() || $request->wantsJson()) {
            try {
                if ($this->siteUrl && $this->folderPath) {
                    $files = $sp->listExcelFilesFromSite($this->siteUrl, $this->folderPath);
                } else {
                    $folderId = (string) config('services.sharepoint.enlaces_folder_id', '');
                    $files    = $sp->listExcelFiles($folderId ?: null);
                }
                return response()->json(['files' => $files]);
            } catch (\Throwable $e) {
                Log::error('EnlaceController: SharePoint listFiles failed', [
                    'error' => $e->getMessage(),
                    'user'  => auth()->id(),
                ]);
                return response()->json(['error' => $e->getMessage()]);
            }
        }

        $hasCredentials = $sp->hasCredentials();
        $hasFolder      = !empty($this->siteUrl) && !empty($this->folderPath);

        $enlaces = EnlaceCarrier::with('batch')
            ->orderBy('pais')
            ->orderBy('cliente')
            ->get();

        $stats = $this->statsFor($enlaces);

        $lastBatch = EnlaceBatch::latest()->first();

        return view('admin.enlaces.index', compact(
            'enlaces', 'stats', 'hasCredentials', 'hasFolder', 'lastBatch'
        ));
    }

    /**
     * Importa el Excel seleccionado desde SharePoint y crea los registros de circuitos.
     */
    public function sharepointImport(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
        ]);

        $filename = $request->input('filename');
        $itemId   = $request->input('item_id');
        $sp       = app(SharePointService::class);
        $tempPath = null;

        try {
            if ($itemId && $this->siteUrl) {
                $tempPath = $sp->downloadFileByIdFromSite($itemId, $filename, $this->siteUrl);
            } elseif ($itemId) {
                $tempPath = $sp->downloadFileById($itemId, $filename);
            } else {
                $folderId = (string) config('services.sharepoint.enlaces_folder_id', '');
                $tempPath = $sp->downloadFileByName($filename, $folderId ?: null);
            }

            $batch = EnlaceBatch::create([
                'filename'           => $filename . ' (SharePoint)',
                'sharepoint_item_id' => $itemId,
            ]);

            Excel::import(new EnlacesCarrierImport($batch->id, $this->sheetNames($tempPath)), $tempPath);

            $total = EnlaceCarrier::where('batch_id', $batch->id)->count();
            $batch->update(['total_registros' => $total]);

            return redirect()->route('admin.enlaces.index')
                ->with('success', "Importación completada: {$total} circuitos desde \"{$filename}\".");

        } catch (\Throwable $e) {
            Log::error('EnlaceController: importación fallida', [
                'filename' => $filename,
                'error'    => $e->getMessage(),
            ]);
            return redirect()->route('admin.enlaces.index')
                ->with('error', 'Error al importar: ' . $e->getMessage());
        } finally {
            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Re-importa un batch eliminando sus registros previos.
     * Solo disponible si tiene sharepoint_item_id y fue creado hace ≤ 7 días.
     */
    public function refreshBatch(Request $request, EnlaceBatch $batch)
    {
        abort_unless($batch->sharepoint_item_id, 403, 'Este batch no tiene referencia de SharePoint.');

        $dias = (int) $batch->created_at->diffInDays(now());
        abort_if($dias > 7, 403, 'Solo se pueden refrescar batches de los últimos 7 días.');

        $sp       = app(SharePointService::class);
        $tempPath = null;

        try {
            $filename = $batch->filename;

            if ($this->siteUrl) {
                $tempPath = $sp->downloadFileByIdFromSite($batch->sharepoint_item_id, $filename, $this->siteUrl);
            } else {
                $tempPath = $sp->downloadFileById($batch->sharepoint_item_id, $filename);
            }

            EnlaceCarrier::where('batch_id', $batch->id)->delete();
            Excel::import(new EnlacesCarrierImport($batch->id, $this->sheetNames($tempPath)), $tempPath);

            $total = EnlaceCarrier::where('batch_id', $batch->id)->count();
            $batch->update(['total_registros' => $total]);

            return redirect()->route('admin.enlaces.index')
                ->with('success', "Batch actualizado: {$total} circuitos.");

        } catch (\Throwable $e) {
            Log::error('EnlaceController: refreshBatch fallido', [
                'batch' => $batch->id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('admin.enlaces.index')
                ->with('error', 'Error al refrescar: ' . $e->getMessage());
        } finally {
            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Auto-sync en segundo plano: lo llama la vista por AJAX al cargar la página, para
     * no bloquear el render. Throttle de 10 min para no golpear SharePoint en cada visita.
     * Devuelve JSON; el front recarga la página si hubo cambios.
     */
    public function autoSync(Request $request)
    {
        // El auto-sync solo ACTUALIZA un archivo ya importado; la primera carga es manual.
        if (!EnlaceBatch::exists()) {
            return response()->json(['status' => 'no_batch']);
        }

        if (Cache::has('enlaces_autosync_throttle')) {
            return response()->json(['status' => 'throttled']);
        }
        Cache::put('enlaces_autosync_throttle', true, now()->addMinutes(10));

        try {
            $result = $this->runSync(false);
            return response()->json($result);
        } catch (\Throwable $e) {
            Log::warning('EnlaceController: auto-sync falló', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error']);
        }
    }

    /**
     * Sincronización manual ("Sincronizar ahora"): fuerza la re-importación del
     * Excel más reciente de SharePoint aunque no haya cambiado.
     */
    public function sync(Request $request)
    {
        try {
            $result = $this->runSync(true);

            return match ($result['status']) {
                'synced'       => redirect()->route('admin.enlaces.index')
                                    ->with('success', "Actualizado: {$result['total']} circuitos desde \"{$result['file']}\"."),
                'up_to_date'   => redirect()->route('admin.enlaces.index')
                                    ->with('success', 'El archivo ya estaba al día (sin cambios).'),
                'file_missing' => redirect()->route('admin.enlaces.index')
                                    ->with('error', "El archivo importado \"{$result['file']}\" ya no está en SharePoint."),
                'no_files'     => redirect()->route('admin.enlaces.index')
                                    ->with('error', 'No se encontraron archivos Excel en la carpeta de SharePoint.'),
                'no_config'    => redirect()->route('admin.enlaces.index')
                                    ->with('error', 'Falta configurar el sitio/carpeta de SharePoint en el .env.'),
                default        => redirect()->route('admin.enlaces.index')
                                    ->with('error', 'No se pudo actualizar.'),
            };
        } catch (\Throwable $e) {
            Log::error('EnlaceController: sync manual falló', ['error' => $e->getMessage()]);
            return redirect()->route('admin.enlaces.index')
                ->with('error', 'Error al sincronizar: ' . $e->getMessage());
        }
    }

    /**
     * Descarga el Excel más reciente de la carpeta de SharePoint y lo importa (upsert).
     * Si $force es false, solo re-importa cuando el archivo cambió desde el último batch.
     *
     * @return array{status:string, file?:string, total?:int}
     */
    private function runSync(bool $force = false): array
    {
        if (!$this->siteUrl || !$this->folderPath) {
            return ['status' => 'no_config'];
        }

        $sp    = app(SharePointService::class);
        $files = $sp->listExcelFilesFromSite($this->siteUrl, $this->folderPath);

        if (empty($files)) {
            return ['status' => 'no_files'];
        }

        $lastBatch = EnlaceBatch::latest()->first();

        // Si ya hay un archivo importado, se actualiza ESE MISMO (por item_id).
        // En la primera importación se toma el más reciente de la carpeta.
        if ($lastBatch && $lastBatch->sharepoint_item_id) {
            $target = collect($files)->firstWhere('item_id', $lastBatch->sharepoint_item_id);
            if (!$target) {
                return ['status' => 'file_missing', 'file' => $lastBatch->filename];
            }
        } else {
            $target = collect($files)->sortByDesc('modified')->first();
        }

        // ¿Ya está al día? (mismo archivo y misma fecha de modificación)
        if (!$force
            && $lastBatch
            && $lastBatch->sharepoint_item_id === ($target['item_id'] ?? null)
            && $lastBatch->source_modified_at === ($target['modified'] ?? null)) {
            return ['status' => 'up_to_date', 'file' => $target['name']];
        }

        $tempPath = null;
        try {
            $tempPath = $sp->downloadFileByIdFromSite($target['item_id'], $target['name'], $this->siteUrl);

            $batch = EnlaceBatch::create([
                'filename'           => $target['name'] . ' (SharePoint)',
                'sharepoint_item_id' => $target['item_id'],
                'source_modified_at' => $target['modified'] ?? null,
            ]);

            Excel::import(new EnlacesCarrierImport($batch->id, $this->sheetNames($tempPath)), $tempPath);

            $total = EnlaceCarrier::where('batch_id', $batch->id)->count();
            $batch->update(['total_registros' => $total]);

            return ['status' => 'synced', 'file' => $target['name'], 'total' => $total];
        } finally {
            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Exporta los circuitos a un PDF con presentación por país y tarjeta por cliente.
     */
    public function exportPdf(Request $request)
    {
        $enlaces = EnlaceCarrier::orderBy('pais')->orderBy('cliente')->get();

        if ($enlaces->isEmpty()) {
            return redirect()->route('admin.enlaces.index')
                ->with('error', 'No hay circuitos para exportar.');
        }

        $stats     = $this->statsFor($enlaces);
        $grouped   = $enlaces->groupBy(fn ($e) => $e->pais ?: 'Sin país')->sortKeys();
        $lastBatch = EnlaceBatch::latest()->first();
        $logo      = $this->ovnicomLogoBase64();

        $html = view('admin.enlaces.pdf', compact('grouped', 'stats', 'lastBatch', 'logo'))->render();

        try {
            $pdf = Browsershot::html($html)
                ->setChromePath(env('BROWSERSHOT_CHROME_PATH', '/usr/bin/chromium'))
                ->setNodeBinary(env('BROWSERSHOT_NODE_PATH', '/usr/bin/node'))
                ->setNpmBinary(env('BROWSERSHOT_NPM_PATH', '/usr/bin/npm'))
                ->noSandbox()
                ->addChromiumArguments(['disable-dev-shm-usage', 'disable-gpu'])
                ->format('A4')
                ->margins(10, 8, 12, 8)
                ->showBackground()
                ->timeout(120)
                ->pdf();
        } catch (\Throwable $e) {
            Log::error('EnlaceController: error generando PDF', ['error' => $e->getMessage()]);
            return redirect()->route('admin.enlaces.index')
                ->with('error', 'No se pudo generar el PDF: ' . $e->getMessage());
        }

        $filename = 'Control-Enlaces-Carrier-' . now()->format('Y-m-d') . '.pdf';

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Calcula las estadísticas agregadas de una colección de circuitos.
     *
     * @return array{total:int, activos:int, incidentes:int, mantenimiento:int, capacidad:int, paises:array<string,int>}
     */
    private function statsFor(\Illuminate\Support\Collection $enlaces): array
    {
        return [
            'total'         => $enlaces->count(),
            'activos'       => $enlaces->where('estado', 'activo')->count(),
            'incidentes'    => $enlaces->where('estado', 'incidente')->count(),
            'mantenimiento' => $enlaces->where('estado', 'mantenimiento')->count(),
            'capacidad'     => (int) $enlaces->sum('capacidad'),
            'paises'        => $enlaces->groupBy('pais')
                ->map->count()
                ->filter()
                ->sortKeys()
                ->toArray(),
        ];
    }

    /**
     * Logo de Ovnicom en data URI (base64) para embeberlo en el PDF.
     */
    private function ovnicomLogoBase64(): ?string
    {
        $candidates = [
            storage_path('app/public/logos/ovnicom.png'),
            public_path('images/ovnicom-logo.png'),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                $mime = mime_content_type($path) ?: 'image/png';
                return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
            }
        }

        return null;
    }

    /**
     * Lista los nombres de las hojas del libro Excel sin cargar sus datos.
     * Si falla la lectura, devuelve [] (el import no procesará ninguna hoja).
     *
     * @return string[]
     */
    private function sheetNames(string $path): array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);

            return $reader->listWorksheetNames($path);
        } catch (\Throwable $e) {
            Log::warning('EnlaceController: no se pudieron leer las hojas del Excel', [
                'path'  => basename($path),
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
