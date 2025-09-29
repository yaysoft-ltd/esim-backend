<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('airalo:sync-packages')->hourly();
Schedule::command('app:sync-airalo-global-package')->hourly();
Schedule::command('app:update-esim-activation-command')->everyFiveMinutes();


