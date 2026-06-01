<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa un cliente MSP registrado en el sistema.
 *
 * Almacena la información maestra de cada cliente: nombre, identificadores,
 * correo electrónico, número de cuenta y ruta al logotipo.
 * La unión con reportes se hace mediante la clave natural `customer_name`.
 *
 * Tabla: msp_clients
 *
 * Relaciones:
 * - Tiene muchos {@see \App\Models\MspReport} mediante la clave natural `customer_name`.
 *
 * @property int         $id
 * @property string      $customer_name  Nombre del cliente. Clave natural usada en msp_reports.
 * @property string|null $customer_id    Identificador del cliente en la plataforma MSP.
 * @property string|null $email_cliente  Correo electrónico para envío de reportes.
 * @property string|null $numero_cuenta  Número de cuenta del cliente en el sistema.
 * @property string|null $logo_path      Ruta relativa en storage/app/public hacia el logotipo.
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MspClient extends Model
{
    /**
     * Campos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_name',
        'customer_id',
        'email_cliente',
        'numero_cuenta',
        'logo_path',
    ];

    // ─── Relación con reportes ────────────────────────────────────────────────

    /**
     * Relación: el cliente tiene muchos reportes MSP.
     *
     * La unión usa la clave natural `customer_name` tanto en esta tabla
     * como en `msp_reports`, en lugar de una clave foránea entera.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<MspReport>
     */
    public function reports()
    {
        return $this->hasMany(MspReport::class, 'customer_name', 'customer_name');
    }

    // ─── Obtener cliente o crear si no existe ─────────────────────────────────

    /**
     * Busca un cliente por nombre o lo crea si no existe.
     *
     * Aplica trim al nombre antes de la búsqueda para evitar duplicados
     * por espacios en blanco. Los valores de `$defaults` solo se asignan
     * en la creación, nunca actualizan un registro existente.
     *
     * @param  string               $name     Nombre del cliente (se aplica trim).
     * @param  array<string, mixed> $defaults Campos adicionales a establecer solo al crear.
     * @return static                         Instancia encontrada o recién creada.
     */
    public static function findOrCreateByName(string $name, array $defaults = []): self
    {
        return static::firstOrCreate(
            ['customer_name' => trim($name)],
            $defaults
        );
    }

    // ─── Actualizar solo campos no vacíos ─────────────────────────────────────

    /**
     * Actualiza la información del cliente ignorando campos vacíos o nulos.
     *
     * Si el cliente no existe lo crea. Solo aplica los campos de `$data`
     * que tengan un valor no nulo y no vacío, preservando los datos
     * existentes en el resto de columnas.
     *
     * @param  string               $customerName Nombre exacto del cliente.
     * @param  array<string, mixed> $data         Mapa campo → valor a actualizar.
     * @return static                             Instancia del cliente recargada desde la BD.
     */
    public static function updateInfo(string $customerName, array $data): self
    {
        $cliente = static::firstOrCreate(['customer_name' => trim($customerName)]);

        // Solo actualizar campos que vienen con valor
        $toUpdate = array_filter($data, fn($v) => $v !== null && $v !== '');
        if (!empty($toUpdate)) {
            $cliente->update($toUpdate);
        }

        return $cliente->fresh();
    }

    // ─── Obtener logo como base64 para PDF ───────────────────────────────────

    /**
     * Devuelve el logotipo del cliente codificado en base64 para embeber en PDF/HTML.
     *
     * Lee el archivo desde `storage/app/public/{logo_path}`, detecta su tipo MIME
     * y lo codifica como data URI. Retorna null si no hay logo configurado o si
     * el archivo no existe en disco.
     *
     * @return string|null Data URI en formato "data:{mime};base64,{contenido}"
     *                     o null si el logo no está disponible.
     */
    public function getLogoBase64(): ?string
    {
        if (!$this->logo_path) return null;

        $fullPath = storage_path('app/public/' . $this->logo_path);
        if (!file_exists($fullPath)) return null;

        $mime   = mime_content_type($fullPath);
        $base64 = base64_encode(file_get_contents($fullPath));
        return "data:{$mime};base64,{$base64}";
    }
}
