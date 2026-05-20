<?php

namespace Tests\Unit\Services;

use App\Services\Meta2Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests Unitarios — Meta2Service
 *
 * Cubre:
 *  - getTelefoniaTickets retorna [] cuando faltan month/year
 *  - getTelefoniaIds cachea el resultado por mes/año
 *  - getTelefoniaTickets cachea el resultado completo
 *  - getCustomFieldsPool retorna [] para IDs vacíos
 *  - getCustomFieldsPool usa caché por ticket (no repite HTTP)
 *  - getPdfReportData retorna estructura vacía sin tickets
 */
class Meta2ServiceTest extends TestCase
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
    // getTelefoniaTickets — validaciones de entrada
    // ─────────────────────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function getTelefoniaTickets_retorna_vacio_sin_month_ni_year(): void
    {
        $result = (new Meta2Service())->getTelefoniaTickets(null, null, null);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
        Http::assertNothingSent();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getTelefoniaTickets_retorna_vacio_sin_year(): void
    {
        $result = (new Meta2Service())->getTelefoniaTickets(null, 5, null);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
        Http::assertNothingSent();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getTelefoniaTickets_retorna_vacio_sin_month(): void
    {
        $result = (new Meta2Service())->getTelefoniaTickets(null, null, 2026);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
        Http::assertNothingSent();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // getTelefoniaIds — caché
    // ─────────────────────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function getTelefoniaIds_cachea_por_mes_y_anio(): void
    {
        // La API devuelve { "value": [...] } — el servicio hace json('value')
        Http::fake([
            '*/ticketsview*' => Http::response(
                ['value' => [['TicketId' => 'T-001'], ['TicketId' => 'T-002']]],
                200
            ),
        ]);

        $service = new Meta2Service();
        $ids1    = $service->getTelefoniaIds(5, 2026);
        $ids2    = $service->getTelefoniaIds(5, 2026); // debe usar caché

        $this->assertEquals(['T-001', 'T-002'], $ids1);
        $this->assertEquals($ids1, $ids2);
        Http::assertSentCount(1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getTelefoniaIds_meses_distintos_usan_claves_distintas(): void
    {
        Http::fake([
            '*/ticketsview*' => Http::response(['value' => [['TicketId' => 'T-X']]], 200),
        ]);

        $service = new Meta2Service();
        $service->getTelefoniaIds(4, 2026);
        $service->getTelefoniaIds(5, 2026);

        Http::assertSentCount(2); // cada mes = clave distinta = 2 llamadas
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getTelefoniaIds_retorna_array_vacio_si_no_hay_tickets(): void
    {
        Http::fake([
            '*/ticketsview*' => Http::response(['value' => []], 200),
        ]);

        $ids = (new Meta2Service())->getTelefoniaIds(1, 2026);

        $this->assertIsArray($ids);
        $this->assertEmpty($ids);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // getTelefoniaTickets — caché completo
    // ─────────────────────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function getTelefoniaTickets_retorna_vacio_si_no_hay_ids(): void
    {
        Http::fake([
            '*/ticketsview*' => Http::response(['value' => []], 200),
        ]);

        $result = (new Meta2Service())->getTelefoniaTickets(null, 3, 2026);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getTelefoniaTickets_cachea_resultado_completo(): void
    {
        // La API envuelve los datos en { "value": [...] }
        Http::fake([
            '*/ticketsview*' => Http::sequence()
                ->push(['value' => [['TicketId' => 'T-111']]], 200) // getTelefoniaIds
                ->push(['value' => [
                    [
                        'TicketId'            => 'T-111',
                        'TicketNumber'        => 'TN-0001',
                        'TicketIssueTypeName' => 'Telefonía',
                        'CreatedDate'         => '2026-05-01T00:00:00Z',
                        'CompletedDate'       => '2026-05-10T00:00:00Z',
                    ],
                ]], 200), // getTicketsByIds
            '*/tickets/T-111/customfields*' => Http::response([], 200),
        ]);

        $service  = new Meta2Service();
        $result1  = $service->getTelefoniaTickets(null, 5, 2026);
        $result2  = $service->getTelefoniaTickets(null, 5, 2026); // desde caché

        $this->assertEquals($result1, $result2);
        $this->assertNotEmpty($result1);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Custom fields — caché por ticket
    // ─────────────────────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function custom_fields_en_cache_no_generan_llamada_http(): void
    {
        // Pre-cargar CF en caché
        Cache::put('meta2:cf:T-AAA', ['Provincia' => 'Panamá', 'Causa' => 'HW-001'], 86400);
        Cache::put('meta2:cf:T-BBB', ['Provincia' => 'Colón',  'Causa' => 'SW-002'], 86400);

        // IDs y tickets en caché también
        Cache::put('meta2:ids:2026:5', ['T-AAA', 'T-BBB'], 86400);
        Cache::put('meta2:tickets:' . md5('T-AAA,T-BBB'), [
            ['TicketId' => 'T-AAA', 'TicketNumber' => 'TN-0001', 'TicketIssueTypeName' => 'Telefonía', 'CreatedDate' => '2026-05-01T00:00:00Z', 'CompletedDate' => '2026-05-05T00:00:00Z'],
            ['TicketId' => 'T-BBB', 'TicketNumber' => 'TN-0002', 'TicketIssueTypeName' => 'Telefonía', 'CreatedDate' => '2026-05-02T00:00:00Z', 'CompletedDate' => '2026-05-06T00:00:00Z'],
        ], 86400);

        $result = (new Meta2Service())->getTelefoniaTickets(null, 5, 2026);

        Http::assertNothingSent(); // todo desde caché
        $this->assertCount(2, $result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // getPdfReportData
    // ─────────────────────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function getPdfReportData_retorna_estructura_basica_sin_tickets(): void
    {
        Http::fake([
            '*/ticketsview*' => Http::response(['value' => []], 200),
        ]);

        $data = (new Meta2Service())->getPdfReportData(3, 2026);

        $this->assertArrayHasKey('month', $data);
        $this->assertArrayHasKey('year', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertEquals(2026, $data['year']);
        $this->assertIsArray($data['summary']);
        $this->assertEmpty($data['summary']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getPdfReportData_cachea_por_mes_y_anio(): void
    {
        Http::fake([
            '*/ticketsview*' => Http::response(['value' => []], 200),
        ]);

        $service = new Meta2Service();
        $data1   = $service->getPdfReportData(3, 2026);
        $data2   = $service->getPdfReportData(3, 2026); // desde caché

        $this->assertEquals($data1, $data2);
        Http::assertSentCount(1);
    }
}
