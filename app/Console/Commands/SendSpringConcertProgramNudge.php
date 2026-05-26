<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\SpringConcertProgramNudge;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendSpringConcertProgramNudge extends Command
{
    protected $signature = 'programs:nudge-spring
        {--preview : Send only to the founder for preview}
        {--dry-run : Show recipient count without sending}';

    protected $description = 'Email verified users who have not uploaded a program since May 1st of the current year';

    public function handle(): int
    {
        $mayFirst = Carbon::create(now()->year, 5, 1)->startOfDay();

        $query = User::query()
            ->whereNotNull('email_verified_at')
            ->whereDoesntHave('programs', function ($q) use ($mayFirst): void {
                $q->where('created_at', '>=', $mayFirst);
            });

        if ($this->option('preview')) {
            $query->where('email', config('app.founder'));
        }

        $users = $query->get();

        if ($this->option('dry-run')) {
            $this->info("Dry run: would send to {$users->count()} user(s) with no program since {$mayFirst->toDateString()}.");

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                Mail::to($user)->queue(new SpringConcertProgramNudge($user));
                $sent++;
            } catch (\Throwable $e) {
                $this->error("Failed to queue for {$user->email}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("Spring concert program nudge queued: {$sent}, failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
