<?php

namespace App\Services\Sales;

use Illuminate\Support\Facades\Cache;

/**
 * Servicio de cálculo de comisiones de vendedores sobre órdenes de venta en Odoo.
 *
 * Las comisiones se calculan sobre el campo cargo.order asociado a las órdenes de venta,
 * separadas en dos tipos:
 *   - OTF (One-Time Fee): cargo único por implementación o instalación
 *   - MRC (Monthly Recurring Charge): cargo mensual recurrente
 *
 * Las órdenes elegibles son las que tienen to_invoice_button_clicked=true y state != cancel.
 * La asignación de período (mes/año) se toma de los campos year_comission y month_comission
 * de la orden de venta (campos personalizados de la instancia Odoo de Ovnicom).
 *
 * Dependencias externas:
 *   - OdooService: ejecuta las llamadas JSON-RPC a Odoo
 *   - Modelo cargo.order de Odoo (personalizado): charge_type in [2,4,8] y cargo_type in [otf, mrc]
 */
class CommissionService
{
    /** TTL de caché para comisiones: 48 horas (datos de períodos cerrados cambian raramente). */
    const CACHE_TTL = 172800;

    /** Mapa de número de mes a nombre en español. */
    const MONTH_LABELS = [
        1 => 'Enero',    2 => 'Febrero',   3 => 'Marzo',     4 => 'Abril',
        5 => 'Mayo',     6 => 'Junio',     7 => 'Julio',     8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    /**
     * @param  OdooService $odoo Inyectado automáticamente por el contenedor de Laravel
     */
    public function __construct(private OdooService $odoo) {}

    // ── Cargos (OTF + MRC) ────────────────────────────────────

    /**
     * Obtiene los cargos OTF y MRC para un conjunto de órdenes de venta.
     *
     * Filtra por charge_type in [2,4,8] que corresponden a los tipos de cargo
     * comisionables según la configuración de Odoo de Ovnicom.
     * Los estados 'new' y 'anulado' se excluyen — solo cargos activos generan comisión.
     * El resultado se agrupa por order_id para facilitar la suma por orden.
     *
     * @param  array $orderIds Lista de IDs de órdenes de venta
     * @return array           Mapa ['otf' => Collection agrupada, 'mrc' => Collection agrupada]
     */
    private function getCargosByOrders(array $orderIds): array
    {
        if (empty($orderIds)) return ['otf' => collect(), 'mrc' => collect()];

        $cargos = $this->odoo->execute('cargo.order', 'search_read',
            [[
                ['order_id',    'in', $orderIds],
                ['charge_type', 'in', [2, 4, 8]],
                ['sub_state',   'not in', ['new', 'anulado']],
            ]],
            [
                'fields' => ['order_id', 'cargo_type', 'sub_amount'],
                'limit'  => 0,
            ]
        ) ?? [];

        $otf = collect($cargos)
            ->where('cargo_type', 'otf')
            ->groupBy(fn($c) => is_array($c['order_id']) ? $c['order_id'][0] : $c['order_id']);

        $mrc = collect($cargos)
            ->where('cargo_type', 'mrc')
            ->groupBy(fn($c) => is_array($c['order_id']) ? $c['order_id'][0] : $c['order_id']);

        return ['otf' => $otf, 'mrc' => $mrc];
    }

    // ── Por período (todos los vendedores) ────────────────────

    /**
     * Obtiene las comisiones de todos los vendedores para un mes y año específicos.
     *
     * Consulta las órdenes con year_comission = $year y month_comission = $month,
     * obtiene los cargos asociados, y delega el procesamiento a process() para
     * agrupar por vendedor y calcular totales.
     *
     * @param  string $year  Año en formato string (p.ej. '2024')
     * @param  string $month Mes en formato string (p.ej. '03')
     * @return array         Estructura procesada: records, cantidad, total_otf, total_mrc, total, by_vendedor
     */
    public function getByPeriod(string $year, string $month): array
    {
        $cacheKey = "commissions:period:{$year}:{$month}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($year, $month) {

            $records = $this->odoo->execute('sale.order', 'search_read',
                [[
                    ['year_comission',            '=', $year],
                    ['month_comission',           '=', $month],
                    ['to_invoice_button_clicked', '=', true],
                    ['state',                     '!=', 'cancel'],
                ]],
                [
                    'fields' => ['name', 'year_comission', 'month_comission',
                                 'state', 'user_id', 'amount_total',
                                 'partner_id', 'date_order'],
                    'order'  => 'date_order desc',
                    'limit'  => 0,
                ]
            ) ?? [];

            $orderIds = collect($records)->pluck('id')->all();
            $cargos   = $this->getCargosByOrders($orderIds);

            $records = collect($records)->map(function ($r) use ($cargos) {
                $id = $r['id'];
                $r['total_otf'] = ($cargos['otf'][$id] ?? collect())->sum('sub_amount');
                $r['total_mrc'] = ($cargos['mrc'][$id] ?? collect())->sum('sub_amount');
                return $r;
            })->all();

            return $this->process($records);
        });
    }

    // ── Por año (todos los vendedores) ────────────────────────

    /**
     * Obtiene las comisiones de todos los vendedores para un año completo.
     *
     * Similar a getByPeriod() pero sin filtro de mes — retorna todas las órdenes
     * del año. El resultado se agrupa y procesa igual con process().
     *
     * @param  string $year Año en formato string (p.ej. '2024')
     * @return array        Misma estructura que getByPeriod()
     */
    public function getByYear(string $year): array
    {
        $cacheKey = "commissions:year:{$year}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($year) {

            $records = $this->odoo->execute('sale.order', 'search_read',
                [[
                    ['year_comission',            '=', $year],
                    ['to_invoice_button_clicked', '=', true],
                    ['state',                     '!=', 'cancel'],
                ]],
                [
                    'fields' => ['name', 'year_comission', 'month_comission',
                                 'state', 'user_id', 'amount_total',
                                 'partner_id', 'date_order'],
                    'order'  => 'date_order desc',
                    'limit'  => 0,
                ]
            ) ?? [];

            $orderIds = collect($records)->pluck('id')->all();
            $cargos   = $this->getCargosByOrders($orderIds);

            $records = collect($records)->map(function ($r) use ($cargos) {
                $id = $r['id'];
                $r['total_otf'] = ($cargos['otf'][$id] ?? collect())->sum('sub_amount');
                $r['total_mrc'] = ($cargos['mrc'][$id] ?? collect())->sum('sub_amount');
                return $r;
            })->all();

            return $this->process($records);
        });
    }

    // ── Procesamiento ─────────────────────────────────────────

    /**
     * Procesa un array de órdenes de venta y genera la estructura de resumen de comisiones.
     *
     * Agrupa los registros por vendedor, suma OTF y MRC por grupo, y ordena
     * el resultado de mayor a menor comisión total (para el ranking de vendedores).
     * user_id en Odoo viene como [id, nombre] en search_read, por lo que
     * se maneja como array para extraer id y nombre.
     *
     * @param  array $records Órdenes de venta con total_otf y total_mrc ya calculados
     * @return array          Mapa con: records (raw), cantidad, total_otf, total_mrc, total, by_vendedor
     */
    private function process(array $records): array
    {
        $collection = collect($records);

        $byVendedor = $collection
            ->groupBy(fn($r) => is_array($r['user_id']) ? $r['user_id'][0] : ($r['user_id'] ?: 0))
            ->map(function ($group) {
                $uid          = $group->first()['user_id'];
                $vendedorId   = is_array($uid) ? $uid[0] : ($uid ?: 0);
                $vendedorName = is_array($uid) ? $uid[1] : '—';
                $totalOtf     = $group->sum('total_otf');
                $totalMrc     = $group->sum('total_mrc');

                return [
                    'vendedor_id'   => $vendedorId,
                    'vendedor_name' => $vendedorName,
                    'cantidad'      => $group->count(),
                    'total_otf'     => round($totalOtf, 2),
                    'total_mrc'     => round($totalMrc, 2),
                    'total'         => round($totalOtf + $totalMrc, 2),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $totalOtf = round($byVendedor->sum('total_otf'), 2);
        $totalMrc = round($byVendedor->sum('total_mrc'), 2);

        return [
            'records'     => $records,
            'cantidad'    => $collection->count(),
            'total_otf'   => $totalOtf,
            'total_mrc'   => $totalMrc,
            'total'       => round($totalOtf + $totalMrc, 2),
            'by_vendedor' => $byVendedor,
        ];
    }

    // ── Por vendedor / mes ────────────────────────────────────

    /**
     * Obtiene el detalle de comisiones de un vendedor específico para un mes.
     *
     * @param  int    $vendedorId ID del usuario vendedor en Odoo
     * @param  string $year       Año (p.ej. '2024')
     * @param  string $month      Mes (p.ej. '03')
     * @return array|null         Mapa con vendedor_id, vendedor, periodo, otf, mrc, total.
     *                            null si el vendedor no tiene registros en Odoo.
     */
    public function getForVendedorMonth(int $vendedorId, string $year, string $month): ?array
    {
        $cacheKey = "commissions:vendedor:{$vendedorId}:period:{$year}:{$month}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($vendedorId, $year, $month) {

            $records = $this->odoo->execute('sale.order', 'search_read',
                [[
                    ['user_id',                   '=', $vendedorId],
                    ['year_comission',            '=', $year],
                    ['month_comission',           '=', $month],
                    ['to_invoice_button_clicked', '=', true],
                    ['state',                     '!=', 'cancel'],
                ]],
                ['fields' => ['name', 'user_id', 'amount_total', 'date_order'], 'limit' => 0]
            ) ?? [];

            $vendedorName = $this->resolveVendedorName($records, $vendedorId);
            if ($vendedorName === null) return null;

            $orderIds  = collect($records)->pluck('id')->all();
            $cargos    = $this->getCargosByOrders($orderIds);
            $totalOtf  = $this->sumCargos($cargos['otf']);
            $totalMrc  = $this->sumCargos($cargos['mrc']);

            $periodo = ucfirst(
                \Carbon\Carbon::createFromDate((int) $year, (int) $month, 1)
                    ->locale('es')
                    ->translatedFormat('F Y')
            );

            return [
                'vendedor_id' => $vendedorId,
                'vendedor'    => $vendedorName,
                'periodo'     => $periodo,
                'otf'         => round($totalOtf, 2),
                'mrc'         => round($totalMrc, 2),
                'total'       => round($totalOtf + $totalMrc, 2),
            ];
        });
    }

    // ── Por vendedor / año ────────────────────────────────────

    /**
     * Obtiene el desglose mensual de comisiones de un vendedor para un año completo.
     *
     * Construye un array de 12 meses (incluso los meses sin ventas con OTF=0, MRC=0)
     * para facilitar la generación de gráficos de tendencia anual.
     *
     * @param  int    $vendedorId ID del usuario vendedor en Odoo
     * @param  string $year       Año (p.ej. '2024')
     * @return array|null         Mapa con vendedor_id, vendedor, year, meses (12 entradas), totales.
     *                            null si el vendedor no existe en Odoo.
     */
    public function getForVendedorYear(int $vendedorId, string $year): ?array
    {
        $cacheKey = "commissions:vendedor:{$vendedorId}:year:{$year}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($vendedorId, $year) {

            $records = $this->odoo->execute('sale.order', 'search_read',
                [[
                    ['user_id',                   '=', $vendedorId],
                    ['year_comission',            '=', $year],
                    ['to_invoice_button_clicked', '=', true],
                    ['state',                     '!=', 'cancel'],
                ]],
                ['fields' => ['name', 'user_id', 'month_comission', 'amount_total', 'date_order'], 'limit' => 0]
            ) ?? [];

            $vendedorName = $this->resolveVendedorName($records, $vendedorId);
            if ($vendedorName === null) return null;

            $orderIds      = collect($records)->pluck('id')->all();
            $cargos        = $this->getCargosByOrders($orderIds);
            $ordersByMonth = collect($records)->groupBy(fn($r) => (int) $r['month_comission']);

            $meses = [];
            for ($m = 1; $m <= 12; $m++) {
                $monthOrderIds = $ordersByMonth->get($m, collect())->pluck('id')->all();

                $otf = collect($monthOrderIds)
                    ->reduce(fn($c, $id) => $c + ($cargos['otf'][$id] ?? collect())->sum('sub_amount'), 0.0);
                $mrc = collect($monthOrderIds)
                    ->reduce(fn($c, $id) => $c + ($cargos['mrc'][$id] ?? collect())->sum('sub_amount'), 0.0);

                $meses[] = [
                    'mes'   => $m,
                    'label' => self::MONTH_LABELS[$m],
                    'otf'   => round($otf, 2),
                    'mrc'   => round($mrc, 2),
                    'total' => round($otf + $mrc, 2),
                ];
            }

            $totales = [
                'otf'   => round(array_sum(array_column($meses, 'otf')), 2),
                'mrc'   => round(array_sum(array_column($meses, 'mrc')), 2),
                'total' => round(array_sum(array_column($meses, 'total')), 2),
            ];

            return [
                'vendedor_id' => $vendedorId,
                'vendedor'    => $vendedorName,
                'year'        => (int) $year,
                'meses'       => $meses,
                'totales'     => $totales,
            ];
        });
    }

    // ── Helpers ───────────────────────────────────────────────

    /**
     * Resuelve el nombre del vendedor desde los registros o via consulta directa a Odoo.
     *
     * Intenta extraer el nombre del primer registro (user_id viene como [id, nombre]).
     * Si no hay registros (vendedor sin ventas en el período), hace un search_read
     * de res.users para obtener el nombre directamente.
     *
     * @param  array $records    Órdenes de venta del vendedor (puede estar vacío)
     * @param  int   $vendedorId ID del usuario en Odoo
     * @return string|null       Nombre del vendedor, o null si no existe en Odoo
     */
    private function resolveVendedorName(array $records, int $vendedorId): ?string
    {
        if (!empty($records)) {
            $uid = $records[0]['user_id'];
            if (is_array($uid)) return $uid[1];
        }

        $users = $this->odoo->execute('res.users', 'search_read',
            [[['id', '=', $vendedorId]]],
            ['fields' => ['name'], 'limit' => 1]
        ) ?? [];

        return $users[0]['name'] ?? null;
    }

    /**
     * Suma los montos sub_amount de todos los cargos en una colección agrupada por order_id.
     *
     * @param  \Illuminate\Support\Collection $grouped Colección agrupada de cargos (output de getCargosByOrders)
     * @return float                                   Suma total de todos los sub_amount
     */
    private function sumCargos(\Illuminate\Support\Collection $grouped): float
    {
        return $grouped->reduce(fn($carry, $group) => $carry + $group->sum('sub_amount'), 0.0);
    }

    // ── Caché ─────────────────────────────────────────────────

    /**
     * Invalida el caché de comisiones de un período específico.
     *
     * @param  string $year  Año del período a invalidar
     * @param  string $month Mes del período a invalidar
     * @return void
     */
    public function clearCache(string $year, string $month): void
    {
        Cache::forget("commissions:period:{$year}:{$month}");
    }

    /**
     * Invalida el caché de comisiones de un año completo.
     *
     * @param  string $year Año a invalidar
     * @return void
     */
    public function clearCacheYear(string $year): void
    {
        Cache::forget("commissions:year:{$year}");
    }

    /**
     * Expone el OdooService subyacente para uso desde el controlador.
     *
     * Permite al controlador acceder a métodos de OdooService (como getExecutives())
     * sin necesidad de inyectar dos servicios por separado.
     *
     * @return OdooService
     */
    public function odoo(): OdooService
    {
        return $this->odoo;
    }
}
