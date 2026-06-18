<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de integración con Microsoft SharePoint vía Microsoft Graph API.
 *
 * Gestiona dos operaciones principales:
 *   1. Lectura de archivos Excel desde una carpeta SharePoint configurada
 *      (usado para importar reportes MSP).
 *   2. Subida de PDFs generados a una carpeta SharePoint de destino
 *      (usado para archivar reportes en la nube).
 *
 * Autenticación: OAuth 2.0 Client Credentials (sin usuario) — el token se obtiene
 * del tenant Azure AD y se cachea ~58 minutos para evitar llamadas repetidas.
 *
 * Dependencias externas:
 *   - Microsoft Graph API v1.0 : https://graph.microsoft.com/v1.0/
 *   - Variables de entorno:
 *       AZURE_TENANT_ID, AZURE_CLIENT_ID, AZURE_CLIENT_SECRET  (autenticación)
 *       SHAREPOINT_SITE_URL, SHAREPOINT_FOLDER_ID              (fuente de Excel)
 *       SHAREPOINT_PDF_SITE_URL, SHAREPOINT_PDF_FOLDER_PATH    (destino de PDFs)
 */
class SharePointService
{
    private string $tenantId;
    private string $clientId;
    private string $clientSecret;
    private string $siteUrl;
    private string $folderId;

    /**
     * Inicializa el servicio leyendo las credenciales y configuración desde config/services.php.
     */
    public function __construct()
    {
        $this->tenantId     = (string) config('services.sharepoint.tenant_id', '');
        $this->clientId     = (string) config('services.sharepoint.client_id', '');
        $this->clientSecret = (string) config('services.sharepoint.client_secret', '');
        $this->siteUrl      = (string) config('services.sharepoint.site_url', '');
        $this->folderId     = (string) config('services.sharepoint.folder_id', '');
    }

    /**
     * Verifica si todas las credenciales y configuraciones obligatorias están presentes.
     *
     * Útil para mostrar advertencias en la UI antes de intentar cualquier operación
     * con SharePoint, evitando errores crípticos de Graph API.
     *
     * @return bool true si todas las variables necesarias tienen valor
     */
    public function hasCredentials(): bool
    {
        return !empty($this->tenantId)
            && !empty($this->clientId)
            && !empty($this->clientSecret)
            && !empty($this->siteUrl)
            && !empty($this->folderId);
    }

    /**
     * Retorna la lista de nombres de variables de entorno que están vacías.
     *
     * Permite mostrar al administrador exactamente qué variables faltan
     * en lugar de un mensaje genérico de error.
     *
     * @return array Lista de nombres de variables faltantes (p.ej. ['AZURE_CLIENT_SECRET'])
     */
    public function missingCredentials(): array
    {
        $map = [
            'AZURE_TENANT_ID'       => $this->tenantId,
            'AZURE_CLIENT_ID'       => $this->clientId,
            'AZURE_CLIENT_SECRET'   => $this->clientSecret,
            'SHAREPOINT_SITE_URL'   => $this->siteUrl,
            'SHAREPOINT_FOLDER_ID'  => $this->folderId,
        ];

        return array_keys(array_filter($map, fn($v) => empty($v)));
    }

    /**
     * Obtiene el token de acceso OAuth 2.0 para Microsoft Graph API.
     *
     * Usa el flujo Client Credentials (sin usuario), adecuado para acceso
     * a SharePoint de la organización desde una app de servidor.
     * El token se cachea 3 500 segundos (~58 min) — los tokens de Azure AD
     * duran 3 600 s (1 h), los 100 s de margen evitan usar un token expirado.
     *
     * @return string Token Bearer listo para usar en cabecera Authorization
     * @throws \RuntimeException si Azure AD rechaza las credenciales o retorna error
     */
    public function getAccessToken(): string
    {
        $cacheKey = 'sharepoint_token_' . md5($this->clientId);

        return Cache::remember($cacheKey, 3500, function () {
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

            return $response->json('access_token');
        });
    }

