<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MspCredential;
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
    public function index()
    {
        $credential = MspCredential::latest()->first();

        return view('admin.api-customers.index', compact('credential'));
    }

    public function fetch(Request $request)
    {
        $credential = MspCredential::latest()->first();

        if (!$credential) {
            return response()->json(['error' => 'Sin credenciales configuradas.'], 400);
        }

        $authHeader = 'Basic ' . base64_encode($credential->username . ':' . $credential->password);
        $baseUrl    = rtrim($credential->base_url, '/');

        $url = $baseUrl . '/customers?$OrderBy=StartDate desc&$select=CustomerName,CustomerId';

        $response = Http::withHeaders([
            'Authorization' => $authHeader,
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
        $credential = MspCredential::latest()->first();

        if (!$credential) {
            return back()->with('error', 'Sin credenciales configuradas.');
        }

        $authHeader = 'Basic ' . base64_encode($credential->username . ':' . $credential->password);
        $baseUrl    = rtrim($credential->base_url, '/');

        $url = $baseUrl . '/customers?$OrderBy=StartDate desc&$select=CustomerName,CustomerId';

        $response = Http::withHeaders([
            'Authorization' => $authHeader,
        ])->timeout(60)->get($url);

        if ($response->failed()) {
            return back()->with('error', "Error API [{$response->status()}]");
        }

        $data = $response->json('value') ?? [];

        // Export inline sin clase separada
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
