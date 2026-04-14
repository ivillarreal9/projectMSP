<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class MspReport extends Model
{
    use HasFactory;
    protected $fillable = [
        'ticket_number', 'customer_name', 'location_name', 'ticket_title',
        'ticket_type', 'fecha_creacion', 'fecha_cierre', 'tiempo_vida_ticket',
        'semana', 'mes_cierre', 'tipo_ticket', 'clasificacion_eventos',
        'causa_dano', 'solucion', 'detalle', 'tipo_cliente', 'ubicacion_hopsa',
        'solucion_definitiva', 'tipo_reporte', 'email_cliente', 'logo_path',
        'periodo','numero_cuenta',
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_cierre'   => 'datetime',
        'tiempo_vida_ticket' => 'decimal:4',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForCustomer(Builder $query, string $customer): Builder
    {
        return $query->where('customer_name', $customer);
    }

    public function scopeForPeriodo(Builder $query, string $periodo): Builder
    {
        return $query->where('periodo', $periodo);
    }

    // ─── Stats helpers ────────────────────────────────────────────────────────

    public static function statsForCustomer(string $customer, ?string $periodo = null): array
    {
        $q = static::forCustomer($customer);
        if ($periodo) $q->forPeriodo($periodo);

        $tickets = $q->orderByRaw("FIELD(tipo_ticket, 'Incidente', 'Solicitud')")->get();

        $incidentes = $tickets->where('tipo_ticket', 'Incidente');
        $solicitudes = $tickets->where('tipo_ticket', 'Solicitud');

        return [
            'total_tickets'       => $tickets->count(),
            'cant_incidentes'     => $incidentes->count(),
            'cant_solicitudes'    => $solicitudes->count(),
            'tiempo_prom_incidentes' => $incidentes->avg('tiempo_vida_ticket') ?? 0,
            'tiempo_prom_solicitudes' => $solicitudes->avg('tiempo_vida_ticket') ?? 0,
            'por_ubicacion_solicitudes' => $solicitudes->groupBy('location_name')
                ->map(fn($g) => $g->count())->sortDesc(),
            'por_ubicacion_incidentes'  => $incidentes->groupBy('location_name')
                ->map(fn($g) => $g->count())->sortDesc(),
            'por_clasificacion'   => $incidentes->groupBy('clasificacion_eventos')
                ->map(fn($g) => $g->count()),
            'alarma_vs_reportado' => $tickets->groupBy('tipo_reporte')
                ->map(fn($g) => $g->count()),
            'alarma_vs_reportado_semana' => $tickets->groupBy('semana')
                ->map(fn($g) => [
                    'Alarma'    => $g->where('tipo_reporte', 'Alarma')->count(),
                    'Reportado' => $g->where('tipo_reporte', 'Reportado')->count(),
                ]),
            'detalle_tickets' => $tickets->map(fn($t) => [
                'ticket'       => $t->ticket_number,
                'tipo'         => $t->tipo_ticket,
                'descripcion'  => $t->ticket_title,
                'causa'        => $t->causa_dano,
                'solucion'     => $t->solucion,
            ])->values(),
        ];
    }

    public static function uniqueCustomers(?string $periodo = null): array
    {
        // Obtener nombres únicos del período
        $query = static::query()
            ->select('customer_name')
            ->distinct();

        if ($periodo) {
            $query->where('periodo', $periodo);
        }

        $customerNames = $query->pluck('customer_name');

        // Traer info desde msp_clients
        return MspClient::whereIn('customer_name', $customerNames)
            ->orderBy('customer_name')
            ->get()
            ->toArray();
    }

    public static function uniquePeriodos(): array
    {
        return static::query()
            ->select('periodo', \DB::raw('MAX(created_at) as last_created'))
            ->whereNotNull('periodo')
            ->groupBy('periodo')
            ->orderByDesc('last_created')
            ->pluck('periodo')
            ->toArray();
    }

    public function client()
    {
        return $this->belongsTo(MspClient::class, 'customer_name', 'customer_name');
    }
}