    /**
     * Resuelve el ID interno de Graph API del sitio SharePoint configurado.
     *
     * Graph API requiere el ID de sitio (no la URL) para todas las operaciones
     * con drives e items. La URL configurada se descompone en hostname + path
     * para construir el endpoint de resolución de Graph.
     *
     * @return string ID del sitio SharePoint (formato "hostname,siteId,webId")
     * @throws \RuntimeException si Graph API no puede resolver la URL del sitio
     */
    public function getSiteId(): string
    {
        $parsed   = parse_url($this->siteUrl);
        $hostname = $parsed['host'] ?? '';
        $sitePath = ltrim($parsed['path'] ?? '', '/');

        $response = Http::withToken($this->getAccessToken())
            ->get("https://graph.microsoft.com/v1.0/sites/{$hostname}:/{$sitePath}");

        if (!$response->successful()) {
            throw new \RuntimeException('Error obteniendo Site ID: ' . $response->body());
        }

        return $response->json('id');
    }

    /**
     * Obtiene el ID del drive principal (biblioteca de documentos) de un sitio SharePoint.
     *
     * Prefiere drives cuyo nombre contenga "document" o "shared" porque corresponden
     * a la biblioteca de documentos estándar de SharePoint. Si ninguno coincide,
     * usa el primero de la lista como fallback.
     *
     * @param  string $siteId ID del sitio SharePoint (obtenido de getSiteId())
     * @return string         ID del drive de documentos
     * @throws \RuntimeException si Graph API retorna error al listar los drives
     */
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

