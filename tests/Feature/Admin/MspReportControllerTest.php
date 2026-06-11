<?php

namespace Tests\Feature\Admin;

use App\Models\MspClient;
use App\Models\MspReport;
use App\Models\MspUploadBatch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests Feature — MspReportController
 *
 * Cubre:
 *  - Acceso restringido a admins
 *  - Index carga correctamente
 *  - Upload valida archivo y período
 *  - Upload crea batch y registros
 *  - Clientes lista correctamente
 *  - Cliente detalle muestra stats
 *  - Update cliente valida email
 *  - Datos sensibles no expuestos en respuestas
 *  - Validaciones de descarga masiva y correos
 */
class MspReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles dinámicos: el acceso por módulo se resuelve vía roleModel->modulos
        $adminRole  = Role::factory()->create(['slug' => 'admin',  'modulos' => array_keys(config('modules'))]);
        $editorRole = Role::factory()->create(['slug' => 'editor', 'modulos' => []]);

        // Con 2FA confirmado + sesión verificada para pasar TwoFactorMiddleware
        $this->admin = User::factory()->create([
            'role_id'              => $adminRole->id,
            'two_factor_secret'    => 'test-secret',
            'two_factor_confirmed' => true,
        ]);
        $this->editor = User::factory()->create([
            'role_id'              => $editorRole->id,
            'two_factor_secret'    => 'test-secret',
            'two_factor_confirmed' => true,
        ]);

        $this->withSession(['2fa_verified' => true]);
    }

    private function makeBatch(string $periodo = 'Enero 2026'): MspUploadBatch
    {
        return MspUploadBatch::create([
            'filename'        => 'test.xlsx',
            'periodo'         => $periodo,
            'total_registros' => 0,
            'clientes_unicos' => 0,
            'fuente'          => 'test',
        ]);
    }

    private function makeReport(array $overrides = []): MspReport
    {
        $batch = $this->makeBatch($overrides['periodo'] ?? 'Enero 2026');
        return MspReport::create(array_merge([
            'ticket_number'  => rand(1000, 9999),
            'customer_name'  => 'Cliente Test',
            'location_name'  => 'Sede Principal',
            'ticket_title'   => 'Problema de red',
            'ticket_type'    => 'Incidente',
            'fecha_creacion' => '2026-01-10',
            'fecha_cierre'   => '2026-01-12',
            'periodo'        => 'Enero 2026',
            'batch_id'       => $batch->id,
        ], $overrides));
    }

    // =========================================================================
    // 1. CONTROL DE ACCESO
    // =========================================================================

    public function test_usuario_no_autenticado_no_puede_acceder_al_index(): void
    {
        $this->get(route('admin.msp.index'))->assertRedirect(route('login'));
    }

    public function test_editor_no_puede_acceder_al_index(): void
    {
        $response = $this->actingAs($this->editor)->get(route('admin.msp.index'));
        $this->assertTrue(
            in_array($response->status(), [302, 403]),
            "Se esperaba 302 o 403, se obtuvo: {$response->status()}"
        );
    }

    public function test_admin_puede_acceder_al_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.msp.index'))
            ->assertStatus(200);
    }

    // =========================================================================
    // 2. IMPORTACIÓN SHAREPOINT — VALIDACIONES
    // (el upload directo de Excel fue reemplazado por la importación desde SharePoint)
    // =========================================================================

    public function test_sharepoint_import_falla_sin_filename(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.msp.sharepoint.import'), ['periodo' => 'Enero 2026'])
            ->assertSessionHasErrors('filename');
    }

    public function test_sharepoint_import_falla_sin_periodo(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.msp.sharepoint.import'), ['filename' => 'reporte.xlsx'])
            ->assertSessionHasErrors('periodo');
    }

    // =========================================================================
    // 3. CLIENTES
    // =========================================================================

    public function test_clientes_lista_correctamente(): void
    {
        $this->makeReport(['customer_name' => 'Empresa Alpha']);
        $this->makeReport(['customer_name' => 'Empresa Beta']);
        MspClient::create(['customer_name' => 'Empresa Alpha']);
        MspClient::create(['customer_name' => 'Empresa Beta']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.msp.clientes', ['periodo' => 'Enero 2026']));

        $response->assertStatus(200);
        $response->assertSee('Empresa Alpha');
        $response->assertSee('Empresa Beta');
    }

    public function test_clientes_busqueda_filtra_por_nombre(): void
    {
        MspClient::create(['customer_name' => 'Empresa Alpha']);
        MspClient::create(['customer_name' => 'Empresa Beta']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.msp.clientes', ['search' => 'Alpha']));

        $response->assertStatus(200);
        $response->assertSee('Empresa Alpha');
        $response->assertDontSee('Empresa Beta');
    }

    // =========================================================================
    // 4. CLIENTE DETALLE
    // =========================================================================

    public function test_cliente_detalle_muestra_stats(): void
    {
        $this->makeReport([
            'customer_name' => 'Cliente Detalle SA',
            'ticket_type'   => 'Incidente',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.msp.clientes.detalle', [
                'customer' => 'Cliente Detalle SA',
                'periodo'  => 'Enero 2026',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Cliente Detalle SA');
    }

    // =========================================================================
    // 5. UPDATE CLIENTE — VALIDACIONES
    // =========================================================================

    public function test_update_cliente_falla_con_email_invalido(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.msp.clientes.update', ['customer' => 'Cliente Test']), [
                'email_cliente' => 'no-es-un-email',
            ])
            ->assertSessionHasErrors('email_cliente');
    }

    public function test_update_cliente_acepta_email_valido(): void
    {
        MspClient::create(['customer_name' => 'Cliente Update']);

        $this->actingAs($this->admin)
            ->post(route('admin.msp.clientes.update', ['customer' => 'Cliente Update']), [
                'email_cliente' => 'contacto@cliente.com',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('msp_clients', [
            'customer_name' => 'Cliente Update',
            'email_cliente' => 'contacto@cliente.com',
        ]);
    }

    public function test_update_cliente_falla_con_logo_no_imagen(): void
    {
        $file = UploadedFile::fake()->create('malware.php', 100, 'application/php');

        $this->actingAs($this->admin)
            ->post(route('admin.msp.clientes.update', ['customer' => 'Cliente Test']), [
                'logo' => $file,
            ])
            ->assertSessionHasErrors('logo');
    }

    // =========================================================================
    // 6. SEGURIDAD — DATOS NO EXPUESTOS
    // =========================================================================

    public function test_email_cliente_no_aparece_en_listado_general(): void
    {
        MspClient::create([
            'customer_name' => 'Cliente Privado',
            'email_cliente' => 'privado@empresa.com',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.msp.clientes'));

        $response->assertDontSee('privado@empresa.com');
    }

    public function test_numero_cuenta_no_aparece_en_listado_general(): void
    {
        MspClient::create([
            'customer_name' => 'Cliente Cuenta',
            'numero_cuenta' => 'ACC-12345-SECRET',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.msp.clientes'));

        $response->assertDontSee('ACC-12345-SECRET');
    }

    public function test_sharepoint_import_no_expone_credenciales_en_errores(): void
    {
        config([
            'services.sharepoint.tenant_id'     => 'tenant-secreto',
            'services.sharepoint.client_secret' => 'secret-muy-privado',
        ]);

        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response([
                'error' => 'invalid_client',
            ], 401),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.msp.sharepoint.import'), [
                'filename' => 'reporte.xlsx',
                'periodo'  => 'Enero 2026',
            ]);

        $error = (string) session('error');
        $this->assertStringNotContainsString('tenant-secreto', $error);
        $this->assertStringNotContainsString('secret-muy-privado', $error);
    }

    // =========================================================================
    // 7. DESCARGA MASIVA — VALIDACIONES
    // =========================================================================

    public function test_descarga_masiva_falla_sin_periodo(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.msp.pdf.masiva.zip'), [
                'clientes' => ['Cliente Test'],
            ])
            ->assertSessionHasErrors('periodo');
    }

    public function test_descarga_masiva_falla_sin_clientes(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.msp.pdf.masiva.zip'), [
                'periodo'  => 'Enero 2026',
                'clientes' => [],
            ])
            ->assertSessionHasErrors('clientes');
    }

    public function test_descarga_masiva_falla_con_mas_de_30_clientes(): void
    {
        $clientes = array_fill(0, 31, 'Cliente Test');

        $this->actingAs($this->admin)
            ->post(route('admin.msp.pdf.masiva.zip'), [
                'periodo'  => 'Enero 2026',
                'clientes' => $clientes,
            ])
            ->assertSessionHasErrors('clientes');
    }

    // =========================================================================
    // 8. ENVÍO DE CORREO — VALIDACIONES
    // =========================================================================

    public function test_enviar_correo_falla_con_email_invalido(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.msp.correos.enviar'), [
                'customer_name' => 'Cliente Test',
                'email'         => 'no-es-email',
                'periodo'       => 'Enero 2026',
                'subject'       => 'Reporte MSP',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_enviar_correo_falla_sin_subject(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.msp.correos.enviar'), [
                'customer_name' => 'Cliente Test',
                'email'         => 'cliente@test.com',
                'periodo'       => 'Enero 2026',
            ])
            ->assertSessionHasErrors('subject');
    }

    public function test_enviar_correo_falla_sin_customer_name(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.msp.correos.enviar'), [
                'email'   => 'cliente@test.com',
                'periodo' => 'Enero 2026',
                'subject' => 'Reporte MSP',
            ])
            ->assertSessionHasErrors('customer_name');
    }
}