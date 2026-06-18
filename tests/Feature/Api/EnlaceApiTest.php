<?php

namespace Tests\Feature\Api;

use App\Models\EnlaceBatch;
use App\Models\EnlaceCarrier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Feature — EnlaceApiController
 *
 * Cubre:
 *  - Autenticación requerida (401 sin token)
 *  - GET /v1/enlaces retorna todos los circuitos
 *  - GET /v1/enlaces retorna total correcto
 *  - POST /v1/enlaces/by-country filtra por país
 *  - POST /v1/enlaces/by-country es case-insensitive
 *  - POST /v1/enlaces/by-country retorna 404 si país no existe
 *  - POST /v1/enlaces/by-country falla sin campo pais (422)
 *  - Campos sensibles no expuestos
 */
class EnlaceApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private EnlaceBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user  = User::factory()->create();
        $this->batch = EnlaceBatch::create([
            'filename'         => 'test-enlaces.xlsx',
            'total_registros'  => 0,
        ]);
    }

    private function token(): string
    {
        return $this->user->createToken('test')->plainTextToken;
    }

    private function makeCarrier(array $overrides = []): EnlaceCarrier
    {
        return EnlaceCarrier::create(array_merge([
            'batch_id'    => $this->batch->id,
            'cliente'     => 'Empresa Test',
            'pais'        => 'Guatemala',
            'carrier'     => 'Claro',
            'estado'      => 'activo',
            'id_circuito' => 'GT-' . uniqid(),
            'capacidad'   => 100,
        ], $overrides));
    }

    // =========================================================================
    // AUTENTICACIÓN
    // =========================================================================

    public function test_get_sin_token_retorna_401(): void
    {
        $this->getJson('/api/v1/enlaces')->assertStatus(401);
    }

    public function test_post_by_country_sin_token_retorna_401(): void
    {
        $this->postJson('/api/v1/enlaces/by-country', ['pais' => 'Guatemala'])
            ->assertStatus(401);
    }

    // =========================================================================
    // GET /v1/enlaces — todos los circuitos
    // =========================================================================

    public function test_get_retorna_todos_los_circuitos(): void
    {
        $this->makeCarrier(['pais' => 'Guatemala']);
        $this->makeCarrier(['pais' => 'El Salvador', 'id_circuito' => 'SV-001']);

        $this->withToken($this->token())
            ->getJson('/api/v1/enlaces')
            ->assertStatus(200)
            ->assertJsonStructure(['total', 'data'])
            ->assertJsonPath('total', 2);
    }

    public function test_get_retorna_estructura_correcta_por_circuito(): void
    {
        $this->makeCarrier([
            'cliente'     => 'Cliente Prueba',
            'pais'        => 'Guatemala',
            'carrier'     => 'Tigo',
            'id_circuito' => 'GT-TEST-001',
            'capacidad'   => 200,
            'estado'      => 'activo',
        ]);

        $response = $this->withToken($this->token())
            ->getJson('/api/v1/enlaces')
            ->assertStatus(200);

        $response->assertJsonPath('data.0.cliente', 'Cliente Prueba');
        $response->assertJsonPath('data.0.pais', 'Guatemala');
        $response->assertJsonPath('data.0.carrier', 'Tigo');
        $response->assertJsonPath('data.0.id_circuito', 'GT-TEST-001');
    }

    public function test_get_retorna_total_cero_sin_registros(): void
    {
        $this->withToken($this->token())
            ->getJson('/api/v1/enlaces')
            ->assertStatus(200)
            ->assertJsonPath('total', 0)
            ->assertJsonPath('data', []);
    }

    public function test_get_ordena_por_pais_y_cliente(): void
    {
        $this->makeCarrier(['pais' => 'Honduras',    'cliente' => 'Zeta Corp',  'id_circuito' => 'HN-001']);
        $this->makeCarrier(['pais' => 'El Salvador', 'cliente' => 'Alpha Corp', 'id_circuito' => 'SV-001']);
        $this->makeCarrier(['pais' => 'Guatemala',   'cliente' => 'Beta Corp',  'id_circuito' => 'GT-001']);

        $data = $this->withToken($this->token())
            ->getJson('/api/v1/enlaces')
            ->assertStatus(200)
            ->json('data');

        $this->assertEquals('El Salvador', $data[0]['pais']);
        $this->assertEquals('Guatemala',   $data[1]['pais']);
        $this->assertEquals('Honduras',    $data[2]['pais']);
    }

    // =========================================================================
    // POST /v1/enlaces/by-country — filtro por país
    // =========================================================================

    public function test_by_country_retorna_circuitos_del_pais(): void
    {
        $this->makeCarrier(['pais' => 'Guatemala',   'id_circuito' => 'GT-001']);
        $this->makeCarrier(['pais' => 'Guatemala',   'id_circuito' => 'GT-002']);
        $this->makeCarrier(['pais' => 'El Salvador', 'id_circuito' => 'SV-001']);

        $this->withToken($this->token())
            ->postJson('/api/v1/enlaces/by-country', ['pais' => 'Guatemala'])
            ->assertStatus(200)
            ->assertJsonPath('total', 2)
            ->assertJsonPath('pais', 'Guatemala');
    }

    public function test_by_country_es_case_insensitive(): void
    {
        $this->makeCarrier(['pais' => 'Guatemala', 'id_circuito' => 'GT-001']);

        $this->withToken($this->token())
            ->postJson('/api/v1/enlaces/by-country', ['pais' => 'GUATEMALA'])
            ->assertStatus(200)
            ->assertJsonPath('total', 1);

        $this->withToken($this->token())
            ->postJson('/api/v1/enlaces/by-country', ['pais' => 'guatemala'])
            ->assertStatus(200)
            ->assertJsonPath('total', 1);
    }

    public function test_by_country_retorna_404_si_pais_no_existe(): void
    {
        $this->makeCarrier(['pais' => 'Guatemala']);

        $this->withToken($this->token())
            ->postJson('/api/v1/enlaces/by-country', ['pais' => 'Francia'])
            ->assertStatus(404)
            ->assertJsonStructure(['message', 'total', 'data']);
    }

    public function test_by_country_falla_sin_campo_pais(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v1/enlaces/by-country', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['pais']);
    }

    public function test_by_country_falla_con_pais_vacio(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v1/enlaces/by-country', ['pais' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['pais']);
    }

    public function test_by_country_falla_si_pais_excede_100_caracteres(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v1/enlaces/by-country', ['pais' => str_repeat('A', 101)])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['pais']);
    }

    // =========================================================================
    // DATOS SENSIBLES
    // =========================================================================

    public function test_batch_id_no_se_expone_en_respuesta(): void
    {
        $this->makeCarrier();

        $response = $this->withToken($this->token())
            ->getJson('/api/v1/enlaces')
            ->assertStatus(200);

        $item = $response->json('data.0');
        $this->assertArrayNotHasKey('batch_id', $item);
    }
}