    /**
     * Lista todos los archivos Excel (.xlsx y .xls) en la carpeta SharePoint configurada.
     *
     * La carpeta está identificada por SHAREPOINT_FOLDER_ID — un ID de item de Graph API,
     * más estable que una ruta de texto que puede cambiar si se mueve la carpeta.
     *
     * @return array Lista de archivos con name, size (KB), modified, download_url, item_id
     * @throws \RuntimeException si Graph API retorna error al listar los children del folder
     */
    public function listExcelFiles(?string $folderId = null): array
    {
        $folder  = $folderId ?? $this->folderId;
        $token   = $this->getAccessToken();
        $siteId  = $this->getSiteId();
        $driveId = $this->getDriveId($siteId);

        $response = Http::withToken($token)
            ->get("https://graph.microsoft.com/v1.0/sites/{$siteId}/drives/{$driveId}/items/{$folder}/children");

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

    /**
     * Descarga un archivo de la carpeta SharePoint buscándolo por nombre exacto.
     *
     * Lista los children de la carpeta configurada y localiza el archivo por nombre.
     * El contenido se guarda en un archivo temporal en storage/app/ con timestamp
     * para evitar colisiones en descargas concurrentes.
     *
     * @param  string $filename Nombre exacto del archivo (incluyendo extensión)
     * @return string           Ruta absoluta al archivo temporal descargado
     * @throws \RuntimeException si el archivo no existe en SharePoint
     */
    public function downloadFileByName(string $filename, ?string $folderId = null): string
    {
        $folder  = $folderId ?? $this->folderId;
        $token   = $this->getAccessToken();
        $siteId  = $this->getSiteId();
        $driveId = $this->getDriveId($siteId);

        $response = Http::withToken($token)
            ->get("https://graph.microsoft.com/v1.0/sites/{$siteId}/drives/{$driveId}/items/{$folder}/children");

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

    /**
     * Sube un archivo PDF al sitio SharePoint de PDFs configurado.
     *
     * Usa un sitio y carpeta distintos de los de Excel (SHAREPOINT_PDF_SITE_URL
     * y SHAREPOINT_PDF_FOLDER_PATH) para separar las fuentes de datos de los entregables.
     * Si SHAREPOINT_PDF_SITE_URL no está configurado, registra un warning y retorna sin
     * error para no bloquear el flujo de generación de PDF.
     *
     * La subida usa el endpoint "upload simple" de Graph API (PUT /root:/{path}:/content),
     * adecuado para archivos pequeños (< 4 MB). Para archivos mayores se necesitaría
     * un upload session.
     *
     * @param  string $localPath Ruta absoluta al archivo PDF en el servidor
     * @param  string $filename  Nombre con el que se guardará en SharePoint
     * @return void
     */
    public function uploadPdf(string $localPath, string $filename): void
    {
        $pdfSiteUrl    = (string) config('services.sharepoint.pdf_site_url', '');
        $pdfFolderPath = (string) config('services.sharepoint.pdf_folder_path', 'Documentos/DESCARGAS DE PDF');

        if (empty($pdfSiteUrl)) {
            Log::warning('SharePoint PDF: SHAREPOINT_PDF_SITE_URL no configurado, se omite la subida.');
            return;
        }

        $token = $this->getAccessToken();

        $parsed   = parse_url($pdfSiteUrl);
        $hostname = $parsed['host'] ?? '';
        $sitePath = ltrim($parsed['path'] ?? '', '/');

        $siteResponse = Http::withToken($token)
            ->get("https://graph.microsoft.com/v1.0/sites/{$hostname}:/{$sitePath}");

        if (!$siteResponse->successful()) {
            Log::error("SharePoint PDF: error obteniendo Site ID: " . $siteResponse->body());
            return;
        }

        $siteId = $siteResponse->json('id');

        $drivesResponse = Http::withToken($token)
            ->get("https://graph.microsoft.com/v1.0/sites/{$siteId}/drives");

        if (!$drivesResponse->successful()) {
            Log::error("SharePoint PDF: error obteniendo drives: " . $drivesResponse->body());
            return;
        }

        $drives  = $drivesResponse->json('value');
        $driveId = null;
        foreach ($drives as $drive) {
            if (str_contains(strtolower($drive['name']), 'document') ||
                str_contains(strtolower($drive['name']), 'shared')) {
                $driveId = $drive['id'];
                break;
            }
        }
        $driveId ??= $drives[0]['id'] ?? null;

        if (!$driveId) {
            Log::error("SharePoint PDF: no se encontró un drive válido en [{$pdfSiteUrl}].");
            return;
        }

        $uploadPath = rtrim($pdfFolderPath, '/') . '/' . $filename;

        $response = Http::withToken($token)
            ->withBody(file_get_contents($localPath), 'application/pdf')
            ->put("https://graph.microsoft.com/v1.0/sites/{$siteId}/drives/{$driveId}/root:/{$uploadPath}:/content");

        if (!$response->successful()) {
            Log::error("SharePoint PDF: error subiendo [{$filename}]: " . $response->body());
        } else {
            Log::info("SharePoint PDF: subido correctamente [{$filename}]");
        }
    }

    /**
     * Descarga un archivo de SharePoint usando su ID de item de Graph API.
     *
     * Más robusto que downloadFileByName() porque el ID es estable aunque el archivo
     * sea renombrado o movido. Si Graph API no incluye downloadUrl en la respuesta
     * del item (ocurre con algunos tipos de archivo), hace una segunda petición
     * al endpoint /content para obtener el binario directamente.
     *
     * @param  string $itemId   ID del item de SharePoint (obtenido de listExcelFiles())
     * @param  string $filename Nombre con el que se guardará el archivo temporal
     * @return string           Ruta absoluta al archivo temporal descargado
     * @throws \RuntimeException si Graph API retorna error al obtener la metadata del item
     */
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

    /**
     * Lista archivos Excel desde una carpeta de un sitio SharePoint distinto al configurado.
     *
     * Útil para módulos que usan un site diferente al de MSP Reports.
     * Accede por ruta dentro del drive raíz del site (no necesita folder_id de Graph API).
     *
     * Endpoint Graph: GET /sites/{host}:/{sitePath}/drive/root:/{folderPath}:/children
     *
     * @param  string $siteUrl    URL completa del sitio SharePoint (p.ej. https://tenant.sharepoint.com/sites/MiSitio)
     * @param  string $folderPath Ruta de la carpeta dentro de la biblioteca de documentos (p.ej. "listado de enlaces- internacionales")
     * @return array  Lista de archivos con name, size, modified, download_url, item_id
     * @throws \RuntimeException si Graph API retorna error
     */
    public function listExcelFilesFromSite(string $siteUrl, string $folderPath): array
    {
        $token  = $this->getAccessToken();
        $parsed = parse_url($siteUrl);
        $host   = $parsed['host'] ?? '';
        $path   = ltrim($parsed['path'] ?? '', '/');

        // 1) Resolver el siteId del site de enlaces (Graph no permite anidar dos colon-paths).
        $siteResp = Http::withToken($token)
            ->get("https://graph.microsoft.com/v1.0/sites/{$host}:/{$path}");

        if (!$siteResp->successful()) {
            throw new \RuntimeException('Error obteniendo site de enlaces: ' . $siteResp->body());
        }

        $siteId = $siteResp->json('id');

        // 2) Listar la carpeta por ruta dentro del drive por defecto (Documentos compartidos).
        //    Se decodifica primero (por si el .env trae el valor URL-encoded copiado de la URL)
        //    y luego se codifica cada segmento por separado para preservar los '/'.
        $decodedPath = rawurldecode($folderPath);
        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $decodedPath)));

