<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MerakiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MerakiLicensesExpiryNotifyCommand extends Command
{
    protected $signature = 'meraki:notify-licenses
                            {--days=90 : Umbral de días para alertar (default 90)}
                            {--dry-run : Muestra el resultado sin enviar correo}';

    protected $description = 'Envía un correo diario con las licencias Meraki próximas a vencer';

    public function handle(MerakiService $meraki): int
    {
        $threshold = (int) $this->option('days');
        $dryRun    = (bool) $this->option('dry-run');

        $this->info("Revisando licencias con vencimiento en ≤ {$threshold} días...");

        try {
            $organizations = $meraki->getOrganizations();
        } catch (\Throwable $e) {
            $this->error('No se pudo obtener organizaciones: ' . $e->getMessage());
            Log::error('meraki:notify-licenses — getOrganizations falló: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $byOrg   = [];
        $summary = ['critical' => 0, 'warning' => 0, 'notice' => 0, 'total' => 0];
        $now     = Carbon::now();

        foreach ($organizations as $org) {
            $orgId   = $org['id'];
            $orgName = $org['name'];

            // ── Per-device licenses ──────────────────────────────────────
            try {
                $licenses = $meraki->getLicenses($orgId);
            } catch (\Throwable $e) {
                Log::warning("meraki:notify-licenses — getLicenses [{$orgId}]: " . $e->getMessage());
                $licenses = [];
            }

            // Serial → nombre del dispositivo
            $deviceMap = [];
            try {
                foreach ($meraki->getDevices($orgId) as $device) {
                    $serial = $device['serial'] ?? '';
                    $deviceMap[$serial] = $device['name'] ?? $device['model'] ?? $serial ?: '—';
                }
            } catch (\Throwable $e) {
                Log::warning("meraki:notify-licenses — getDevices [{$orgId}]: " . $e->getMessage());
            }

            $expiring = [];

            foreach ($licenses as $lic) {
                $expDate = $lic['expirationDate'] ?? null;
                if (!$expDate) {
                    continue;
                }

                try {
                    $exp  = Carbon::parse($expDate);
                    $days = (int) $now->diffInDays($exp, false);
                } catch (\Throwable) {
                    continue;
                }

                // Ignorar ya vencidas y las que superan el umbral
                if ($days < 0 || $days > $threshold) {
                    continue;
                }

                $serial     = $lic['deviceSerial'] ?? null;
                $deviceName = $serial ? ($deviceMap[$serial] ?? $serial) : '(sin asignar)';

                $urgency = match (true) {
                    $days < 30 => 'critical',
                    $days < 60 => 'warning',
                    default    => 'notice',
                };

                $expiring[] = [
                    'licenseType' => $lic['licenseType'] ?? '—',
                    'deviceName'  => $deviceName,
                    'serial'      => $serial ?? '—',
                    'state'       => $lic['state'] ?? '—',
                    'expiresAt'   => $exp->format('d/m/Y'),
                    'daysLeft'    => $days,
                    'urgency'     => $urgency,
                ];

                $summary[$urgency]++;
                $summary['total']++;
            }

            // ── Co-termination: usa overview cuando no hay licencias individuales ──
            if (empty($licenses)) {
                try {
                    $overview = $meraki->getLicensesOverview($orgId);
                    $expDate  = $overview['expirationDate'] ?? null;

                    if ($expDate) {
                        $exp  = Carbon::parse($expDate);
                        $days = (int) $now->diffInDays($exp, false);

                        if ($days >= 0 && $days <= $threshold) {
                            $urgency = match (true) {
                                $days < 30 => 'critical',
                                $days < 60 => 'warning',
                                default    => 'notice',
                            };

                            $expiring[] = [
                                'licenseType' => 'Co-termination',
                                'deviceName'  => 'Toda la organización',
                                'serial'      => '—',
                                'state'       => $overview['status'] ?? '—',
                                'expiresAt'   => $exp->format('d/m/Y'),
                                'daysLeft'    => $days,
                                'urgency'     => $urgency,
                            ];

                            $summary[$urgency]++;
                            $summary['total']++;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("meraki:notify-licenses — getLicensesOverview [{$orgId}]: " . $e->getMessage());
                }
            }

            if (!empty($expiring)) {
                usort($expiring, fn ($a, $b) => $a['daysLeft'] <=> $b['daysLeft']);
                $byOrg[] = ['orgName' => $orgName, 'orgId' => $orgId, 'licenses' => $expiring];
            }
        }

        if (empty($byOrg)) {
            $this->info("Sin licencias por vencer en los próximos {$threshold} días. No se envía correo.");
            Log::info("meraki:notify-licenses — Sin licencias próximas ({$threshold} días).");
            return Command::SUCCESS;
        }

        $this->info(
            "Encontradas: {$summary['total']} (críticas <30d: {$summary['critical']}, " .
            "advertencia <60d: {$summary['warning']}, aviso <90d: {$summary['notice']})"
        );

        $recipients = $this->getRecipients();

        if (empty($recipients)) {
            $this->warn('Sin destinatarios. Agrega MERAKI_NOTIFY_EMAILS en .env o crea usuarios con rol admin.');
            return Command::FAILURE;
        }

        $generatedAt = now()->format('d/m/Y H:i');

        if ($dryRun) {
            $this->info('--dry-run activo. Destinatarios: ' . implode(', ', $recipients));
            $rows = [];
            foreach ($byOrg as $o) {
                foreach ($o['licenses'] as $l) {
                    $rows[] = [$o['orgName'], $l['licenseType'], $l['deviceName'], $l['daysLeft'] . ' días'];
                }
            }
            $this->table(['Organización', 'Licencia', 'Dispositivo', 'Días restantes'], $rows);
            return Command::SUCCESS;
        }

        $sendgridKey = config('services.sendgrid.api_key');
        $fromEmail   = config('services.sendgrid.from', 'ivillarreal@ovni.com');

        if (empty($sendgridKey)) {
            $this->error('SENDGRID_API_KEY no configurado en .env');
            Log::error('meraki:notify-licenses — SENDGRID_API_KEY vacío.');
            return Command::FAILURE;
        }

        $subject = $summary['critical'] > 0
            ? "⚠️ Meraki: {$summary['critical']} licencia(s) vencen en menos de 30 días"
            : 'Meraki: Reporte de licencias próximas a vencer';

        $html = view('emails.meraki-licenses-expiring', [
            'byOrg'       => $byOrg,
            'summary'     => $summary,
            'generatedAt' => $generatedAt,
        ])->render();

        foreach ($recipients as $email) {
            $payload = [
                'personalizations' => [[
                    'to'      => [['email' => $email]],
                    'subject' => $subject,
                ]],
                'from'    => ['email' => $fromEmail, 'name' => config('app.name', 'Ovnicom Analytics')],
                'subject' => $subject,
                'content' => [['type' => 'text/html', 'value' => $html]],
            ];

            $response = Http::withToken($sendgridKey)
                ->post('https://api.sendgrid.com/v3/mail/send', $payload);

            if ($response->successful() || $response->status() === 202) {
                $this->info("Correo enviado a: {$email}");
            } else {
                $this->error("SendGrid error [{$email}]: " . $response->body());
                Log::error("meraki:notify-licenses — SendGrid error [{$email}]: " . $response->body());
            }
        }

        Log::info('meraki:notify-licenses completado. Enviado a: ' . implode(', ', $recipients));

        return Command::SUCCESS;
    }

    private function getRecipients(): array
    {
        $envEmails = config('meraki.notify_emails', '');

        if (!empty($envEmails)) {
            return array_values(array_filter(array_map('trim', explode(',', $envEmails))));
        }

        // Fallback: todos los usuarios con rol admin
        return User::whereHas('roleModel', fn ($q) => $q->where('slug', 'admin'))
            ->pluck('email')
            ->toArray();
    }
}
