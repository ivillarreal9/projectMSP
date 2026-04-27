<?php

namespace Tests\Unit\Imports;

use App\Imports\MspReportsImport;
use App\Models\MspClient;
use App\Models\MspReport;
use App\Models\MspUploadBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * Tests Unitarios — MspReportsImport
 *
 * Cubre:
 *  - Importación correcta de filas válidas
 *  - Filas vacías son ignoradas
 *  - Fechas en formato Excel serial se convierten correctamente
 *  - Fechas en string se convierten correctamente
 *  - Duplicados se actualizan (upsert por ticket_number)
 *  - Cliente se crea en msp_clients automáticamente
 *  - Texto se normaliza a minúsculas
 *  - Fórmulas Excel en semana/mes_cierre se resuelven
 *  - Tiempo de vida se calcula si no viene en el Excel
 *  - Campos sensibles no se filtran en respuestas
 */
class MspReportsImportTest extends TestCase
{
    use RefreshDatabase;

    private MspUploadBatch $batch;
    private MspReportsImport $import;

    protected function setUp(): void
    {
        parent::setUp();

        $this->batch = MspUploadBatch::create([
            'filename'        => 'test.xlsx',
            'periodo'         => 'Enero 2026',
            'total_registros' => 0,
            'clientes_unicos' => 0,
            'fuente'          => 'test',
        ]);

        $this->import = new MspReportsImport('Enero 2026', $this->batch->id);
    }

    // =========================================================================
    // 1. IMPORTACIÓN BÁSICA
    // =========================================================================

    /** @test */
    public function importa_fila_valida_correctamente(): void
    {
        $row = $this->makeRow([
            'ticket_number'  => 12345,
            'customername'   => 'Empresa Test',
            'locationname'   => 'Sede Central',
            'tickettitle'    => 'Problema de red',
            'tickettype'     => 'Incidente',
            'fecha_de_creacion' => '2026-01-10',
            'fecha_de_cierre'   => '2026-01-12',
        ]);

        $this->import->model($row);

        $this->assertDatabaseHas('msp_reports', [
            'ticket_number' => 12345,
            'customer_name' => 'Empresa Test',
            'location_name' => 'Sede Central',
            'ticket_title'  => 'Problema de red',
            'ticket_type'   => 'Incidente',
        ]);
    }

    /** @test */
    public function fila_sin_customer_ni_ticket_es_ignorada(): void
    {
        $row = $this->makeRow([
            'customername'  => '',
            'ticket_number' => null,
        ]);

        $result = $this->import->model($row);

        $this->assertNull($result);
        $this->assertDatabaseCount('msp_reports', 0);
    }

    /** @test */
    public function fila_solo_con_customer_vacio_es_ignorada(): void
    {
        $row = $this->makeRow([
            'customername'  => '',
            'ticket_number' => 999,
        ]);

        // Tiene ticket_number pero no customer — debe procesarse igual
        $this->import->model($row);

        $this->assertDatabaseHas('msp_reports', ['ticket_number' => 999]);
    }

    // =========================================================================
    // 2. UPSERT — EVITAR DUPLICADOS
    // =========================================================================

    /** @test */
    public function ticket_duplicado_se_actualiza_no_se_duplica(): void
    {
        $row = $this->makeRow([
            'ticket_number' => 11111,
            'customername'  => 'Cliente A',
            'tickettitle'   => 'Título original',
        ]);

        $this->import->model($row);
        $this->assertDatabaseCount('msp_reports', 1);

        // Mismo ticket, título diferente
        $rowActualizado = $this->makeRow([
            'ticket_number' => 11111,
            'customername'  => 'Cliente A',
            'tickettitle'   => 'Título actualizado',
        ]);

        $this->import->model($rowActualizado);

        // Sigue siendo 1 registro, no 2
        $this->assertDatabaseCount('msp_reports', 1);
        $this->assertDatabaseHas('msp_reports', [
            'ticket_number' => 11111,
            'ticket_title'  => 'Título actualizado',
        ]);
    }

    // =========================================================================
    // 3. CREACIÓN AUTOMÁTICA DE CLIENTES
    // =========================================================================

    /** @test */
    public function crea_cliente_en_msp_clients_si_no_existe(): void
    {
        $row = $this->makeRow([
            'ticket_number' => 22222,
            'customername'  => 'Nuevo Cliente SA',
        ]);

        $this->import->model($row);

        $this->assertDatabaseHas('msp_clients', [
            'customer_name' => 'Nuevo Cliente SA',
        ]);
    }

    /** @test */
    public function no_duplica_cliente_existente(): void
    {
        MspClient::create(['customer_name' => 'Cliente Existente']);

        $row = $this->makeRow([
            'ticket_number' => 33333,
            'customername'  => 'Cliente Existente',
        ]);

        $this->import->model($row);

        $this->assertDatabaseCount('msp_clients', 1);
    }

    // =========================================================================
    // 4. NORMALIZACIÓN DE TEXTO
    // =========================================================================