        $response = Http::withToken($token)
            ->get("https://graph.microsoft.com/v1.0/sites/{$siteId}/drive/root:/{$encodedPath}:/children");

        if (!$response->successful()) {
            throw new \RuntimeException('Error listando archivos de enlaces: ' . $response->body());
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

    /**
     * Descarga un archivo por item_id desde un sitio SharePoint distinto al configurado.
     *
     * @param  string $itemId   ID del item en Graph API
     * @param  string $filename Nombre del archivo (usado para el path del temporal)
     * @param  string $siteUrl  URL completa del sitio propietario del item
     * @return string           Ruta absoluta al archivo temporal descargado
     */
    public function downloadFileByIdFromSite(string $itemId, string $filename, string $siteUrl): string
    {
        $token  = $this->getAccessToken();
        $parsed = parse_url($siteUrl);
        $host   = $parsed['host'] ?? '';
        $path   = ltrim($parsed['path'] ?? '', '/');

        // Resolver siteId del site de enlaces
        $siteResp = Http::withToken($token)
            ->get("https://graph.microsoft.com/v1.0/sites/{$host}:/{$path}");

        if (!$siteResp->successful()) {
            throw new \RuntimeException('Error obteniendo site de enlaces: ' . $siteResp->body());
        }

        $siteId  = $siteResp->json('id');
        $driveId = $this->getDriveId($siteId);

        $response = Http::withToken($token)
            ->get("https://graph.microsoft.com/v1.0/sites/{$siteId}/drives/{$driveId}/items/{$itemId}");

        if (!$response->successful()) {
            throw new \RuntimeException('Error obteniendo archivo: ' . $response->body());
        }

        $downloadUrl = $response->json('@microsoft.graph.downloadUrl');

        if (empty($downloadUrl)) {
            $fileContent = Http::withToken($token)
                ->get("https://graph.microsoft.com/v1.0/sites/{$siteId}/drives/{$driveId}/items/{$itemId}/content")
                ->body();
        } else {
            $fileContent = Http::get($downloadUrl)->body();
        }

        $tempPath = storage_path('app/temp_' . time() . '_' . $filename);
        file_put_contents($tempPath, $fileContent);

        return $tempPath;
    }
}