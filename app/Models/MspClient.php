<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MspClient extends Model
{
    protected $fillable = [
        'customer_name',
        'email_cliente',
        'numero_cuenta',
        'logo_path',
    ];

    // ─── Relación con reportes ────────────────────────────────────────────────
    public function reports()
    {
        return $this->hasMany(MspReport::class, 'customer_name', 'customer_name');
    }

    // ─── Obtener cliente o crear si no existe ─────────────────────────────────
    public static function findOrCreateByName(string $name, array $defaults = []): self
    {
        return static::firstOrCreate(
            ['customer_name' => trim($name)],
            $defaults
        );
    }

    // ─── Actualizar solo campos no vacíos ─────────────────────────────────────
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
