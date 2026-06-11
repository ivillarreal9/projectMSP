<?php

namespace Tests\Unit\Services;

use App\Services\MerakiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests Unitarios — MerakiService
 *
 * Cubre:
 *  - getLicenses devuelve [] cuando la org usa co-termination (400)
 *  - getLicenses lanza excepción en otros errores (500)
 *  - getLicenses cachea el resultado
 *  - flushAllDevicesCache limpia la clave global
 *  - getAllDevicesWithStatuses usa clave global (no por sesión)
 *  - warmCache no lanza excepción si un org falla
 */
class MerakiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'meraki.api_key'  => 'test-api-key',
            'meraki.base_url' => 'https://api.meraki.com/api/v1',
        ]);

        Cache::flush();

        // Cualquier petición no cubierta por Http::fake() debe fallar el test,
        // nunca salir a la API real de Meraki.
        Http::preventStrayRequests();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // getLicenses
    // ─────────────────────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function getLicenses_retorna_array_vacio_cuando_org_usa_co_termination(): void
    {
        Http::fake([
            // El sufijo * cubre el query string de paginación (?perPage=1000)
            '*/organizations/org-515892/licenses*' => Http::response(
                ['errors' => ['Organization with ID 515892 does not support per-device licensing']],
                400
            ),
        ]);

        $service = new MerakiService();
        $result  = $service->getLicenses('org-515892');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getLicenses_lanza_excepcion_en_error_500(): void
    {
        Http::fake([
            '*/organizations/org-bad/licenses*' => Http::response('Internal Server Error', 500),
        ]);

        Cache::forget('meraki_licenses_org-bad');

        $this->expectException(\Exception::class);

        (new MerakiService())->getLicenses('org-bad');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getLicenses_cachea_el_resultado(): void
    {
        Http::fake([
            '*/organizations/org-abc/licenses*' => Http::response(
                [['id' => 'lic-1', 'state' => 'active']],
                200
            ),
        ]);

        $service  = new MerakiService();
        $result1  = $service->getLicenses('org-abc');
        $result2  = $service->getLicenses('org-abc');

        $this->assertEquals($result1, $result2);
        Http::assertSentCount(1); // segunda llamada usa caché
    }

    // ─────────────────────────────────────────────────────────────────────────
    // flushAllDevicesCache
    // ─────────────────────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function flushAllDevicesCache_elimina_la_clave_global(): void
    {
        Cache::put('meraki_all_devices_global', [['serial' => 'ABC123']], 3600);

        (new MerakiService())->flushAllDevicesCache();

        $this->assertNull(Cache::get('meraki_all_devices_global'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getAllDevicesWithStatuses_usa_clave_global_no_por_sesion(): void
    {
        Http::fake([
            // El orden importa: los patrones más específicos van primero
            '*/organizations/org1/devices/statuses*' => Http::response([['serial' => 'Q2XY-1234', 'status' => 'online']], 200),
            '*/organizations/org1/devices*'          => Http::response([['serial' => 'Q2XY-1234', 'model' => 'MR36', 'name' => 'AP-Lobby']], 200),
            '*/organizations'                        => Http::response([['id' => 'org1', 'name' => 'Org 1']], 200),
        ]);

        (new MerakiService())->getAllDevicesWithStatuses();

        $this->assertNotNull(Cache::get('meraki_all_devices_global'));
        $this->assertNull(Cache::get('meraki_all_devices_' . session()->getId()));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // warmCache
    // ─────────────────────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function warmCache_no_lanza_excepcion_si_un_org_falla(): void
    {
        Http::fake([
            // El orden importa: los patrones más específicos van primero
            '*/organizations/org-ok/devices/statuses*' => Http::response([['serial' => 'S1', 'status' => 'online']], 200),
            '*/organizations/org-ok/devices*'          => Http::response([['serial' => 'S1', 'model' => 'MR36', 'name' => 'AP1']], 200),
            '*/organizations/org-ok/networks*'         => Http::response([], 200),
            '*/organizations/org-ok/uplinks/statuses*' => Http::response([], 200),
            '*/organizations/org-ok/licenses*'         => Http::response([], 200),
            '*/organizations/org-bad/*'                => Http::response('Error', 500),
            '*/organizations' => Http::response([
                ['id' => 'org-ok',  'name' => 'Org OK'],
                ['id' => 'org-bad', 'name' => 'Org Bad'],
            ], 200),
        ]);

        try {
            (new MerakiService())->warmCache();
            $this->assertTrue(true); // sin excepción = correcto
        } catch (\Throwable $e) {
            $this->fail('warmCache lanzó excepción: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // flushOrgCache
    // ─────────────────────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function flushOrgCache_elimina_todas_las_claves_de_la_org(): void
    {
        $orgId = 'org-test';
        Cache::put("meraki_org_{$orgId}", ['id' => $orgId], 3600);
        Cache::put("meraki_networks_{$orgId}", [], 3600);
        Cache::put("meraki_devices_{$orgId}", [], 3600);
        Cache::put("meraki_device_statuses_{$orgId}", [], 3600);
        Cache::put("meraki_uplink_statuses_{$orgId}", [], 3600);

        (new MerakiService())->flushOrgCache($orgId);

        $this->assertNull(Cache::get("meraki_org_{$orgId}"));
        $this->assertNull(Cache::get("meraki_networks_{$orgId}"));
        $this->assertNull(Cache::get("meraki_devices_{$orgId}"));
        $this->assertNull(Cache::get("meraki_device_statuses_{$orgId}"));
        $this->assertNull(Cache::get("meraki_uplink_statuses_{$orgId}"));
    }
}
