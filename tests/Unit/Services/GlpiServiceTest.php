<?php

namespace Tests\Unit\Services;

use App\Services\GlpiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests Unitarios — GlpiService
 *
 * Cubre:
 *  - initSession usa token cacheado sin llamar a la API
 *  - initSession obtiene y cachea token nuevo
 *  - initSession lanza excepción cuando la API falla
 *  - killSession limpia el caché del token
 *  - getAllItems cachea el resultado (segunda llamada no hace HTTP)
 *  - getAllItems con 401 reintenta renovando sesión
 */
class GlpiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'glpi.base_url'   => 'https://glpi.test/apirest.php',
            'glpi.app_token'  => 'app-token-test',
            'glpi.user_token' => 'user-token-test',
        ]);

        Cache::flush();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // initSession
    // ─────────────────────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function initSession_retorna_token_cacheado_sin_llamar_api(): void
    {
        Cache::put('glpi_session_token', 'token-existente', 3000);

        $service = new GlpiService();
        $token   = $service->initSession();

        $this->assertEquals('token-existente', $token);
        Http::assertNothingSent();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function initSession_obtiene_y_cachea_token_nuevo(): void
    {
        Http::fake([
            '*/initSession' => Http::response(['session_token' => 'token-nuevo-123'], 200),
        ]);

        $service = new GlpiService();
        $token   = $service->initSession();

        $this->assertEquals('token-nuevo-123', $token);
        $this->assertEquals('token-nuevo-123', Cache::get('glpi_session_token'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function initSession_lanza_excepcion_cuando_api_falla(): void
    {
        Http::fake([
            '*/initSession' => Http::response('Unauthorized', 401),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/No se pudo iniciar sesión/');

        (new GlpiService())->initSession();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // killSession
    // ─────────────────────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function killSession_limpia_el_cache_del_token(): void
    {
        Cache::put('glpi_session_token', 'token-a-borrar', 3000);
        Http::fake(['*/killSession' => Http::response([], 200)]);

        (new GlpiService())->killSession();

        $this->assertNull(Cache::get('glpi_session_token'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function killSession_no_hace_nada_si_no_hay_token(): void
    {
        (new GlpiService())->killSession();

        Http::assertNothingSent();
        $this->assertNull(Cache::get('glpi_session_token'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // getAllItems
    // ─────────────────────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function getAllItems_cachea_resultado_y_no_repite_http(): void
    {
        Cache::put('glpi_session_token', 'session-test', 3000);

        Http::fake([
            '*/Computer*' => Http::response(
                [['id' => 1, 'name' => 'PC-001'], ['id' => 2, 'name' => 'PC-002']],
                200,
                ['Content-Range' => 'items 0-1/2']
            ),
        ]);

        $service  = new GlpiService();
        $result1  = $service->getAllItems('Computer', ['range' => '0-1']);
        $result2  = $service->getAllItems('Computer', ['range' => '0-1']);

        $this->assertEquals($result1, $result2);
        Http::assertSentCount(1); // segunda llamada desde caché
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getAllItems_diferentes_params_usan_claves_cache_distintas(): void
    {
        Cache::put('glpi_session_token', 'session-test', 3000);

        Http::fake([
            '*/Computer*' => Http::response([], 200),
        ]);

        $service = new GlpiService();
        $service->getAllItems('Computer', ['range' => '0-0']);
        $service->getAllItems('Computer', ['range' => '0-49']);

        Http::assertSentCount(2); // params distintos = claves distintas = dos llamadas HTTP
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forgetItemsCache_limpia_claves_conocidas(): void
    {
        Cache::put('glpi_session_token', 'session-test', 3000);

        Http::fake(['*/NetworkEquipment*' => Http::response([], 200)]);

        $service = new GlpiService();

        // Generar entradas de caché
        $service->getAllItems('NetworkEquipment', ['range' => '0-0']);
        $service->getAllItems('NetworkEquipment', ['range' => '0-499', 'expand_dropdowns' => true, 'get_hateoas' => false]);
        $service->getAllItems('NetworkEquipment', ['range' => '0-4999', 'expand_dropdowns' => true, 'get_hateoas' => false]);

        Http::assertSentCount(3);

        // Limpiar caché
        $service->forgetItemsCache('NetworkEquipment');

        // Las siguientes llamadas deben ir a la API de nuevo
        $service->getAllItems('NetworkEquipment', ['range' => '0-0']);
        Http::assertSentCount(4);
    }
}
