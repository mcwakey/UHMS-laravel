<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly permissions audit — feeds /admin/permissions dashboard.
Schedule::command('permissions:audit --json')
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->onOneServer();

// Quarterly attestation export (Jan / Apr / Jul / Oct, 03:00).
Schedule::command('permissions:export')
    ->cron('0 3 1 1,4,7,10 *')
    ->withoutOverlapping()
    ->onOneServer();
