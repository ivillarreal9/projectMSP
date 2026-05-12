<?php

namespace App\Console\Commands;

use App\Models\MspClient;
use App\Models\User;
use App\Services\Sales\OdooService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncMspClientsFromOdoo extends Command
{
    protected $signature = 'msp:sync-from-odoo
                            {--dry-run : Muestra los cambios sin aplicarlos}
                            {--chunk=100 : Tamaño de lote al llamar la API}';

    protected $description = 'Sincroniza email_cliente y numero_cuenta de clientes MSP usando datos de Odoo';

    public function handle(OdooService $odoo): int
    {
        $isDry   = $this->option('dry-run');
        $chunk   = (int) $this->option('chunk');

        $this->info('Obteniendo clientes MSP...');
        $names = MspClient::orderBy('customer_name')->pluck('customer_name')->toArray();
        $this->line('  Total en MSP: ' . count($names));

        // ── Buscar en Odoo por nombre exacto ─────────────────────────────────
        $this->info('Consultando Odoo (búsqueda bulk por nombre exacto)...');

        $odooMap = [];
        $bar     = $this->output->createProgressBar(count(array_chunk($names, 200)));
        $bar->start();

        foreach (array_chunk($names, 200) as $batch) {
            $result = $odoo->execute('res.partner', 'search_read',
                [[['name', 'in', $batch], ['is_company', '=', true]]],
                ['fields' => ['name', 'account_no', 'email'], 'limit' => 0]
            ) ?? [];

            foreach ($result as $r) {
                $odooMap[$r['name']] = $r;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line('  Encontrados en Odoo: ' . count($odooMap));

        // ── Construir payload ─────────────────────────────────────────────────
        $payload = [];
        foreach ($names as $name) {
            if (!isset($odooMap[$name])) continue;

            $m      = $odooMap[$name];
            $ref    = ($m['account_no'] !== false && $m['account_no'] !== null && $m['account_no'] !== '')
                      ? (string) $m['account_no'] : null;

            // Odoo puede devolver varios emails separados por coma — tomamos el primero válido
            $email = null;
            if ($m['email'] && $m['email'] !== false) {
                $parts = array_map('trim', explode(',', $m['email']));
                foreach ($parts as $p) {
                    if (filter_var($p, FILTER_VALIDATE_EMAIL)) {
                        $email = $p;
                        break;
                    }
                }
            }

            if (!$email && !$ref) continue;

            $payload[] = array_filter([
                'customer_name'  => $name,
                'email_cliente'  => $email,
                'numero_cuenta'  => $ref,
            ], fn($v) => $v !== null);
        }

        $this->line('  Con datos para actualizar: ' . count($payload));

        if ($isDry) {
            $this->warn('Modo dry-run — no se realizan cambios.');
            $rows = array_map(fn($c) => [
                $c['customer_name'],
                $c['email_cliente'] ?? '—',
                $c['numero_cuenta'] ?? '—',
            ], array_slice($payload, 0, 30));

            $this->table(['Cliente', 'Email', 'Nro. Cuenta'], $rows);
            if (count($payload) > 30) {
                $this->line('  ... y ' . (count($payload) - 30) . ' más.');
            }
            return self::SUCCESS;
        }

        // ── Generar token temporal para llamar la API ─────────────────────────
        $admin = User::where('role', 'admin')->first()
              ?? User::whereHas('roleModel', fn($q) => $q->where('slug', 'admin'))->first();

        if (!$admin) {
            $this->error('No se encontró un usuario administrador para generar el token.');
            return self::FAILURE;
        }

        $token     = $admin->createToken('msp-sync-command')->plainTextToken;
        $apiUrl    = rtrim(config('app.url'), '/') . '/api/v1/msp-clients/bulk-update';
        $totalSent = 0;
        $updated   = 0;
        $skipped   = 0;
        $errors    = [];

        $this->info("Llamando API en lotes de {$chunk}...");
        $bar = $this->output->createProgressBar(count(array_chunk($payload, $chunk)));
        $bar->start();

        foreach (array_chunk($payload, $chunk) as $lote) {
            $response = Http::withToken($token)
                ->withoutVerifying()
                ->timeout(30)
                ->post($apiUrl, ['clients' => $lote]);

            if ($response->successful()) {
                $data     = $response->json();
                $updated += $data['updated'] ?? 0;
                $skipped += $data['skipped'] ?? 0;
                $errors   = array_merge($errors, $data['errors'] ?? []);
            } else {
                $errors[] = 'HTTP ' . $response->status() . ': ' . $response->body();
            }

            $totalSent += count($lote);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // ── Limpiar token temporal ────────────────────────────────────────────
        $admin->tokens()->where('name', 'msp-sync-command')->delete();

        // ── Resultado ────────────────────────────────────────────────────────
        $this->info("✅ Completado.");
        $this->table(['Métrica', 'Valor'], [
            ['Enviados a la API', $totalSent],
            ['Actualizados',      $updated],
            ['Omitidos',          $skipped],
            ['Errores',           count($errors)],
        ]);

        if (!empty($errors)) {
            $this->warn('Errores:');
            foreach (array_slice($errors, 0, 10) as $e) {
                $this->line("  - {$e}");
            }
        }

        return self::SUCCESS;
    }
}
