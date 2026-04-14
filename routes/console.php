<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('quicktips:send')->dailyAt('08:00')->timezone('America/New_York');
Schedule::command('survey:send')->cron('30 7 8-14 * 1')->timezone('America/New_York'); // second Monday of the month at 7:30 am Eastern
