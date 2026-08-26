<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SongTitleOrigin;
use App\Mail\WeeklyStatsEmail;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\StatSnapshot;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendWeeklyStats extends Command
{
    protected $signature = 'stats:send-weekly';

    protected $description = 'Store a weekly platform stats snapshot and email it to the founder';

    public function handle(): int
    {
        $snapshot = StatSnapshot::create([
            'verified_users_count' => User::query()->whereNotNull('email_verified_at')->count(),
            'schools_count' => School::query()->count(),
            'programs_count' => Program::query()->count(),
            'song_titles_count' => SongTitle::query()->where('origin', SongTitleOrigin::Performed)->count(),
            'captured_at' => now(),
        ]);

        $comparisons = [
            'week' => $this->closestSnapshotBefore($snapshot, now()->subWeek()),
            'month' => $this->closestSnapshotBefore($snapshot, now()->subMonth()),
            'quarter' => $this->closestSnapshotBefore($snapshot, now()->subQuarter()),
            'year' => $this->closestSnapshotBefore($snapshot, now()->subYear()),
        ];

        $founderEmail = config('app.founder');

        if (! $founderEmail) {
            $this->warn('Snapshot stored, but app.founder is not configured — skipping email.');

            return self::SUCCESS;
        }

        Mail::to($founderEmail)->send(new WeeklyStatsEmail($snapshot, $comparisons));

        $this->info('Weekly stats snapshot stored and emailed.');

        return self::SUCCESS;
    }

    private function closestSnapshotBefore(StatSnapshot $current, Carbon $target): ?StatSnapshot
    {
        return StatSnapshot::query()
            ->where('id', '!=', $current->id)
            ->where('captured_at', '<=', $target)
            ->orderByDesc('captured_at')
            ->first();
    }
}
