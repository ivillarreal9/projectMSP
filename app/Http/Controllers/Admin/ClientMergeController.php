<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ClientMergeController extends Controller
{
    public function index()
    {
        return view('admin.client-merge.index');
    }

    public function process(Request $request)
    {
        $request->validate([
            'msp_file'  => 'required|file|mimes:xlsx,xls',
            'odoo_file' => 'required|file|mimes:xlsx,xls',
            'threshold' => 'nullable|integer|min:50|max:100',
        ]);

        $threshold = (int) $request->get('threshold', 80);

        try {
            $mspRows  = $this->readExcel($request->file('msp_file')->getPathname(),  'msp');
            $odooRows = $this->readExcel($request->file('odoo_file')->getPathname(), 'odoo');
        } catch (\Exception $e) {
            return back()->with('error', 'Error leyendo archivos: ' . $e->getMessage());
        }

        $result = $this->merge($mspRows, $odooRows, $threshold);

        $export = new class($result) implements FromArray, WithHeadings, ShouldAutoSize, WithStyles {
            public function __construct(private array $data) {}

            public function headings(): array
            {
                return [
                    'Customer ID (MSP)',
                    'Customer Name (MSP)',
                    'Número de Cuenta (ODOO)',
                    'RUC (ODOO)',
                    'Score de similitud',
                    'Matches encontrados',
                ];
            }

            public function array(): array
            {
                return array_map(fn($r) => [
                    $r['msp_id'],
                    $r['msp_nombre'],
                    $r['cuentas'],
                    $r['rucs'],
                    $r['score'] . '%',
                    $r['matches'],
                ], $this->data);
            }

            public function styles(Worksheet $sheet): array
            {
                $lastCol = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['argb' => 'FFFFFFFF'],
                        'size'  => 10,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF7C3AED'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Filas alternas
                if ($lastRow > 1) {
                    for ($row = 2; $row <= $lastRow; $row++) {
                        if ($row % 2 === 0) {
                            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                                'fill' => [
                                    'fillType'   => Fill::FILL_SOLID,
                                    'startColor' => ['argb' => 'FFF9FAFB'],
                                ],
                            ]);
                        }
                    }
                }

                $sheet->getRowDimension(1)->setRowHeight(22);

                return [];
            }
        };

        $filename = 'clientes-merge-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download($export, $filename);
    }

    // -------------------------------------------------------------------------
    // Leer Excel y devolver array limpio
    // -------------------------------------------------------------------------

    private function readExcel(string $path, string $type): array
    {
        $rows = [];

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet       = $spreadsheet->getActiveSheet();

        $firstRow = true;
        foreach ($sheet->getRowIterator() as $row) {
            if ($firstRow) { $firstRow = false; continue; } // skip header

            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = $cell->getValue();
            }

            if ($type === 'msp') {
                $nombre = trim($cells[0] ?? '');
                $id     = trim($cells[1] ?? '');
                if ($nombre) {
                    $rows[] = ['nombre' => $nombre, 'id' => $id];
                }
            } else {
                // ODOO: limpiar sufijo " - 04001090" del nombre
                $nombre_raw = trim($cells[0] ?? '');
                $cuenta     = trim($cells[1] ?? '');
                $ruc        = trim($cells[2] ?? '');
                $nombre     = preg_replace('/\s*-\s*\d{6,}.*$/', '', $nombre_raw);
                $nombre     = trim($nombre);
                if ($nombre) {
                    $rows[] = ['nombre' => $nombre, 'cuenta' => $cuenta, 'ruc' => $ruc];
                }
            }
        }

        return $rows;
    }

    // -------------------------------------------------------------------------
    // Fuzzy merge
    // -------------------------------------------------------------------------

    private function merge(array $mspRows, array $odooRows, int $threshold): array
    {
        $result = [];

        // Precalcular nombres ODOO en uppercase
        $odooClean = array_map(fn($r) => [
            'nombre'  => strtoupper($r['nombre']),
            'cuenta'  => $r['cuenta'],
            'ruc'     => $r['ruc'],
        ], $odooRows);

        foreach ($mspRows as $msp) {
            $mspNombre = strtoupper($msp['nombre']);
            $mspId     = $msp['id'];

            $matches = [];

            foreach ($odooClean as $odoo) {
                $score = $this->similarityScore($mspNombre, $odoo['nombre']);
                if ($score >= $threshold) {
                    $matches[] = [
                        'score'   => $score,
                        'nombre'  => $odoo['nombre'],
                        'cuenta'  => $odoo['cuenta'],
                        'ruc'     => $odoo['ruc'],
                    ];
                }
            }

            if (empty($matches)) {
                // Sin match — incluir igualmente con campos vacíos
                $result[] = [
                    'msp_id'     => $mspId,
                    'msp_nombre' => $msp['nombre'],
                    'cuentas'    => '',
                    'rucs'       => '',
                    'score'      => 0,
                    'matches'    => 0,
                ];
                continue;
            }

            // Ordenar por score desc
            usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);

            // Agrupar cuentas y RUCs únicos manteniendo orden
            $cuentas = array_values(array_unique(array_filter(array_column($matches, 'cuenta'))));
            $rucs    = array_values(array_unique(array_filter(array_column($matches, 'ruc'))));

            $result[] = [
                'msp_id'     => $mspId,
                'msp_nombre' => $msp['nombre'],
                'cuentas'    => implode(' | ', $cuentas),
                'rucs'       => implode(' | ', $rucs),
                'score'      => $matches[0]['score'],
                'matches'    => count($matches),
            ];
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Calcular similitud sin dependencias externas (similar_text de PHP)
    // -------------------------------------------------------------------------

    private function similarityScore(string $a, string $b): int
    {
        if ($a === $b) return 100;
        if (empty($a) || empty($b)) return 0;

        similar_text($a, $b, $percent);

        return (int) round($percent);
    }
}
