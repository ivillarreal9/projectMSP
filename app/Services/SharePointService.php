<?php

namespace App\Services;

use App\Models\MspSetting;
use Illuminate\Support\Facades\Http;

class SharePointService
{
    private string $tenantId;
    private string $clientId;
    private string $clientSecret;
    private string $siteUrl;
    private string $folderId;
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->tenantId     = MspSetting::get('azure_tenant_id', '');
        $this->clientId     = MspSetting::get('azure_client_id', '');
        $this->clientSecret = MspSetting::get('azure_client_secret', '');
        $this->siteUrl      = MspSetting::get('sharepoint_site_url', '');
        $this->folderId     = MspSetting::get('sharepoint_folder_id', '');
    }

    // ─── Validar que hay credenciales configuradas ────────────────────────────
    public function hasCredentials(): bool
    {
        return !empty($this->tenantId) &&
               !empty($this->clientId) &&
               !empty($this->clientSecret) &&
               !empty($this->siteUrl) &&
               !empty($this->folderId);
    }

    // ─── 1. Obtener Access Token ──────────────────────────────────────────────
    public function getAccessToken(): string
    {
        if ($this->accessToken) return $this->accessToken;

        $response = Http::asForm()->post(
            "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token",
            [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope'         => 'https://graph.microsoft.com/.default',
            ]
        );

        if (!$response->successful()) {
            throw new \RuntimeException('Error obteniendo token: ' . $response->body());
        }

        $this->accessToken = $response->json('access_token');
        return $this->accessToken;
    }

    // ─── 2. Obtener Site ID ───────────────────────────────────────────────────
    public function getSiteId(): string
    {
        $parsed   = parse_url($this->siteUrl);
        $hostname = $parsed['host'];
        $sitePath = ltrim($parsed['path'], '/');

        $response = Http::withToken($this->getAccessToken())
            ->get("https://graph.microsoft.com/v1.0/sites/{$hostname}:/{$sitePath}");

        if (!$response->successful()) {
            throw new \RuntimeException('Error obteniendo Site ID: ' . $response->body());
        }

        return $response->json('id');
    }

    // ─── 3. Obtener Drive ID ──────────────────────────────────────────────────
    public function getDriveId(string $siteId): string
    {
        $response = Http::withToken($this->getAccessToken())
            ->get("https://graph.microsoft.com/v1.0/sites/{$siteId}/drives");

        if (!$response->successful()) {
            throw new \RuntimeException('Error obteniendo Drive ID: ' . $response->body());
        }

        $drives = $response->json('value');
        foreach ($drives as $drive) {
            if (str_contains(strtolower($drive['name']), 'document') ||
                str_contains(strtolower($drive['name']), 'shared')) {
                return $drive['id'];
            }
        }

        return $drives[0]['id'];
    }

    // ─── 4. Listar archivos Excel usando folder ID ────────────────────────────
    public function listExcelFiles(): array
    {
        $token   = $this->getAccessToken();
        $siteId  = $this->getSiteId();
        $driveId = $this->getDriveId($siteId);

        $response = Http::withToken($token)
            ->get("https://graph.microsoft.com/v1.0/sites/{$siteId}/drives/{$driveId}/items/{$this->folderId}/children");

        if (!$response->successful()) {
            throw new \RuntimeException('Error listando archivos: ' . $response->body());
        }

        return collect($response->json('value'))
            ->filter(fn($f) => str_ends_with(strtolower($f['name']), '.xlsx') ||
                               str_ends_with(strtolower($f['name']), '.xls'))
            ->map(fn($f) => [
                'name'         => $f['name'],
                'size'         => round($f['size'] / 1024, 1) . ' KB',
                'modified'     => $f['lastModifiedDateTime'],
                'download_url' => $f['@microsoft.graph.downloadUrl'] ?? null,
                'item_id'      => $f['id'],
            ])
            ->values()
            ->toArray();
    }

    // ─── 5. Descargar archivo por nombre ─────────────────────────────────────
    public function downloadFileByName(string $filename): string
    {
        $token   = $this->getAccessToken();
        $siteId  = $this->getSiteId();
        $driveId = $this->getDriveId($siteId);

        $response = Http::withToken($token)
            ->get("https://graph.microsoft.com/v1.0/sites/{$siteId}/drives/{$driveId}/items/{$this->folderId}/children");

        $files = collect($response->json('value'));
        $file  = $files->firstWhere('name', $filename);

        if (!$file) {
            throw new \RuntimeException("Archivo '{$filename}' no encontrado en SharePoint.");
        }

        $downloadUrl = $file['@microsoft.graph.downloadUrl'];
        $fileContent = Http::get($downloadUrl)->body();

        $tempPath = storage_path('app/temp_' . time() . '_' . $filename);
        file_put_contents($tempPath, $fileContent);

        return $tempPath;
    }

    // ─── 6. Descargar por item ID ─────────────────────────────────────────────
    public function downloadFileById(string $itemId, string $filename): string
    {
        $token   = $this->getAccessToken();
        $siteId  = $this->getSiteId();
        $driveId = $this->getDriveId($siteId);

        $response = Http::withToken($token)
            ->get("https://graph.microsoft.com/v1.0/sites/{$siteId}/drives/{$driveId}/items/{$itemId}");

        if (!$response->successful()) {
            throw new \RuntimeException('Error obteniendo archivo: ' . $response->body());
        }

        $downloadUrl = $response->json('@microsoft.graph.downloadUrl');

        // Si no trae downloadUrl, usar el endpoint de content directamente
        if (empty($downloadUrl)) {
            $contentResponse = Http::withToken($token)
                ->get("https://graph.microsoft.com/v1.0/sites/{$siteId}/drives/{$driveId}/items/{$itemId}/content");
            $fileContent = $contentResponse->body();
        } else {
            $fileContent = Http::get($downloadUrl)->body();
        }

        $tempPath = storage_path('app/temp_' . time() . '_' . $filename);
        file_put_contents($tempPath, $fileContent);

        return $tempPath;
    }
}
