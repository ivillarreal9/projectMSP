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

class ApiCustomersController extends Controller
{
    private function getAuthHeader(): string
    {
        $username = config('services.msp.username');
        $password = config('services.msp.password');
        return 'Basic ' . base64_encode($username . ':' . $password);
    }

    private function getBaseUrl(): string
    {
        return rtrim(config('services.msp.base_url'), '/');
    }

    public function index()
    {
        $credencialesOk = !empty(config('services.msp.username')) && !empty(config('services.msp.password'));
        return view('admin.api-customers.index', compact('credencialesOk'));
    }

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