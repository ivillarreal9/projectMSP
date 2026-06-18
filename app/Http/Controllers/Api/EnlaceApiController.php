<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EnlaceCarrier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnlaceApiController extends Controller
{
    /**
     * GET /v1/enlaces
     * Retorna todos los circuitos carrier registrados.
     */
    public function index(): JsonResponse
    {
        $enlaces = EnlaceCarrier::orderBy('pais')->orderBy('cliente')->get($this->campos());

        return response()->json([
            'total'   => $enlaces->count(),
            'data'    => $enlaces,
        ]);
    }

    /**
     * POST /v1/enlaces/by-country
     * Retorna los circuitos de un país específico.
     *
     * Body: { "pais": "Guatemala" }
     */
    public function byCountry(Request $request): JsonResponse
    {
        $request->validate([
            'pais' => 'required|string|max:100',
        ]);

        $pais    = trim($request->input('pais'));
        $enlaces = EnlaceCarrier::whereRaw('LOWER(pais) = ?', [mb_strtolower($pais)])
            ->orderBy('cliente')
            ->get($this->campos());

        if ($enlaces->isEmpty()) {
            return response()->json([
                'message' => "No se encontraron circuitos para el país \"{$pais}\".",
                'total'   => 0,
                'data'    => [],
            ], 404);
        }

        return response()->json([
            'pais'  => $pais,
            'total' => $enlaces->count(),
            'data'  => $enlaces,
        ]);
    }

    private function campos(): array
    {
        return [
            'id', 'pais', 'cliente', 'carrier', 'estado',
            'id_circuito', 'so_ref', 'capacidad', 'ubicacion',
            'gateway', 'ip_disponible', 'mascara',
            'dns', 'dns_secundario',
            'contacto_nombre', 'contacto_telefono', 'contacto_email',
            'notas',
        ];
    }
}
