<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// H-1 day reminders: once every morning.
Schedule::command('reminders:generate --type=day')
    ->dailyAt('08:00')
    ->withoutOverlapping();

// H-1 hour reminders: every hour, on the hour (non-overlapping 1-hour buckets).
Schedule::command('reminders:generate --type=hour')
    ->hourly()
    ->withoutOverlapping();

// Keep a rolling 60-day window of bookable slots for active vendors.
Schedule::command('slots:generate --days=60')
    ->dailyAt('02:00')
    ->withoutOverlapping();
