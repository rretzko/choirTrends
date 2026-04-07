<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\Newsletter as NewsletterMailable;
use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendNewsletter extends Command
{
    protected $signature = 'newsletter:send
        {--id= : The newsletter ID to send}
        {--preview : Send only to the founder for preview}
        {--dry-run : Show recipient count without sending}';

    protected $description = 'Send a newsletter email to all eligible users';

    public function handle(): int
    {
        $newsletterId = $this->option('id');
        $isDryRun = $this->option('dry-run');
        $isPreview = $this->option('preview');

        $newsletter = $newsletterId
            ? Newsletter::findOrFail($newsletterId)
            : Newsletter::query()->whereNull('sent_at')->latest()->first();

        if (! $newsletter) {
            $this->error('No unsent newsletter found. Create one in the Founder > Newsletter page or specify --id.');

            return self::FAILURE;
        }

        $subject = $newsletter->subject;
        $data = [
            'headline' => $newsletter->headline,
            'intro' => $newsletter->intro,
            'sections' => $newsletter->sections,
            'cta_text' => $newsletter->cta_text,
            'cta_url' => $newsletter->cta_url,
            'closing' => $newsletter->closing,
        ];

        $query = User::query()
            ->whereNotNull('email_verified_at');

        if ($isPreview) {
            $founderEmail = config('app.founder');
            $query->where('email', $founderEmail);
        }

        $users = $query->get();

        if ($isDryRun) {
            $this->info("Dry run: would send \"{$subject}\" to {$users->count()} user(s).");

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                Mail::to($user)->send(new NewsletterMailable(emailSubject: $subject, user: $user, newsletterData: $data));
                $sent++;
            } catch (\Throwable $e) {
                $this->error("Failed to send to {$user->email}: {$e->getMessage()}");
                $failed++;
            }
        }

        if (! $isPreview) {
            $newsletter->update([
                'sent_at' => now(),
                'recipient_count' => $sent,
            ]);
        }

        $this->info("Newsletter sent: {$sent}, failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
