<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class SurveyInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'Rick Retzko, Founder'),
            subject: "We'd love your feedback — ChoirTrends",
        );
    }

    public function content(): Content
    {
        $remaining = 3 - $this->user->survey_emails_sent_count;

        return new Content(
            view: 'emails.survey-invitation',
            with: [
                'surveyUrl' => URL::signedRoute('survey.show', ['user' => $this->user->id]),
                'remainingEmails' => $remaining,
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
