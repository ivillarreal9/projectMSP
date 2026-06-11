<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modelo que representa un ticket de soporte técnico MSP.
 *
 * Cada registro corresponde a un ticket importado desde Excel mediante
 * {@see \App\Imports\MspReportsImport}. Solo se almacenan tickets de tipo
 * "Incidente" o "Solicitud" (se excluyen cancelaciones, instalaciones e inspecciones).
 *
 * Tabla: msp_reports
 *
 * Relaciones:
 * - Pertenece a {@see \App\Models\MspClient} mediante la clave natural `customer_name`.
 *
 * @property int         $id
 * @property int|null    $ticket_number         Número único del ticket en la plataforma MSP.
 * @property string|null $customer_name         Nombre del cliente (clave natural hacia msp_clients).
 * @property string|null $location_name         Nombre de la ubicación o sede del cliente.
 * @property string|null $ticket_title          Título/descripción breve del ticket.
 * @property string|null $ticket_type           Tipo interno MSP (puede incluir subtipos como instalación).
 * @property \Carbon\Carbon|null $fecha_creacion Fecha y hora en que se abrió el ticket.
 * @property \Carbon\Carbon|null $fecha_cierre   Fecha y hora en que se cerró el ticket.
 * @property float|null  $tiempo_vida_ticket     Duración del ticket en días (4 decimales).
 * @property string|null $semana                Número de semana del año en que se cerró (ej. "S12").
 * @property string|null $mes_cierre            Mes en que se cerró el ticket (ej. "Marzo").
 * @property string|null $tipo_ticket           Clasificación principal: "Incidente" o "Solicitud".
 * @property string|null $clasificacion_eventos Categoría del evento (normalizada a minúsculas).
 * @property string|null $causa_dano            Causa raíz del daño o problema.
 * @property string|null $solucion              Solución aplicada al ticket.
 * @property string|null $detalle               Detalle adicional del ticket.
 * @property string|null $tipo_cliente          Clasificación del cliente (ej. "Corporativo", "Residencial").
 * @property string|null $ubicacion_hopsa       Nombre de ubicación específico para cliente HOPSA.
 * @property string|null $solucion_definitiva   Recomendación de solución definitiva.
 * @property string|null $tipo_reporte          Origen del reporte: "Alarma" o "Reportado".
 * @property string|null $email_cliente         Correo electrónico del cliente asociado al ticket.
 * @property string|null $logo_path             Ruta relativa al logo del cliente en storage.
 * @property string|null $periodo               Período del reporte en formato "Mes Año" (ej. "Marzo 2025").
 * @property string|null $numero_cuenta         Número de cuenta u orden del cliente.
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MspReport extends Model
{
    use HasFactory;

    /**
     * Campos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_number', 'customer_name', 'location_name', 'ticket_title',
        'ticket_type', 'fecha_creacion', 'fecha_cierre', 'tiempo_vida_ticket',
        'semana', 'mes_cierre', 'tipo_ticket', 'clasificacion_eventos',
        'causa_dano', 'solucion', 'detalle', 'tipo_cliente', 'ubicacion_hopsa',
        'solucion_definitiva', 'tipo_reporte', 'email_cliente', 'logo_path',
        'periodo','numero_cuenta',
    ];

    /**
     * Conversiones automáticas de atributos.
     *
     * - `fecha_creacion` / `fecha_cierre`: se convierten a instancias de Carbon.
     * - `tiempo_vida_ticket`: se trata como decimal con 4 decimales de precisión.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_cierre'   => 'datetime',
        'tiempo_vida_ticket' => 'decimal:4',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Scope que filtra tickets por nombre exacto de cliente.
     *
     * Uso: MspReport::forCustomer('Acme Corp')->get()
     *
     * @param  Builder $query    Constructor de consulta Eloquent.
     * @param  string  $customer Nombre exacto del cliente (campo customer_name).
     * @return Builder
     */
    public function scopeForCustomer(Builder $query, string $customer): Builder
    {
        return $query->where('customer_name', $customer);
    }

    /**
     * Scope que filtra tickets por período de reporte.
     *
     * Uso: MspReport::forPeriodo('Marzo 2025')->get()
     *
     * @param  Builder $query   Constructor de consulta Eloquent.
     * @param  string  $periodo Período en formato "Mes Año" (ej. "Marzo 2025").
     * @return Builder
     */
    public function scopeForPeriodo(Builder $query, string $periodo): Builder
    {
        return $query->where('periodo', $periodo);
    }

    // ─── Stats helpers ────────────────────────────────────────────────────────

    /**
     * Genera un resumen estadístico completo de tickets para un cliente dado.
     *
     * Considera únicamente tickets de tipo "Incidente" y "Solicitud", excluyendo
     * aquellos cuyo ticket_type contenga "cancelaci", "instalaci" o "inspecci".
     *
     * El normalizador interno unifica capitalización y espacios para evitar
     * duplicados en agrupaciones (ej. "No Imputable" == "no  imputable").
     *
     * @param  string      $customer Nombre exacto del cliente.
     * @param  string|null $periodo  Período a filtrar (ej. "Marzo 2025"). Si es null, incluye todos los períodos.
     * @return array{
     *   total_tickets: int,
     *   cant_incidentes: int,
     *   cant_solicitudes: int,
     *   tiempo_prom_incidentes: float,
     *   tiempo_prom_solicitudes: float,
     *   por_ubicacion_solicitudes: \Illuminate\Support\Collection,
     *   por_ubicacion_incidentes: \Illuminate\Support\Collection,
     *   por_clasificacion: \Illuminate\Support\Collection,
     *   alarma_vs_reportado: \Illuminate\Support\Collection,
     *   alarma_vs_reportado_semana: \Illuminate\Support\Collection,
     *   detalle_tickets: \Illuminate\Support\Collection
     * }
     */
    public static function statsForCustomer(string $customer, ?string $periodo = null): array
    {
        $q = static::forCustomer($customer);
        if ($periodo) $q->forPeriodo($periodo);

        // Solo Incidente y Solicitud, excluyendo ticket_type con cancelación/instalación/inspección
        $q->whereIn('tipo_ticket', ['Incidente', 'Solicitud'])
        ->where(function ($query) {
            $query->whereNull('ticket_type')
                    ->orWhere(function ($q2) {
                        $q2->whereRaw("LOWER(ticket_type) NOT LIKE '%cancelaci%'")
                        ->whereRaw("LOWER(ticket_type) NOT LIKE '%instalaci%'")
                        ->whereRaw("LOWER(ticket_type) NOT LIKE '%inspecci%'");
                    });
        });

        // CASE en vez de FIELD() (solo MySQL) para compatibilidad con SQLite (tests).
        // Equivalente: el whereIn ya limita tipo_ticket a estos dos valores.
        $tickets = $q->orderByRaw("CASE tipo_ticket WHEN 'Incidente' THEN 1 WHEN 'Solicitud' THEN 2 ELSE 3 END")->get();

        $incidentes  = $tickets->where('tipo_ticket', 'Incidente');
        $solicitudes = $tickets->where('tipo_ticket', 'Solicitud');

        // Normalizador: baja a minúsculas + trim para agrupar, luego capitaliza para mostrar
        $normalize = fn($v) => $v ? ucfirst(preg_replace('/\s+/', ' ', trim(mb_strtolower((string) $v)))) : 'Sin clasificar';

        return [
            'total_tickets'           => $tickets->count(),
            'cant_incidentes'         => $incidentes->count(),
            'cant_solicitudes'        => $solicitudes->count(),
            'tiempo_prom_incidentes'  => $incidentes->avg('tiempo_vida_ticket') ?? 0,
            'tiempo_prom_solicitudes' => $solicitudes->avg('tiempo_vida_ticket') ?? 0,

            'por_ubicacion_solicitudes' => $solicitudes->groupBy(fn($t) => $normalize($t->location_name))
                ->map(fn($g) => $g->count())->sortDesc(),

            'por_ubicacion_incidentes' => $incidentes->groupBy(fn($t) => $normalize($t->location_name))
                ->map(fn($g) => $g->count())->sortDesc(),

            'por_clasificacion' => $incidentes->groupBy(fn($t) => $normalize($t->clasificacion_eventos))
                ->map(fn($g) => $g->count()),

            'alarma_vs_reportado' => $tickets->groupBy(fn($t) => $normalize($t->tipo_reporte))
                ->map(fn($g) => $g->count()),

            'alarma_vs_reportado_semana' => $tickets->groupBy('semana')
                ->map(fn($g) => [
                    'Alarma'    => $g->filter(fn($t) => $normalize($t->tipo_reporte) === 'Alarma')->count(),
                    'Reportado' => $g->filter(fn($t) => $normalize($t->tipo_reporte) === 'Reportado')->count(),
                ]),

            'detalle_tickets' => $tickets->map(fn($t) => [
                'ticket'      => $t->ticket_number,
                'tipo'        => $t->tipo_ticket,
                'descripcion' => $t->ticket_title,
                'causa'       => $t->causa_dano,
                'solucion'    => $t->solucion,
            ])->values(),
        ];
    }

    /**
     * Obtiene la lista de clientes únicos que tienen tickets registrados.
     *
     * Primero obtiene los nombres de cliente distintos en `msp_reports` (filtrando
     * por período si se indica) y luego enriquece el resultado con los datos
     * completos almacenados en `msp_clients` (email, logo, número de cuenta).
     *
     * @param  string|null $periodo Período a filtrar (ej. "Marzo 2025"). Si es null, incluye todos los períodos.
     * @return array<int, array<string, mixed>> Lista de clientes ordenados alfabéticamente.
     */
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

    /**
     * Obtiene los períodos de reporte disponibles, ordenados del más reciente al más antiguo.
     *
     * Agrupa por `periodo`, toma el `MAX(created_at)` de cada grupo para determinar
     * el orden cronológico y descarta registros sin período asignado.
     *
     * @return array<int, string> Lista de períodos en formato "Mes Año" (ej. ["Marzo 2025", "Febrero 2025"]).
     */
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

    /**
     * Traduce el nombre de un período del inglés al español.
     *
     * Reemplaza los nombres de meses en inglés por sus equivalentes en español.
     * Ejemplo: "March 2025" → "Marzo 2025".
     *
     * @param  string $periodo Período en inglés (ej. "March 2025").
     * @return string Período traducido al español (ej. "Marzo 2025").
     */
    public static function translatePeriodo(string $periodo): string
    {
        $meses = [
            'January'   => 'Enero',   'February'  => 'Febrero',
            'March'     => 'Marzo',   'April'     => 'Abril',
            'May'       => 'Mayo',    'June'       => 'Junio',
            'July'      => 'Julio',   'August'    => 'Agosto',
            'September' => 'Septiembre', 'October' => 'Octubre',
            'November'  => 'Noviembre',  'December' => 'Diciembre',
        ];

        return str_replace(array_keys($meses), array_values($meses), $periodo);
    }

    /**
     * Relación: el reporte pertenece a un cliente MSP.
     *
     * La unión se realiza mediante la clave natural `customer_name` en lugar de
     * una clave foránea entera, ya que el nombre del cliente es el identificador
     * compartido entre ambas tablas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<MspClient, MspReport>
     */
    public function client()
    {
        return $this->belongsTo(MspClient::class, 'customer_name', 'customer_name');
    }
}
