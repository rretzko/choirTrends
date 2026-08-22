<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('quicktips:send')->dailyAt('08:00')->timezone('America/New_York');
Schedule::command('stats:send-weekly')->weeklyOn(1, '7:30')->timezone('America/New_York');
// Rewritten from cron('30 7 8-14 * 1') — day-of-month and day-of-week were both restricted, which
// cron OR-combines rather than AND-combines, so it fired every Monday plus every 8th-14th, not just
// the second Monday.
Schedule::command('survey:send')
    ->dailyAt('07:30')
    ->timezone('America/New_York')
    ->when(function (): bool {
        $now = now()->timezone('America/New_York');

        return $now->isMonday() && $now->day >= 8 && $now->day <= 14;
    });

// Skips 2026 (already fired incorrectly due to cron OR-logic bug; idempotency guard covers future years).
Schedule::command('programs:nudge-spring')
    ->dailyAt('08:00')
    ->timezone('America/New_York')
    ->when(function (): bool {
        $now = now()->timezone('America/New_York');

        return $now->year > 2026
            && $now->month === 6
            && $now->isThursday()
            && $now->day <= 7;
    });
