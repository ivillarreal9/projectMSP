<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Meta2Service;
use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;

class Meta2Controller extends Controller
{
    protected Meta2Service $meta2Service;

    public function __construct(Meta2Service $meta2Service)
    {
        $this->meta2Service = $meta2Service;
    }

    public function index(Request $request)
    {
        try {
            $search = $request->input('search', '');
            $month  = $request->input('month') ? (int)$request->input('month') : null;
            $year   = $request->input('year')  ? (int)$request->input('year')  : null;

            $tickets = $this->meta2Service->getTelefoniaTickets($search, $month, $year);

            $page    = $request->get('page', 1);
            $perPage = 10;
            $total   = count($tickets);
            $offset  = ($page - 1) * $perPage;

            $meta2 = new \Illuminate\Pagination\LengthAwarePaginator(
                array_slice($tickets, $offset, $perPage),
                $total,
                $perPage,
                $page,
                ['path' => route('admin.meta-2.index'), 'query' => $request->query()]
            );

            return view('admin.meta-2.index', compact('meta2'));

        } catch (\Exception $e) {
            return back()->with('error', 'Error al obtener datos: ' . $e->getMessage());
        }
    }

    public function exportPdf(Request $request)
    {
        $month = (int) $request->get('month');
        $year  = (int) $request->get('year');

        if (!$month || !$year) {
            return back()->with('error', 'Debes seleccionar mes y año.');
        }

        try {
            $data = $this->meta2Service->getPdfReportData($month, $year);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $html = view('admin.meta-2.pdf', $data)->render();

        $monthName = \Carbon\Carbon::createFromDate($year, $month, 1)
            ->locale('es')->monthName;

        $filename = "informe-meta2-{$monthName}-{$year}.pdf";
        $path     = storage_path("app/public/{$filename}");

        Browsershot::html($html)
            ->setChromePath(env('BROWSERSHOT_CHROME_PATH'))
            ->setNodeBinary(env('BROWSERSHOT_NODE_PATH'))
            ->setNpmBinary(env('BROWSERSHOT_NPM_PATH'))
            ->setNodeModulePath(base_path('node_modules'))
            ->newHeadlessMode()
            ->noSandbox()
            ->waitUntilNetworkIdle()
            ->paperSize(279.4, 215.9)
            ->landscape()
            ->margins(8, 8, 8, 8)
            ->savePdf($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    public function stream(Request $request)
    {
        $month  = (int) $request->get('month');
        $year   = (int) $request->get('year');
        $search = (string) $request->get('search', '');

        if (!$month || !$year) {
            abort(400, 'Mes y año requeridos.');
        }

        return response()->stream(function () use ($month, $year, $search) {

            // Limpiar todos los niveles de buffer antes de empezar SSE
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            $send = function (string $event, array $data) {
                echo "event: {$event}\n";
                echo 'data: ' . json_encode($data) . "\n\n";
                while (ob_get_level() > 0) {
                    ob_end_flush();
                }
                flush();
            };

            try {
                // Paso 1
                $send('step', ['step' => 1, 'message' => 'Obteniendo IDs del período...']);
                $ids = $this->meta2Service->getTelefoniaIds($month, $year);
                $send('step', ['step' => 1, 'message' => count($ids) . ' tickets encontrados.', 'done' => true]);

                if (empty($ids)) {
                    $send('done', ['html' => '', 'total' => 0]);
                    return;
                }

                // Paso 2
                $send('step', ['step' => 2, 'message' => 'Trayendo detalle de tickets...']);
                $tickets = $this->meta2Service->getTicketsByIdsPublic($ids, $search);
                $send('step', ['step' => 2, 'message' => count($tickets) . ' tickets cargados.', 'done' => true]);

                // Paso 3
                $send('step', ['step' => 3, 'message' => 'Cargando campos personalizados...']);
                $result = $this->meta2Service->attachCustomFields($tickets);
                $send('step', ['step' => 3, 'message' => 'Campos cargados.', 'done' => true]);

                // Resultado final
                $html = view('admin.meta-2._table', ['tickets' => $result])->render();
                $send('done', ['html' => $html, 'total' => count($result)]);

            } catch (\Throwable $e) {
                $send('error', [
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                ]);
            }

        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $month = (int) $request->get('month');
        $year  = (int) $request->get('year');

        if (!$month || !$year) {
            return back()->with('error', 'Debes seleccionar mes y año.');
        }

        $tickets = $this->meta2Service->getTelefoniaTickets('', $month, $year);

        if (empty($tickets)) {
            return back()->with('error', 'No hay tickets para exportar en ese período.');
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('META 2 - Telefonía');

        $fixedHeaders = ['Ticket #', 'Tipo', 'Creado', 'Completado'];

        $customKeys = [];
        foreach ($tickets as $ticket) {
            foreach (array_keys($ticket['custom_fields'] ?? []) as $key) {
                if (!in_array($key, ['ticketId', 'ticket_id']) && !in_array($key, $customKeys)) {
                    $customKeys[] = $key;
                }
            }
        }

        $headers = array_merge($fixedHeaders, $customKeys);

        foreach ($headers as $col => $header) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . '1';
            $sheet->setCellValue($cell, $header);
        }

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1E3A5F']],
            'alignment' => ['horizontal' => 'center'],
        ]);

        foreach ($tickets as $rowIndex => $ticket) {
            $row = $rowIndex + 2;

            $fixedValues = [
                $ticket['ticket_number']  ?? '',
                $ticket['issue_type']     ?? '',
                $ticket['created_date']   ?? '',
                $ticket['completed_date'] ?? '',
            ];

            $customValues = array_map(
                fn($key) => $ticket['custom_fields'][$key] ?? '',
                $customKeys
            );

            $values = array_merge($fixedValues, $customValues);

            foreach ($values as $col => $value) {
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . $row;
                $sheet->setCellValue($cell, $value);
            }

            if ($rowIndex % 2 === 0) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                    'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFF0F4FF']],
                ]);
            }
        }

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $monthName = \Carbon\Carbon::createFromDate($year, $month, 1)->locale('es')->monthName;
        $filename  = "meta2-telefonia-{$monthName}-{$year}.xlsx";

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}