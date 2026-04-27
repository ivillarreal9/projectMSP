<?php

namespace Tests\Unit\Models;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Unitarios — User Model
 *
 * Cubre:
 *  - Campos ocultos (password, remember_token)
 *  - Roles estáticos (isAdmin, isEditor, isVentas, hasRole)
 *  - Acceso a módulos dinámicos (canAccessModule)
 *  - Módulos accesibles por rol
 *  - Relación con Role model
 */
class UserModelTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeEditor(): User
    {
        return User::factory()->create(['role' => 'editor']);
    }

    private function makeVentas(): User
    {
        return User::factory()->create(['role' => 'ventas']);
    }

    // =========================================================================
    // 1. CAMPOS OCULTOS — SEGURIDAD
    // =========================================================================

    /** @test */
    public function el_password_no_se_incluye_al_serializar_el_usuario(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secreto123')]);

        $array = $user->toArray();
        $json  = $user->toJson();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertStringNotContainsString('password', $json);
    }

    /** @test */
    public function el_remember_token_no_se_incluye_al_serializar_el_usuario(): void
    {
        $user = User::factory()->create();
        $user->remember_token = 'token_privado_xyz';
        $user->save();

        $array = $user->toArray();
        $json  = $user->toJson();

        $this->assertArrayNotHasKey('remember_token', $array);
        $this->assertStringNotContainsString('remember_token', $json);
        $this->assertStringNotContainsString('token_privado_xyz', $json);
    }

    /** @test */
    public function el_password_se_guarda_como_hash_no_en_texto_plano(): void
    {
        $user = User::factory()->create(['password' => bcrypt('miPassword123')]);

        $this->assertNotEquals('miPassword123', $user->password);
        $this->assertTrue(password_verify('miPassword123', $user->password));
    }

    // =========================================================================
    // 2. ROLES ESTÁTICOS
    // =========================================================================

    /** @test */
    public function isAdmin_retorna_true_solo_para_admin(): void
    {
        $admin  = $this->makeAdmin();
        $editor = $this->makeEditor();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($editor->isAdmin());
    }

    /** @test */
    public function isEditor_retorna_true_solo_para_editor(): void
    {
        $editor = $this->makeEditor();
        $admin  = $this->makeAdmin();

        $this->assertTrue($editor->isEditor());
        $this->assertFalse($admin->isEditor());
    }

    /** @test */
    public function isVentas_retorna_true_solo_para_ventas(): void
    {
        $ventas = $this->makeVentas();
        $admin  = $this->makeAdmin();

        $this->assertTrue($ventas->isVentas());
        $this->assertFalse($admin->isVentas());
    }

    /** @test */
    public function hasRole_compara_el_rol_correctamente(): void
    {
        $user = User::factory()->create(['role' => 'ventas']);

        $this->assertTrue($user->hasRole('ventas'));
        $this->assertFalse($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('editor'));
    }

    /** @test */
    public function hasRole_es_sensible_a_mayusculas(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        // El rol 'Admin' (con mayúscula) NO debe coincidir con 'admin'
        $this->assertFalse($user->hasRole('Admin'));
        $this->assertFalse($user->hasRole('ADMIN'));
        $this->assertTrue($user->hasRole('admin'));
    }

    // =========================================================================
    // 3. MÓDULOS ACCESIBLES — ADMIN
    // =========================================================================

    /** @test */
    public function admin_tiene_acceso_a_todos_los_modulos(): void
    {
        $admin = $this->makeAdmin();

        $modulos = $admin->modulosAccesibles();

        $this->assertContains('msp_reports', $modulos);
        $this->assertContains('api_msp', $modulos);
        $this->assertContains('meta2', $modulos);
        $this->assertContains('encuestas', $modulos);
        $this->assertContains('usuarios', $modulos);
        $this->assertContains('sales', $modulos);
    }

    /** @test */
    public function admin_puede_acceder_a_cualquier_modulo_con_canAccessModule(): void
    {
        $admin = $this->makeAdmin();

        $this->assertTrue($admin->canAccessModule('msp_reports'));
        $this->assertTrue($admin->canAccessModule('usuarios'));
        $this->assertTrue($admin->canAccessModule('modulo_que_no_existe'));
    }

    // =========================================================================
    // 4. MÓDULOS ACCESIBLES — ROLES DINÁMICOS
    // =========================================================================

    /** @test */
    public function usuario_sin_role_model_no_puede_acceder_a_modulos(): void
    {
        $user = User::factory()->create(['role' => 'editor', 'role_id' => null]);

        $this->assertFalse($user->canAccessModule('msp_reports'));
        $this->assertFalse($user->canAccessModule('sales'));
    }

    /** @test */
    public function usuario_con_role_model_puede_acceder_a_modulos_asignados(): void
    {
        // Crear un rol con módulos específicos
        $role = Role::factory()->create([
            'modulos' => ['msp_reports', 'sales'],
        ]);

        $user = User::factory()->create([
            'role'    => 'editor',
            'role_id' => $role->id,
        ]);

        $this->assertTrue($user->canAccessModule('msp_reports'));
        $this->assertTrue($user->canAccessModule('sales'));
        $this->assertFalse($user->canAccessModule('usuarios'));
    }

    /** @test */
    public function modulosAccesibles_retorna_array_vacio_si_no_tiene_role_model(): void
    {
        $user = User::factory()->create(['role' => 'editor', 'role_id' => null]);

        $modulos = $user->modulosAccesibles();

        $this->assertIsArray($modulos);
        $this->assertEmpty($modulos);
    }

    // =========================================================================
    // 5. FILLABLE — PROTECCIÓN DE ASIGNACIÓN MASIVA
    // =========================================================================

    /** @test */
    public function solo_los_campos_fillable_se_pueden_asignar_masivamente(): void
    {
        $user = new User();

        $fillable = $user->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
        $this->assertContains('role', $fillable);
        $this->assertContains('role_id', $fillable);
    }

    /** @test */
    public function campos_sensibles_no_son_fillable(): void
    {
        $user = new User();

        $fillable = $user->getFillable();

        // Estos campos NO deben poder asignarse masivamente
        $this->assertNotContains('remember_token', $fillable);
        $this->assertNotContains('email_verified_at', $fillable);
    }
}