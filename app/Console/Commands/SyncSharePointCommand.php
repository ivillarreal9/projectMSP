<?php

// app/Console/Commands/SyncSharePointCommand.php

namespace App\Console\Commands;

use App\Jobs\SyncSharePointExcelJob;
use Illuminate\Console\Command;

class SyncSharePointCommand extends Command
{
    protected $signature   = 'msp:sync-sharepoint {--periodo= : Período a sincronizar (ej: "Enero 2026")} {--force : Forzar aunque no haya cambios}';
    protected $description = 'Sincroniza el Excel de SharePoint con la base de datos MSP';

    public function handle(): void
    {
        $periodo = $this->option('periodo') ?: now()->translatedFormat('F Y');
        $forzar  = $this->option('force') ?? false;

        $this->info("🔄 Sincronizando SharePoint para período: {$periodo}");

        SyncSharePointExcelJob::dispatch($periodo, $forzar);

        $this->info('✅ Job despachado correctamente. Revisa los logs para el resultado.');
    }
}
