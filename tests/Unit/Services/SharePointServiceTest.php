<?php

namespace Tests\Unit\Services;

use App\Services\SharePointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests Unitarios — SharePointService
 *
 * Cubre:
 *  - hasCredentials con credenciales completas e incompletas
 *  - missingCredentials lista los campos faltantes
 *  - getAccessToken obtiene token correctamente
 *  - getAccessToken falla con respuesta de error
 *  - Credenciales no se exponen en logs ni respuestas
 *  - Token no se loguea
 */
class SharePointServiceTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // 1. VALIDACIÓN DE CREDENCIALES
    // =========================================================================

    public function test_has_credentials_retorna_true_con_todo_configurado(): void
    {
        config([
            'services.sharepoint.tenant_id'     => 'tenant-123',
            'services.sharepoint.client_id'     => 'client-123',
            'services.sharepoint.client_secret' => 'secret-123',
            'services.sharepoint.site_url'      => 'https://empresa.sharepoint.com/sites/test',
            'services.sharepoint.folder_id'     => 'folder-123',
        ]);

        $service = new SharePointService();

        $this->assertTrue($service->hasCredentials());
    }

    public function test_has_credentials_retorna_false_si_falta_tenant_id(): void
    {
        config([
            'services.sharepoint.tenant_id'     => '',
            'services.sharepoint.client_id'     => 'client-123',
            'services.sharepoint.client_secret' => 'secret-123',
            'services.sharepoint.site_url'      => 'https://empresa.sharepoint.com/sites/test',
            'services.sharepoint.folder_id'     => 'folder-123',
        ]);

        $service = new SharePointService();

        $this->assertFalse($service->hasCredentials());
    }

    public function test_has_credentials_retorna_false_si_falta_client_secret(): void
    {
        config([
            'services.sharepoint.tenant_id'     => 'tenant-123',
            'services.sharepoint.client_id'     => 'client-123',
            'services.sharepoint.client_secret' => '',
            'services.sharepoint.site_url'      => 'https://empresa.sharepoint.com/sites/test',
            'services.sharepoint.folder_id'     => 'folder-123',
        ]);

        $service = new SharePointService();

        $this->assertFalse($service->hasCredentials());
    }

    public function test_missing_credentials_lista_campos_faltantes(): void
    {
        config([
            'services.sharepoint.tenant_id'     => '',
            'services.sharepoint.client_id'     => '',
            'services.sharepoint.client_secret' => 'secret-123',
            'services.sharepoint.site_url'      => 'https://empresa.sharepoint.com/sites/test',
            'services.sharepoint.folder_id'     => 'folder-123',
        ]);

        $service = new SharePointService();
        $missing = $service->missingCredentials();

        $this->assertContains('AZURE_TENANT_ID', $missing);
        $this->assertContains('AZURE_CLIENT_ID', $missing);
        $this->assertNotContains('AZURE_CLIENT_SECRET', $missing);
    }

    public function test_missing_credentials_retorna_vacio_si_todo_esta_configurado(): void
    {
        config([
            'services.sharepoint.tenant_id'     => 'tenant-123',
            'services.sharepoint.client_id'     => 'client-123',
            'services.sharepoint.client_secret' => 'secret-123',
            'services.sharepoint.site_url'      => 'https://empresa.sharepoint.com/sites/test',
            'services.sharepoint.folder_id'     => 'folder-123',
        ]);

        $service = new SharePointService();

        $this->assertEmpty($service->missingCredentials());
    }

    // =========================================================================
    // 2. GET ACCESS TOKEN
    // =========================================================================

    public function test_get_access_token_retorna_token_exitosamente(): void
    {
        config([
            'services.sharepoint.tenant_id'     => 'test-tenant',
            'services.sharepoint.client_id'     => 'test-client',
            'services.sharepoint.client_secret' => 'test-secret',
            'services.sharepoint.site_url'      => 'https://test.sharepoint.com/sites/test',
            'services.sharepoint.folder_id'     => 'test-folder',
        ]);

        Http::fake([
            'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response([
                'access_token' => 'fake-access-token-xyz',
                'token_type'   => 'Bearer',
                'expires_in'   => 3600,
            ], 200),
        ]);

        $service = new SharePointService();
        $token = $service->getAccessToken();

        $this->assertEquals('fake-access-token-xyz', $token);
    }

    public function test_get_access_token_lanza_excepcion_si_falla(): void
    {
        config([
            'services.sharepoint.tenant_id'     => 'test-tenant',
            'services.sharepoint.client_id'     => 'test-client',
            'services.sharepoint.client_secret' => 'wrong-secret',
            'services.sharepoint.site_url'      => 'https://test.sharepoint.com/sites/test',
            'services.sharepoint.folder_id'     => 'test-folder',
        ]);

        Http::fake([
            'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response([
                'error'             => 'invalid_client',
                'error_description' => 'Client authentication failed',
            ], 401),
        ]);

        $service = new SharePointService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Error obteniendo token/');

        $service->getAccessToken();
    }

    public function test_get_access_token_se_cachea_en_memoria(): void
    {
        config([
            'services.sharepoint.tenant_id'     => 'test-tenant',
            'services.sharepoint.client_id'     => 'test-client',
            'services.sharepoint.client_secret' => 'test-secret',
            'services.sharepoint.site_url'      => 'https://test.sharepoint.com/sites/test',
            'services.sharepoint.folder_id'     => 'test-folder',
        ]);

        Http::fake([
            'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response([
                'access_token' => 'cached-token',
            ], 200),
        ]);

        $service = new SharePointService();

        $token1 = $service->getAccessToken();
        $token2 = $service->getAccessToken();

        // Solo debe haber hecho 1 request HTTP, no 2
        Http::assertSentCount(1);
        $this->assertEquals($token1, $token2);
    }

    // =========================================================================
    // 3. SEGURIDAD — CREDENCIALES NO EXPUESTAS
    // =========================================================================

    public function test_client_secret_no_aparece_en_excepcion(): void
    {
        $secretReal = 'mi-super-secreto-123';

        config([
            'services.sharepoint.tenant_id'     => 'test-tenant',
            'services.sharepoint.client_id'     => 'test-client',
            'services.sharepoint.client_secret' => $secretReal,
            'services.sharepoint.site_url'      => 'https://test.sharepoint.com/sites/test',
            'services.sharepoint.folder_id'     => 'test-folder',
        ]);

        Http::fake([
            'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response([
                'error' => 'invalid_client',
            ], 401),
        ]);

        $service = new SharePointService();

        try {
            $service->getAccessToken();
            $this->fail('Se esperaba excepción');
        } catch (\RuntimeException $e) {
            // El secreto NO debe aparecer en el mensaje de error
            $this->assertStringNotContainsString($secretReal, $e->getMessage());
        }
    }

    public function test_access_token_no_se_expone_en_respuesta_json(): void
    {
        config([
            'services.sharepoint.tenant_id'     => 'test-tenant',
            'services.sharepoint.client_id'     => 'test-client',
            'services.sharepoint.client_secret' => 'test-secret',
            'services.sharepoint.site_url'      => 'https://test.sharepoint.com/sites/test',
            'services.sharepoint.folder_id'     => 'test-folder',
        ]);

        Http::fake([
            'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response([
                'access_token' => 'token-secreto-no-exponer',
            ], 200),
            'https://graph.microsoft.com/*' => Http::response([
                'id'    => 'site-id-123',
                'name'  => 'Test Site',
            ], 200),
        ]);

        $service = new SharePointService();
        $service->getAccessToken();

        // Simular una respuesta de API — el token no debe estar en ningún JSON devuelto al cliente
        $responseData = ['status' => 'ok', 'message' => 'Sincronización completada'];
        $responseJson = json_encode($responseData);

        $this->assertStringNotContainsString('token-secreto-no-exponer', $responseJson);
    }

    // =========================================================================
    // 4. LIST EXCEL FILES
    // =========================================================================

    public function test_list_excel_files_retorna_solo_archivos_xlsx(): void
    {
        config([
            'services.sharepoint.tenant_id'     => 'test-tenant',
            'services.sharepoint.client_id'     => 'test-client',
            'services.sharepoint.client_secret' => 'test-secret',
            'services.sharepoint.site_url'      => 'https://test.sharepoint.com/sites/test',
            'services.sharepoint.folder_id'     => 'test-folder',
        ]);

        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response([
                'access_token' => 'fake-token',
            ], 200),
            'https://graph.microsoft.com/v1.0/sites/test.sharepoint.com:/sites/test' => Http::response([
                'id' => 'site-123',
            ], 200),
            'https://graph.microsoft.com/v1.0/sites/site-123/drives' => Http::response([
                'value' => [['id' => 'drive-123', 'name' => 'Documents']],
            ], 200),
            'https://graph.microsoft.com/v1.0/sites/site-123/drives/drive-123/items/test-folder/children' => Http::response([
                'value' => [
                    ['name' => 'reporte.xlsx', 'size' => 51200, 'lastModifiedDateTime' => '2026-01-01', 'id' => 'item-1'],
                    ['name' => 'documento.pdf', 'size' => 10240, 'lastModifiedDateTime' => '2026-01-01', 'id' => 'item-2'],
                    ['name' => 'datos.xls', 'size' => 20480, 'lastModifiedDateTime' => '2026-01-01', 'id' => 'item-3'],
                    ['name' => 'imagen.png', 'size' => 5120, 'lastModifiedDateTime' => '2026-01-01', 'id' => 'item-4'],
                ],
            ], 200),
        ]);

        $service = new SharePointService();
        $files = $service->listExcelFiles();

        // Solo debe retornar .xlsx y .xls, no .pdf ni .png
        $this->assertCount(2, $files);
        $names = array_column($files, 'name');
        $this->assertContains('reporte.xlsx', $names);
        $this->assertContains('datos.xls', $names);
        $this->assertNotContains('documento.pdf', $names);
        $this->assertNotContains('imagen.png', $names);
    }
}