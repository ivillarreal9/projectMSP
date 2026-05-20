<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Registrar el driver de Azure para Laravel Socialite (solo si el paquete está instalado)
        if (class_exists(\SocialiteProviders\Manager\SocialiteWasCalled::class)) {
            Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
                $event->extendSocialite('azure', \SocialiteProviders\Azure\Provider::class);
            });
        }
    }
}
