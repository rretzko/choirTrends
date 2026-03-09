<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\QuickTipStatus;
use App\Mail\QuickTipEmail;
use App\Models\QuickTip;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendQuickTips extends Command
{
    protected $signature = 'quicktips:send';

    protected $description = 'Send the next Quick Tip email to eligible users';

    public function handle(): int
    {
        $daysBetween = config('quicktip.days_between_tips');
        $sent = 0;
        $skipped = 0;

        $users = User::query()
            ->where('quick_tip_emails_enabled', true)
            ->get();

        foreach ($users as $user) {
            $latestPivot = $user->quickTips()
                ->wherePivotNotNull('emailed_at')
                ->orderByPivot('emailed_at', 'desc')
                ->first();

            if ($latestPivot) {
                /** @var string $emailedAt */
                $emailedAt = $latestPivot->pivot->getAttribute('emailed_at');
                $lastEmailed = Carbon::parse($emailedAt);
                if ($lastEmailed->diffInDays(now()) < $daysBetween) {
                    $skipped++;

                    continue;
                }
            } else {
                if ($user->created_at->diffInDays(now()) < $daysBetween) {
                    $skipped++;

                    continue;
                }
            }

            $sentTipIds = $user->quickTips()->pluck('quick_tips.id');

            $nextTip = QuickTip::query()
                ->whereIn('status', [QuickTipStatus::Pending, QuickTipStatus::Sent])
                ->whereNotIn('id', $sentTipIds)
                ->orderBy('sort_order')
                ->first();

            if (! $nextTip) {
                $skipped++;

                continue;
            }

            Mail::to($user)->queue(new QuickTipEmail($nextTip, $user));

            $user->quickTips()->attach($nextTip->id, [
                'emailed_at' => now(),
            ]);

            if ($nextTip->status === QuickTipStatus::Pending) {
                $nextTip->update(['status' => QuickTipStatus::Sent]);
            }

            $sent++;
        }

        $this->info("Quick Tips sent: {$sent}, skipped: {$skipped}");

        return self::SUCCESS;
    }
}
