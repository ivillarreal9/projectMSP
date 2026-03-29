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

        // Renderizar la vista a HTML
        $html = view('admin.meta-2.pdf', $data)->render();

        $monthName = \Carbon\Carbon::createFromDate($year, $month, 1)
            ->locale('es')->monthName;

        $filename = "informe-meta2-{$monthName}-{$year}.pdf";
        $path     = storage_path("app/public/{$filename}");

        // Generar PDF con Browsershot
        Browsershot::html($html)
            ->setChromePath(env('BROWSERSHOT_CHROME_PATH'))
            ->setNodeBinary(env('BROWSERSHOT_NODE_PATH'))
            ->setNpmBinary(env('BROWSERSHOT_NPM_PATH'))
            ->setNodeModulePath(base_path('node_modules'))
            ->newHeadlessMode()
            ->noSandbox()
            ->waitUntilNetworkIdle()
            ->paperSize(279.4, 215.9) // 👈 Letter: 8.5" x 11" en mm
            ->landscape()
            ->margins(8, 8, 8, 8)
            ->savePdf($path);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    public function stream(Request $request)
        {
        $month = (int) $request->get('month');
        $year  = (int) $request->get('year');
        $search = $request->get('search', '');

        if (!$month || !$year) {
            abort(400, 'Mes y año requeridos.');
        }

        return response()->stream(function () use ($month, $year, $search) {

            $send = function (string $event, array $data) {
                echo "event: {$event}\n";
                echo 'data: ' . json_encode($data) . "\n\n";
                ob_flush();
                flush();
            };

            try {
                // ── Paso 1 ──────────────────────────────────────────
                $send('step', ['step' => 1, 'message' => 'Obteniendo IDs del período...']);
                $ids = $this->meta2Service->getTelefoniaIds($month, $year);

                if (empty($ids)) {
                    $send('done', ['html' => '<div class="empty">Sin tickets en ese período.</div>']);
                    return;
                }

                $send('step', ['step' => 1, 'message' => count($ids) . ' tickets encontrados.', 'done' => true]);

                // ── Paso 2 ──────────────────────────────────────────
                $send('step', ['step' => 2, 'message' => 'Trayendo detalle de tickets...']);
                $tickets = $this->meta2Service->getTicketsByIdsPublic($ids, $search);
                $send('step', ['step' => 2, 'message' => 'Detalle cargado.', 'done' => true]);

                // ── Paso 3 ──────────────────────────────────────────
                $send('step', ['step' => 3, 'message' => 'Cargando campos personalizados...']);
                $result = $this->meta2Service->attachCustomFields($tickets);
                $send('step', ['step' => 3, 'message' => 'Campos cargados.', 'done' => true]);

                // ── HTML final ──────────────────────────────────────
                $html = view('admin.meta-2._table', ['tickets' => $result])->render();
                $send('done', ['html' => $html]);

            } catch (\Exception $e) {
                $send('error', ['message' => $e->getMessage()]);
            }

        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',   // Nginx: desactiva buffer
        ]);
    }
}