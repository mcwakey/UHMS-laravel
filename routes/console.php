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

// Payment Timing Policy Phase 5 — expire due patient financial-risk profiles.
// Idempotent and bounded; overlap-protected. Changes no payment gate or visit.
Schedule::command('billing:financial-risk-expire')
    ->dailyAt('01:15')
    ->withoutOverlapping()
    ->onOneServer();

// Payment Timing Policy Phase 7 — expire due approved visit payment arrangements.
// Idempotent, overlap-protected; administrative only (changes no payment gate).
Schedule::command('billing:visit-payment-arrangement-expire --commit')
    ->dailyAt('01:20')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('billing:visit-financial-clearance-exception-expire --commit')
    ->dailyAt('01:25')
    ->withoutOverlapping()
    ->onOneServer();

// Quarterly attestation export (Jan / Apr / Jul / Oct, 03:00).
Schedule::command('permissions:export')
    ->cron('0 3 1 1,4,7,10 *')
    ->withoutOverlapping()
    ->onOneServer();

// Phase 9.6 — proactive journey handoff escalation + stale cleanup. Idempotent and
// bounded; gated by config and protected against overlapping runs.
if (config('journey.handoff_escalation.enabled', true)) {
    $journeyEvent = Schedule::command('journey:handoffs:escalate')
        ->withoutOverlapping()
        ->onOneServer();
    $frequency = (string) config('journey.handoff_escalation.frequency', 'everyFiveMinutes');
    method_exists($journeyEvent, $frequency) ? $journeyEvent->{$frequency}() : $journeyEvent->everyFiveMinutes();
}

// Phase 9.8 — daily aggregate analytics snapshot. Idempotent + non-overlapping.
if (config('journey.analytics_snapshot.enabled', true)) {
    Schedule::command('journey:analytics:snapshot')
        ->dailyAt((string) config('journey.analytics_snapshot.daily_time', '00:30'))
        ->withoutOverlapping()
        ->onOneServer();
}

// Phase 9.10 — capture predictions hourly, evaluate them daily (idempotent).
if (config('journey.prediction_accuracy.enabled', true)) {
    Schedule::command('journey:predictions:evaluate --capture')
        ->hourly()->withoutOverlapping()->onOneServer();
    Schedule::command('journey:predictions:evaluate --evaluate')
        ->dailyAt('01:00')->withoutOverlapping()->onOneServer();
}

if (config('admissions.reservations.auto_expiry_schedule_enabled', false)) {
    Schedule::command('admissions:expire-bed-reservations')
        ->everyFifteenMinutes()
        ->withoutOverlapping()
        ->onOneServer();
}
