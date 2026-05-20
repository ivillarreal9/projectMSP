<?php

namespace App\Console\Commands;

use App\Services\GlpiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GlpiWarmCacheCommand extends Command
{
    protected $signature   = 'glpi:warm-cache';
    protected $description = 'Pre-carga el caché de activos GLPI para evitar timeouts en la UI';

    public function handle(GlpiService $glpi): int
    {
        $this->info('Iniciando pre-carga de caché GLPI...');

        try {
            $glpi->warmCache();
            $this->info('Caché GLPI actualizado correctamente.');
            Log::info('glpi:warm-cache completado correctamente.');
        } catch (\Throwable $e) {
            $this->error('Error al pre-cargar caché GLPI: ' . $e->getMessage());
            Log::error('glpi:warm-cache falló: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
