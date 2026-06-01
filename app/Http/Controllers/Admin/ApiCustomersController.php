<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Controlador del módulo API Customers.
 *
 * Consulta y exporta el listado de clientes directamente desde la API MSP externa.
 * A diferencia del módulo MSP Reports que trabaja con datos importados a la base de datos,
 * este módulo hace llamadas en tiempo real a la API para obtener el directorio completo
 * de clientes con su CustomerId.
 *
 * Casos de uso:
 *  - Obtener el CustomerId de un cliente para configurarlo en MSP Reports.
 *  - Exportar el directorio completo de clientes MSP a Excel para auditoría.
 *
 * Autenticación: HTTP Basic Auth con credenciales AZURE_MSP_USERNAME / AZURE_MSP_PASSWORD
 * configuradas en config/services.php → services.msp.
 *
 * Vistas:
 *   - admin.api-customers.index → Vista con botón de consulta y tabla de resultados
 *
 * Rutas principales (prefijo /admin/api-customers):
 *   GET  /        → index()
 *   GET  /fetch   → fetch()   [AJAX]
 *   GET  /export  → export()  [descarga Excel]
 */
class ApiCustomersController extends Controller
{
    /**
     * Construye el header de autenticación HTTP Basic para la API MSP.
     *
     * Codifica en Base64 el par "usuario:contraseña" según el estándar RFC 7617.
     *
     * @return string  Header completo: "Basic {base64(usuario:password)}"
     */
    private function getAuthHeader(): string
    {
        $username = config('services.msp.username');
        $password = config('services.msp.password');
        return 'Basic ' . base64_encode($username . ':' . $password);
    }

    /**
     * Obtiene la URL base de la API MSP sin barra final.
     *
     * @return string  URL base (ej: "https://api.msp.ovnicom.com/v1")
     */
    private function getBaseUrl(): string
    {
        return rtrim(config('services.msp.base_url'), '/');
    }

    /**
     * Vista principal del módulo API Customers.
     *
     * Muestra el estado de las credenciales MSP y el formulario de consulta.
     * Los datos reales se cargan vía AJAX al hacer clic en el botón de consulta.
     *
     * @return \Illuminate\View\View  Vista admin.api-customers.index con: credencialesOk
     */
    public function index()
    {
        $credencialesOk = !empty(config('services.msp.username')) && !empty(config('services.msp.password'));
        return view('admin.api-customers.index', compact('credencialesOk'));
    }

    /**
     * Consulta el directorio completo de clientes desde la API MSP (endpoint AJAX).
     *
     * Ordena los resultados por StartDate descendente (clientes más recientes primero).
     * Solo selecciona CustomerName y CustomerId para minimizar el payload de respuesta.
     * Timeout de 60 segundos para manejar respuestas lentas de la API externa.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  {data: array, total: int} o {error: string}
     */
    public function fetch(Request $request)
    {
        if (!config('services.msp.username')) {
            return response()->json(['error' => 'Sin credenciales configuradas en .env'], 400);
        }

        $url = $this->getBaseUrl() . '/customers?$OrderBy=StartDate desc&$select=CustomerName,CustomerId';

        $response = Http::withHeaders([
            'Authorization' => $this->getAuthHeader(),
        ])->timeout(60)->get($url);

        if ($response->failed()) {
            return response()->json([
                'error' => "Error API [{$response->status()}]: " . $response->body()
            ], 500);
        }

        $data = $response->json('value') ?? [];

        return response()->json([
            'data'  => $data,
            'total' => count($data),
        ]);
    }

    /**
     * Exporta el directorio de clientes MSP a un archivo Excel (.xlsx).
     *
     * Usa una clase anónima inline que implementa las interfaces de Maatwebsite Excel
     * para definir encabezados, datos, auto-size de columnas y estilos (header violeta).
     * Realiza la misma consulta que fetch() pero retorna un archivo descargable.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function export(Request $request)
    {
        if (!config('services.msp.username')) {
            return back()->with('error', 'Sin credenciales configuradas en .env');
        }

        $url = $this->getBaseUrl() . '/customers?$OrderBy=StartDate desc&$select=CustomerName,CustomerId';

        $response = Http::withHeaders([
            'Authorization' => $this->getAuthHeader(),
        ])->timeout(60)->get($url);

        if ($response->failed()) {
            return back()->with('error', "Error API [{$response->status()}]");
        }

        $data = $response->json('value') ?? [];

        $export = new class($data) implements FromArray, WithHeadings, ShouldAutoSize, WithStyles {
            public function __construct(private array $data) {}

            public function headings(): array
            {
                return ['Customer Name', 'Customer ID'];
            }

            public function array(): array
            {
                return array_map(fn($row) => [
                    $row['CustomerName'] ?? '',
                    $row['CustomerId']   ?? '',
                ], $this->data);
            }

            public function styles(Worksheet $sheet): array
            {
                $sheet->getStyle('A1:B1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF7C3AED'],
                    ],
                ]);
                return [];
            }
        };

        return Excel::download($export, 'customers-msp-' . now()->format('Y-m-d') . '.xlsx');
    }
}