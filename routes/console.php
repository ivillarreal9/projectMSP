<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sales:refresh-cache')->dailyAt('06:00');
Schedule::command('glpi:warm-cache')->everyThirtyMinutes();
Schedule::command('meraki:warm-cache')->everyThirtyMinutes();
Schedule::command('meraki:notify-licenses')->dailyAt('08:00');
