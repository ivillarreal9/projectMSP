<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de humo — páginas de autenticación
 *
 * Garantizan que toda ruta de auth con vista asociada renderiza (HTTP 200).
 * Origen: las vistas Breeze fueron eliminadas en su momento dejando las rutas
 * vivas, lo que producía errores 500 en producción (ej. "¿Olvidaste tu
 * contraseña?" del login). Estos tests evitan que vuelva a pasar.
 */
class AuthPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_de_registro_renderiza(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_pagina_de_recuperar_contrasena_renderiza(): void
    {
        $this->get('/forgot-password')->assertStatus(200);
    }

    public function test_pagina_de_restablecer_contrasena_renderiza(): void
    {
        $this->get('/reset-password/token-de-prueba')->assertStatus(200);
    }

    public function test_pagina_de_confirmar_contrasena_renderiza(): void
    {
        $user = User::factory()->create();

        $this->actingAsWith2fa($user)
            ->get('/confirm-password')
            ->assertStatus(200);
    }

    public function test_pagina_de_verificar_email_renderiza_para_usuario_no_verificado(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAsWith2fa($user)
            ->get('/verify-email')
            ->assertStatus(200);
    }

    public function test_pagina_de_verificar_email_redirige_si_ya_esta_verificado(): void
    {
        $user = User::factory()->create();

        $this->actingAsWith2fa($user)
            ->get('/verify-email')
            ->assertRedirect();
    }
}