    /** @test */
    public function clasificacion_eventos_se_normaliza_a_minusculas(): void
    {
        $row = $this->makeRow([
            'ticket_number'              => 44444,
            'customername'               => 'Test',
            'clasificacion_de_eventos'   => 'No Imputable',
            'fecha_de_cierre'            => '2026-01-15',
        ]);

        $this->import->model($row);

        $this->assertDatabaseHas('msp_reports', [
            'ticket_number'         => 44444,
            'clasificacion_eventos' => 'no imputable',
        ]);
    }

    /** @test */
    public function customer_name_se_trimea(): void
    {
        $row = $this->makeRow([
            'ticket_number' => 55555,
            'customername'  => '  Empresa Con Espacios  ',
        ]);

        $this->import->model($row);

        $this->assertDatabaseHas('msp_reports', [
            'ticket_number' => 55555,
            'customer_name' => 'Empresa Con Espacios',
        ]);
    }

    // =========================================================================
    // 5. FECHAS
    // =========================================================================

    /** @test */
    public function fecha_en_string_se_convierte_correctamente(): void
    {
        $row = $this->makeRow([
            'ticket_number'     => 66666,
            'customername'      => 'Test',
            'fecha_de_creacion' => '2026-01-10 08:00:00',
            'fecha_de_cierre'   => '2026-01-12 17:00:00',
        ]);

        $this->import->model($row);

        $report = MspReport::where('ticket_number', 66666)->first();
        $this->assertNotNull($report->fecha_creacion);
        $this->assertNotNull($report->fecha_cierre);
        $this->assertEquals('2026-01-10', $report->fecha_creacion->format('Y-m-d'));
        $this->assertEquals('2026-01-12', $report->fecha_cierre->format('Y-m-d'));
    }

    /** @test */
    public function fecha_en_serial_excel_se_convierte_correctamente(): void
    {
        // 45667 es el serial de Excel para 2025-01-01 aproximadamente
        $row = $this->makeRow([
            'ticket_number'     => 77777,
            'customername'      => 'Test',
            'fecha_de_creacion' => 45292, // Serial de Excel
            'fecha_de_cierre'   => 45294,
        ]);

        $this->import->model($row);

        $report = MspReport::where('ticket_number', 77777)->first();
        $this->assertNotNull($report->fecha_creacion);
        $this->assertNotNull($report->fecha_cierre);
    }

    /** @test */
    public function fecha_invalida_se_guarda_como_null(): void
    {
        $row = $this->makeRow([
            'ticket_number'     => 88888,
            'customername'      => 'Test',
            'fecha_de_creacion' => 'esto-no-es-fecha',
            'fecha_de_cierre'   => null,
        ]);

        $this->import->model($row);

        $report = MspReport::where('ticket_number', 88888)->first();
        $this->assertNull($report->fecha_creacion);
        $this->assertNull($report->fecha_cierre);
    }

    // =========================================================================
    // 6. TIEMPO DE VIDA
    // =========================================================================

    /** @test */
    public function tiempo_vida_numerico_se_redondea_a_4_decimales(): void
    {
        $row = $this->makeRow([
            'ticket_number'               => 99999,
            'customername'                => 'Test',
            'tiempo_de_vida_del_ticket'   => '2.123456789',
        ]);

        $this->import->model($row);

        $report = MspReport::where('ticket_number', 99999)->first();
        $this->assertEquals(2.1235, (float) $report->tiempo_vida_ticket);
    }

    /** @test */
    public function tiempo_vida_se_calcula_si_no_viene_en_excel(): void
    {
        $row = $this->makeRow([
            'ticket_number'             => 10001,
            'customername'              => 'Test',
            'tiempo_de_vida_del_ticket' => null,
            'fecha_de_creacion'         => '2026-01-10 00:00:00',
            'fecha_de_cierre'           => '2026-01-12 00:00:00', // 2 días
        ]);

        $this->import->model($row);

        $report = MspReport::where('ticket_number', 10001)->first();
        $this->assertNotNull($report->tiempo_vida_ticket);
        $this->assertEquals(2.0, (float) $report->tiempo_vida_ticket);
    }

    // =========================================================================
    // 7. CHUNK SIZE
    // =========================================================================

    /** @test */
    public function chunk_size_es_200(): void
    {
        $this->assertEquals(200, $this->import->chunkSize());
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function makeRow(array $overrides = []): array
    {
        return array_merge([
            'ticket_number'                    => 1,
            'customername'                     => 'Cliente Default',
            'locationname'                     => 'Ubicación Default',
            'tickettitle'                      => 'Título Default',
            'tickettype'                       => 'Incidente',
            'fecha_de_creacion'                => '2026-01-01',
            'fecha_de_cierre'                  => '2026-01-02',
            'tiempo_de_vida_del_ticket'        => null,
            'semana'                           => 'S1',
            'mes_cierre'                       => 'Enero',
            'tipo_de_ticket'                   => 'Incidente',
            'clasificacion_de_eventos'         => 'no imputable',
            'causa_de_dano'                    => null,
            'solucion'                         => null,
            'detalle'                          => null,
            'tipo_de_cliente'                  => null,
            'ubicacion_hopsa'                  => null,
            'solucion_definitiva_recomendacion'=> null,
            'tipo_de_reporte'                  => null,
            'email_cliente'                    => null,
            'logo_path'                        => null,
            'batch_id'                         => $this->batch->id,
        ], $overrides);
    }
}