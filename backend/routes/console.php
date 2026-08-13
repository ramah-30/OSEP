<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Deliver scheduled invitations/reminders as they fall due. Requires a host cron
// running `php artisan schedule:run` every minute.
Schedule::command('osep:dispatch-reminders')->everyMinute()->withoutOverlapping();
