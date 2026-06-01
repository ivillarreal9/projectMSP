<?php

namespace App\Services;

use App\Models\MspClient;
use App\Models\MspReport;
use Spatie\Browsershot\Browsershot;

/**
 * Servicio de generación de PDFs para los reportes MSP de clientes.
 *
 * Responsable de todo el ciclo de vida del PDF:
 *   1. Construir el nombre de archivo seguro para el sistema de archivos.
 *   2. Resolver los logos (cliente y Ovnicom) en formato base64 para embebido en HTML.
 *   3. Renderizar la vista Blade pdf_template a HTML.
 *   4. Convertir el HTML a PDF usando Spatie/Browsershot (Chromium headless).
 *
 * El PDF resultante se guarda en storage/app/public/msp_pdfs/ y puede servirse
 * directamente como archivo estático o subirse a SharePoint via SharePointService.
 *
 * Dependencias externas:
 *   - Spatie/Browsershot : require chromium, node, npm en el contenedor Docker
 *   - MspReport::statsForCustomer() : calcula estadísticas del reporte desde la BD
 *   - MspClient : modelo con método getLogoBase64() para el logo del cliente
 *   - Variables de entorno: BROWSERSHOT_CHROME_PATH, BROWSERSHOT_NODE_PATH, BROWSERSHOT_NPM_PATH
 */
class MspPdfService
{
    /**
     * Construye el nombre de archivo PDF seguro para el sistema de archivos.
     *
     * Reemplaza espacios, barras y comas con guiones para evitar problemas
     * en rutas de almacenamiento y nombres de archivo en SharePoint.
     *
     * @param  string $customer Nombre del cliente
     * @param  string $periodo  Período del reporte (p.ej. "Enero 2024")
     * @return string           Nombre de archivo con formato "MSP-{cliente}-{periodo}.pdf"
     */
    public function buildFilename(string $customer, string $periodo): string
    {
        $safeCustomer = str_replace([' ', '/', ',', '\\'], '-', $customer);
        $safePeriodo  = str_replace(' ', '-', $periodo);
        return "MSP-{$safeCustomer}-{$safePeriodo}.pdf";
    }

    /**
     * Retorna la ruta absoluta en disco donde se almacena el PDF.
     *
     * La carpeta msp_pdfs/ está dentro del disco public de Laravel para que
     * los archivos sean accesibles via Storage::url() o symlink artisan.
     *
     * @param  string $filename Nombre de archivo devuelto por buildFilename()
     * @return string           Ruta absoluta al archivo en storage/app/public/msp_pdfs/
     */
    public function outputPath(string $filename): string
    {
        return storage_path("app/public/msp_pdfs/{$filename}");
    }

    /**
     * Genera (o reutiliza) el PDF de reporte MSP para un cliente y período.
     *
     * Si el archivo ya existe en disco y no se fuerza la regeneración, lo devuelve
     * directamente sin llamar a Browsershot — optimización clave para envíos masivos
     * donde el mismo PDF puede solicitarse varias veces seguidas.
     *
     * @param  string $customer        Nombre del cliente (tal como aparece en MspReport)
     * @param  string $periodo         Período del reporte (p.ej. "Enero 2024")
     * @param  bool   $forceRegenerate Si true, regenera el PDF aunque ya exista en disco
     * @return string                  Ruta absoluta al PDF generado o reutilizado
     */
    public function generate(string $customer, string $periodo, bool $forceRegenerate = false): string
    {
        $filename = $this->buildFilename($customer, $periodo);
        $path     = $this->outputPath($filename);

        if (!$forceRegenerate && file_exists($path)) {
            return $path;
        }

        $stats       = MspReport::statsForCustomer($customer, $periodo);
        $logoUrl     = $this->resolveClientLogoBase64($customer);
        $ovnicomLogo = $this->resolveOvnicomLogoBase64();

        $html = view('admin.reports.msp.pdf_template',
            compact('customer', 'stats', 'periodo', 'ovnicomLogo') + ['logoUrl' => $logoUrl]
        )->render();

        $this->renderPdf($html, $path);

        return $path;
    }

    /**
     * Convierte el HTML del reporte a un archivo PDF usando Browsershot (Chromium headless).
     *
     * Crea el directorio de destino si no existe. Los argumentos de Chromium
     * (noSandbox, disable-dev-shm-usage, disable-gpu) son necesarios para
     * ejecutar correctamente dentro del contenedor Docker sin privilegios root.
     * El timeout de 120 s es alto porque el HTML del reporte puede ser extenso.
     *
     * @param  string $html       HTML completo del reporte (incluye logos en base64)
     * @param  string $outputPath Ruta absoluta donde se guardará el PDF
     * @return void
     */
    private function renderPdf(string $html, string $outputPath): void
    {
        if (!is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        Browsershot::html($html)
            ->setChromePath(env('BROWSERSHOT_CHROME_PATH', '/usr/bin/chromium'))
            ->setNodeBinary(env('BROWSERSHOT_NODE_PATH', '/usr/bin/node'))
            ->setNpmBinary(env('BROWSERSHOT_NPM_PATH', '/usr/bin/npm'))
            ->noSandbox()
            ->addChromiumArguments([
                'disable-dev-shm-usage',
                'disable-gpu',
            ])
            ->format('A4')
            ->showBackground()
            ->timeout(120)
            ->save($outputPath);
    }

    /**
     * Obtiene el logo del cliente en formato base64 desde el modelo MspClient.
     *
     * Los logos se embeben como data URI en el HTML para que Browsershot los incluya
     * en el PDF sin necesidad de acceso a URLs externas desde el contenedor.
     *
     * @param  string $customer Nombre del cliente a buscar en la tabla msp_clients
     * @return string|null      Data URI "data:{mime};base64,{datos}", o null si no hay logo
     */
    private function resolveClientLogoBase64(string $customer): ?string
    {
        $cliente = MspClient::where('customer_name', $customer)->first();
        return $cliente?->getLogoBase64();
    }

    /**
     * Obtiene el logo de Ovnicom en formato base64 buscando en rutas candidatas.
     *
     * Busca primero en storage/app/public/logos/ y luego en public/images/ para
     * soportar tanto entornos Docker como instalaciones locales de desarrollo.
     *
     * @return string|null Data URI "data:{mime};base64,{datos}", o null si no se encuentra el logo
     */
    private function resolveOvnicomLogoBase64(): ?string
    {
        $candidates = [
            storage_path('app/public/logos/ovnicom.png'),
            public_path('images/ovnicom-logo.png'),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                $mime = mime_content_type($path);
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            }
        }

        return null;
    }
}
