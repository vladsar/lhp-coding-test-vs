<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reminder emails (3 days / 24 hours before an event). Running hourly is
// enough: each attendee is reminded exactly once per window thanks to the
// marker columns, regardless of when the scheduler happens to run.
Schedule::command('events:send-reminders')->hourly();
