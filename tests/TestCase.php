<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class
        );
    }

    /**
     * Autentica al usuario con 2FA confirmado y sesión verificada,
     * para pasar TwoFactorMiddleware en tests de rutas protegidas.
     */
    protected function actingAsWith2fa(\App\Models\User $user): static
    {
        $user->forceFill([
            'two_factor_secret'    => 'test-secret',
            'two_factor_confirmed' => true,
        ])->save();

        return $this->withSession(['2fa_verified' => true])->actingAs($user);
    }
}