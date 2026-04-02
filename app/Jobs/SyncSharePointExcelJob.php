<?php

// ─────────────────────────────────────────────────────────────
// app/Jobs/SyncSharePointExcelJob.php
// ─────────────────────────────────────────────────────────────

namespace App\Jobs;

use App\Imports\MspReportsImport;
use App\Models\MspReport;
use App\Models\MspUploadBatch;
use App\Services\SharePointService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class SyncSharePointExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 3;

    private string $periodo;
    private bool   $forzar;

    public function __construct(string $periodo = '', bool $forzar = false)
    {
        $this->periodo = $periodo ?: Carbon::now()->translatedFormat('F Y');
        $this->forzar  = $forzar;
    }

    public function handle(SharePointService $sharePoint): void
    {
        Log::info("SyncSharePointExcelJob iniciado", ['periodo' => $this->periodo]);

        try {
            // 1. Descargar Excel desde SharePoint
            $localPath = $sharePoint->downloadExcel();

            // 2. Marcar data existente como inactiva (histórico)
            MspReport::where('periodo', $this->periodo)
                     ->where('activo', true)
                     ->update(['activo' => false]);

            // 3. Crear batch
            $batch = MspUploadBatch::create([
                'filename'        => 'SharePoint: ' . config('services.sharepoint.file'),
                'periodo'         => $this->periodo,
                'total_registros' => 0,
                'clientes_unicos' => 0,
                'fuente'          => 'sharepoint',
            ]);

            // 4. Importar Excel
            Excel::import(new MspReportsImport($this->periodo, $batch->id), $localPath);

            // 5. Actualizar stats del batch
            $total  = MspReport::where('periodo', $this->periodo)->where('activo', true)->count();
            $unicos = MspReport::where('periodo', $this->periodo)->where('activo', true)
                               ->distinct('customer_name')->count();

            $batch->update(['total_registros' => $total, 'clientes_unicos' => $unicos]);

            // 6. Limpiar archivo temporal
            if (file_exists($localPath)) unlink($localPath);

            Log::info("SyncSharePointExcelJob completado", [
                'periodo' => $this->periodo,
                'total'   => $total,
                'unicos'  => $unicos,
            ]);

        } catch (\Throwable $e) {
            Log::error("SyncSharePointExcelJob falló", [
                'error'   => $e->getMessage(),
                'periodo' => $this->periodo,
            ]);
            throw $e;
        }
    }
}
