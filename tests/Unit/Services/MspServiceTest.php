<?php

namespace Tests\Unit\Services;

use App\Services\MspService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests Unitarios — MspService
 *
 * Cubre:
 *  - updateCustomer usa método PUT (no PATCH)
 *  - updateCustomer envía CustomerName en el cuerpo
 *  - updateCustomer envía referenceId en el cuerpo
 *  - updateCustomer limpia el caché msp:customers:sync
 *  - updateCustomer lanza RuntimeException en error HTTP
 *  - fetchCustomers retorna desde caché sin llamar API
 */
class MspServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.msp.username' => 'test-user',
            'services.msp.password' => 'test-pass',
            'services.msp.base_url' => 'https://api.msp.test',
        ]);

        Cache::flush();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // updateCustomer
    // ─────────────────────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function updateCustomer_usa_metodo_PUT(): void
    {
        Http::fake([
            '*/customers/CUST-123' => Http::response([], 200),
        ]);

        (new MspService())->updateCustomer('CUST-123', 'Empresa ABC', 'REF-001');

        Http::assertSent(fn ($req) => $req->method() === 'PUT');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function updateCustomer_no_usa_metodo_PATCH(): void
    {
        Http::fake([
            '*/customers/CUST-123' => Http::response([], 200),
        ]);

        (new MspService())->updateCustomer('CUST-123', 'Empresa ABC', 'REF-001');

        Http::assertNotSent(fn ($req) => $req->method() === 'PATCH');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function updateCustomer_envia_CustomerName_en_el_cuerpo(): void
    {
        Http::fake([
            '*/customers/CUST-456' => Http::response([], 200),
        ]);

        (new MspService())->updateCustomer('CUST-456', 'Telecomunicaciones S.A.', 'TC-001');

        Http::assertSent(function ($req) {
            $body = $req->data();
            return isset($body['CustomerName'])
                && $body['CustomerName'] === 'Telecomunicaciones S.A.';
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function updateCustomer_envia_referenceId_en_el_cuerpo(): void
    {
        Http::fake([
            '*/customers/CUST-789' => Http::response([], 200),
        ]);

        (new MspService())->updateCustomer('CUST-789', 'Empresa XYZ', 'REF-XYZ-999');

        Http::assertSent(function ($req) {
            $body = $req->data();
            return isset($body['referenceId'])
                && $body['referenceId'] === 'REF-XYZ-999';
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function updateCustomer_limpia_cache_de_clientes_al_tener_exito(): void
    {
        Http::fake([
            '*/customers/CUST-123' => Http::response([], 200),
        ]);

        Cache::put('msp:customers:sync', [
            ['CustomerId' => 'CUST-123', 'CustomerName' => 'Empresa Vieja'],
        ], 3600);

        (new MspService())->updateCustomer('CUST-123', 'Empresa Nueva', 'REF-001');

        $this->assertNull(Cache::get('msp:customers:sync'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function updateCustomer_lanza_excepcion_en_error_400(): void
    {
        Http::fake([
            '*/customers/CUST-ERR' => Http::response(
                ['message' => 'CustomerName field is required'],
                400
            ),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Error MSP update \[400\]/');

        (new MspService())->updateCustomer('CUST-ERR', 'Empresa', 'REF-001');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function updateCustomer_no_limpia_cache_si_falla(): void
    {
        Http::fake([
            '*/customers/CUST-FAIL' => Http::response('Error', 500),
        ]);

        Cache::put('msp:customers:sync', [['CustomerId' => 'C1']], 3600);

        try {
            (new MspService())->updateCustomer('CUST-FAIL', 'Empresa', 'REF');
        } catch (\RuntimeException $e) {
            // Caché debe seguir intacto si la llamada falló
            $this->assertNotNull(Cache::get('msp:customers:sync'));
            return;
        }

        $this->fail('Debería haber lanzado RuntimeException');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // fetchCustomers
    // ─────────────────────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function fetchCustomers_retorna_desde_cache_sin_llamar_api(): void
    {
        $cached = [
            ['CustomerId' => 'C1', 'CustomerName' => 'Cliente Uno'],
            ['CustomerId' => 'C2', 'CustomerName' => 'Cliente Dos'],
        ];

        Cache::put('msp:customers:sync', $cached, 3600);

        $result = (new MspService())->fetchCustomers();

        Http::assertNothingSent();
        $this->assertEquals($cached, $result);
    }
}
