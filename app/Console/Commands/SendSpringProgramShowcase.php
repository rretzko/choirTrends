<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Mail\SpringProgramShowcase;
use App\Models\Program;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SendSpringProgramShowcase extends Command
{
    protected $signature = 'programs:showcase-spring
        {program : Id of the featured program to showcase}
        {--from=2026-01-01 : Start of the spring window (event date)}
        {--to=2026-06-30 : End of the spring window (event date)}
        {--preview : Send only to the founder for preview}
        {--dry-run : Show recipient count without sending}';

    protected $description = 'Email directors with no spring program, showcasing a featured program to encourage uploads';

    public function handle(): int
    {
        $program = Program::query()->find($this->argument('program'));

        if (! $program) {
            $this->error("Program {$this->argument('program')} not found.");

            return self::FAILURE;
        }

        $from = Carbon::parse($this->option('from'))->startOfDay();
        $to = Carbon::parse($this->option('to'))->endOfDay();

        $query = User::query()
            ->whereNotNull('email_verified_at')
            ->where(function ($q): void {
                $q->whereNull('role')->orWhere('role', '!=', UserRole::Assistant->value);
            })
            ->whereDoesntHave('programs', function ($q) use ($from, $to): void {
                $q->whereBetween('event_date', [$from->toDateString(), $to->toDateString()]);
            });

        if ($this->option('preview')) {
            $query = User::query()->where('email', config('app.founder'));
        }

        $users = $query->get();

        if ($this->option('dry-run')) {
            $this->info("Dry run: would send to {$users->count()} user(s) with no program dated {$from->toDateString()} to {$to->toDateString()}.");

            return self::SUCCESS;
        }

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($users as $user) {
            $cacheKey = "spring_program_showcase:{$program->id}:{$user->id}";

            if (! $this->option('preview') && Cache::has($cacheKey)) {
                $skipped++;

                continue;
            }

            try {
                Mail::to($user)->queue(new SpringProgramShowcase($user, $program));

                if (! $this->option('preview')) {
                    Cache::put($cacheKey, true, now()->addYear());
                }

                $sent++;
            } catch (\Throwable $e) {
                $this->error("Failed to queue for {$user->email}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("Spring program showcase queued: {$sent}, skipped: {$skipped}, failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
