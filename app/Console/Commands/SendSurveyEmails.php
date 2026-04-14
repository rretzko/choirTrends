<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\SurveyInvitation;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendSurveyEmails extends Command
{
    protected $signature = 'survey:send';

    protected $description = 'Send survey emails to users with no programs who registered at least two weeks ago';

    public function handle(): int
    {
        $sent = 0;
        $skipped = 0;

        $users = User::query()
            ->where('created_at', '<=', now()->subWeeks(2))
            ->where('survey_emails_sent_count', '<', 3)
            ->doesntHave('programs')
            ->doesntHave('surveyResponses')
            ->get();

        foreach ($users as $user) {
            Mail::to($user)->queue(new SurveyInvitation($user));

            $user->increment('survey_emails_sent_count');

            $sent++;
        }

        $this->info("Survey emails sent: {$sent}, skipped: {$skipped}");

        return self::SUCCESS;
    }
}
