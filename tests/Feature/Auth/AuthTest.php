<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email'    => 'test@ovnicom.com',
            'password' => bcrypt('password123'),
            'role'     => 'editor',
        ], $overrides));
    }

    private function attemptLogin(string $email, string $password): \Illuminate\Testing\TestResponse
    {
        return $this->post('/login', [
            'email'    => $email,
            'password' => $password,
            '_token'   => csrf_token(),
        ]);
    }

    public function test_la_pagina_de_login_carga_correctamente(): void
    {
        $this->get('/login')->assertStatus(200)->assertViewIs('auth.login');
    }

    public function test_usuario_autenticado_no_puede_ver_la_pagina_de_login(): void
    {
        $this->actingAs($this->makeUser())->get('/login')->assertRedirect();
    }

    public function test_usuario_puede_hacer_login_con_credenciales_validas(): void
    {
        $user = $this->makeUser();
        $this->attemptLogin('test@ovnicom.com', 'password123')
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_regenera_la_sesion_para_prevenir_session_fixation(): void
    {
        $this->makeUser();
        $before = session()->getId();
        $this->attemptLogin('test@ovnicom.com', 'password123');
        $this->assertNotEquals($before, session()->getId());
    }

    public function test_login_falla_con_password_incorrecto(): void
    {
        $this->makeUser();
        $this->attemptLogin('test@ovnicom.com', 'password_malo')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_falla_con_email_inexistente(): void
    {
        $this->attemptLogin('noexiste@ovnicom.com', 'password123')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_el_mensaje_de_error_no_revela_si_el_email_existe(): void
    {
        $this->attemptLogin('noexiste@ovnicom.com', 'password_malo');
        $errorMsg = session('errors')?->first('email') ?? '';
        $this->assertStringNotContainsStringIgnoringCase('no encontrado', $errorMsg);
        $this->assertStringNotContainsStringIgnoringCase('not found', $errorMsg);
    }

    public function test_login_falla_si_el_email_esta_vacio(): void
    {
        $this->post('/login', ['email' => '', 'password' => 'password123', '_token' => csrf_token()])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_falla_si_el_password_esta_vacio(): void
    {
        $this->post('/login', ['email' => 'test@ovnicom.com', 'password' => '', '_token' => csrf_token()])
            ->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_login_falla_si_el_email_no_tiene_formato_valido(): void
    {
        $this->post('/login', ['email' => 'esto-no-es-un-email', 'password' => 'password123', '_token' => csrf_token()])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_el_password_no_aparece_en_la_sesion_despues_del_login(): void
    {
        $this->makeUser();
        $this->attemptLogin('test@ovnicom.com', 'password123');
        $this->assertStringNotContainsString('password123', json_encode(session()->all()));
    }

    public function test_el_remember_token_no_se_expone_en_la_respuesta(): void
    {
        $user = $this->makeUser();
        $user->remember_token = 'token_secreto_123';
        $user->save();
        $response = $this->attemptLogin('test@ovnicom.com', 'password123');
        $this->assertStringNotContainsString('token_secreto_123', $response->getContent());
        $this->assertStringNotContainsString('remember_token', $response->getContent());
    }

    public function test_el_hash_del_password_no_aparece_en_sesion(): void
    {
        $user = $this->makeUser();
        $this->attemptLogin('test@ovnicom.com', 'password123');
        $this->assertStringNotContainsString($user->password, json_encode(session()->all()));
    }

    public function test_demasiados_intentos_de_login_activan_el_throttle(): void
    {
        $this->makeUser();
        RateLimiter::clear('login');
        for ($i = 0; $i < 5; $i++) {
            $this->attemptLogin('test@ovnicom.com', 'password_malo');
        }
        $response = $this->attemptLogin('test@ovnicom.com', 'password_malo');
        $response->assertSessionHasErrors('email');
        $errorMsg = session('errors')?->first('email') ?? '';
        $this->assertTrue(
            str_contains(strtolower($errorMsg), 'too many') ||
            str_contains(strtolower($errorMsg), 'seconds') ||
            str_contains(strtolower($errorMsg), 'demasiados') ||
            str_contains(strtolower($errorMsg), 'throttle') ||
            $errorMsg === trans('auth.throttle'),
            "Throttle no activado. Error: {$errorMsg}"
        );
    }

    public function test_usuario_puede_hacer_logout(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)
            ->post('/logout', ['_token' => csrf_token()])
            ->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_logout_invalida_la_sesion(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->post('/logout', ['_token' => csrf_token()]);
        $this->assertGuest();
    }

    public function test_logout_regenera_el_csrf_token(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);
        $before = csrf_token();
        $this->post('/logout', ['_token' => csrf_token()]);
        $this->assertNotEquals($before, csrf_token());
    }

    public function test_usuario_no_autenticado_es_redirigido_al_intentar_logout(): void
    {
        $this->post('/logout', ['_token' => csrf_token()])->assertRedirect('/login');
    }
}