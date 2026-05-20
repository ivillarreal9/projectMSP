<?php

namespace App\Console\Commands;

use App\Services\MerakiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MerakiWarmCacheCommand extends Command
{
    protected $signature   = 'meraki:warm-cache';
    protected $description = 'Pre-carga el caché de dispositivos y licencias Meraki para evitar timeouts en la UI';

    public function handle(MerakiService $meraki): int
    {
        $this->info('Iniciando pre-carga de caché Meraki...');

        try {
            $meraki->warmCache();
            $this->info('Caché Meraki actualizado correctamente.');
            Log::info('meraki:warm-cache completado correctamente.');
        } catch (\Throwable $e) {
            $this->error('Error al pre-cargar caché Meraki: ' . $e->getMessage());
            Log::error('meraki:warm-cache falló: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
