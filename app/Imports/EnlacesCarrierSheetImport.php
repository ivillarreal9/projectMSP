<?php

namespace App\Imports;

use App\Models\EnlaceCarrier;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

/**
 * Mapea las filas de UNA hoja del Excel de enlaces a modelos EnlaceCarrier.
 *
 * Cabeceras esperadas (fila 4, normalizadas a snake_case por maatwebsite/excel):
 *   pais, cliente_sitio, so_ref, id_circuito, carrier_operador, direccion,
 *   capacidad, gateway, ip_disponible, mascara, dns_primario, dns_secundario,
 *   contacto_tecnico, telefono, email, notas
 *
 * El matching es flexible (acepta variaciones es/en, con/sin tildes) para tolerar
 * cambios de nombre de columna entre versiones del archivo.
 */
class EnlacesCarrierSheetImport implements ToModel, WithHeadingRow, WithChunkReading, SkipsEmptyRows
{
    /**
     * @param int         $batchId
     * @param string|null $paisDefault País a usar si la fila no trae columna País
     *                                  (las hojas por país lo derivan del nombre de la hoja).
     */
    public function __construct(private int $batchId, private ?string $paisDefault = null) {}

    /** Las cabeceras reales están en la fila 4 (filas 1-3 = banners de título). */
    public function headingRow(): int
    {
        return 4;
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function model(array $row): ?EnlaceCarrier
    {
        $cliente = $this->col($row, ['cliente_sitio', 'cliente', 'client', 'sitio', 'nombre_cliente', 'customer', 'nombre']);

        if (empty($cliente)) {
            return null;
        }

        $capacidadRaw = $this->col($row, ['capacidad', 'capacity', 'ancho_de_banda', 'velocidad', 'mbps', 'mb', 'bandwidth', 'ancho_banda']);

        $estado = strtolower(trim($this->col($row, ['estado', 'status', 'estatus', 'state']) ?? 'activo'));
        if (!in_array($estado, ['activo', 'incidente', 'mantenimiento'])) {
            $estado = 'activo';
        }

        $idCircuito = $this->toStr($this->col($row, ['id_circuito', 'id circuito', 'circuito', 'circuit', 'circuit_id', 'id_de_circuito']));

        // Campos que provienen del Excel (se actualizan siempre en un upsert).
        $attrs = [
            'batch_id'           => $this->batchId,
            'cliente'            => $this->toStr($cliente),
            'ubicacion'          => $this->toStr($this->col($row, ['direccion', 'dirección', 'ubicacion', 'ubicación', 'address', 'location', 'sede'])),
            // Si conocemos el país por el nombre de la hoja, lo usamos siempre.
            // No leemos la columna País de la fila: puede traer texto de fórmula
            // (=_xlfn.LET…) cuando alguien pegó accidentalmente el Consolidado.
            'pais'               => $this->paisDefault
                                        ?? $this->toStr($this->col($row, ['pais', 'país', 'country', 'region', 'región'])),
            'carrier'            => $this->toStr($this->col($row, ['carrier_operador', 'carrier', 'operador', 'proveedor', 'isp', 'operadora'])),
            'so_ref'             => $this->toStr($this->col($row, ['so_ref', 'so', 'ref', 'referencia', 'orden', 'orden_de_servicio', 'service_order'])),
            'id_circuito'        => $idCircuito,
            'capacidad'          => $this->toMb($capacidadRaw),
            'gateway'            => $this->toStr($this->col($row, ['gateway', 'gw', 'puerta_de_enlace', 'gateway_ip'])),
            'ip_disponible'      => $this->toStr($this->col($row, ['ip_disponible', 'ip disponible', 'ip_asignada', 'ip_host', 'ip', 'available_ip'])),
            'mascara'            => $this->toStr($this->col($row, ['mascara', 'máscara', 'mask', 'subnet_mask', 'netmask', 'subred'])),
            'dns'                => $this->toStr($this->col($row, ['dns_primario', 'dns', 'dns_primary', 'primary_dns', 'nameserver', 'servidor_dns', 'dns_server'])),
            'dns_secundario'     => $this->toStr($this->col($row, ['dns_secundario', 'dns_secondary', 'secondary_dns', 'dns2', 'dns_2'])),
            'contacto_nombre'    => $this->toStr($this->col($row, ['contacto_tecnico', 'contacto', 'contact', 'contacto_nombre', 'nombre_contacto', 'nombre_del_contacto'])),
            'contacto_telefono'  => $this->toStr($this->col($row, ['telefono', 'teléfono', 'contacto_telefono', 'phone', 'tel', 'celular', 'movil'])),
            'contacto_email'     => $this->toStr($this->col($row, ['email', 'correo', 'contacto_email', 'email_contacto', 'mail', 'correo_electronico'])),
            'notas'              => $this->toStr($this->col($row, ['notas', 'nota', 'notes', 'observaciones', 'observacion', 'comentarios', 'comentario'])),
        ];

        // Sin ID de circuito no hay clave para emparejar → se inserta como nuevo.
        if (empty($idCircuito)) {
            $attrs['estado'] = $estado;
            return new EnlaceCarrier($attrs);
        }

        // Upsert atómico por ID de circuito (INSERT ... ON DUPLICATE KEY UPDATE).
        // A prueba de condiciones de carrera (auto-sync + import manual simultáneos)
        // gracias al índice único en id_circuito.
        // `estado` se incluye solo en los valores (se fija al insertar) pero NO en las
        // columnas a actualizar → si el registro ya existe, su estado se preserva.
        $updateColumns = array_values(array_diff(array_keys($attrs), ['id_circuito']));

        EnlaceCarrier::upsert(
            [array_merge($attrs, ['estado' => $estado])],
            ['id_circuito'],
            $updateColumns
        );

        return null; // ya persistido vía upsert
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function col(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            // Try exact key
            if (isset($row[$key]) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
            // Try normalized (remove accents, lowercase)
            $normalized = $this->normalize($key);
            foreach ($row as $rowKey => $value) {
                if ($this->normalize((string) $rowKey) === $normalized && $value !== null && $value !== '') {
                    return $value;
                }
            }
        }
        return null;
    }

    private function normalize(string $str): string
    {
        $str = mb_strtolower($str);
        $str = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $str);
        $str = preg_replace('/[^a-z0-9]+/', '_', $str);
        return trim($str, '_');
    }

    private function toStr(mixed $v): ?string
    {
        if ($v === null || $v === '') return null;
        $s = trim((string) $v);
        // Normaliza placeholders de "vacío" usados en el Excel (— / N/A / -)
        if ($s === '' || in_array(mb_strtolower($s), ['—', '-', '–', 'n/a', 'na', 'n.a.'])) {
            return null;
        }
        return $s;
    }

    /**
     * Convierte una capacidad a MB (entero). Tolera "100 MB", "1,024 MB (1 Gbps)",
     * "500MB", "1 Gbps", usando coma como separador de millares.
     */
    private function toMb(mixed $v): ?int
    {
        if ($v === null || $v === '') return null;
        $raw = (string) $v;

        // Quitar cualquier anotación entre paréntesis: "1,024 MB (1 Gbps)" → "1,024 MB"
        $clean = preg_replace('/\([^)]*\)/', '', $raw);

        // Detectar unidad GB sobre el texto ya sin paréntesis ("GB", "Gbps", "G b")
        $isGb = preg_match('/g\s*b/i', $clean) === 1;

        // Extraer solo dígitos y separadores
        $num = preg_replace('/[^\d.,]/', '', $clean);
        if ($num === '') return null;

        // Normalizar separadores: coma de millares vs coma decimal
        if (str_contains($num, ',') && str_contains($num, '.')) {
            $num = str_replace(',', '', $num);                       // coma = millares
        } elseif (str_contains($num, ',')) {
            $num = preg_match('/,\d{3}(\D|$)/', $num . ' ')
                ? str_replace(',', '', $num)                         // 1,024 → millares
                : str_replace(',', '.', $num);                       // 1,5  → decimal
        }

        if (!is_numeric($num)) return null;
        $val = (float) $num;

        return (int) round($isGb ? $val * 1024 : $val);
    }
}
