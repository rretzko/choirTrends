<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SurveyInvitationsSent extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{name: string, email: string, send_number: int}>  $recipients
     */
    public function __construct(public array $recipients) {}

    public function envelope(): Envelope
    {
        $count = count($this->recipients);

        return new Envelope(
            subject: '[Survey] '.($count === 1 ? '1 invitation sent' : "{$count} invitations sent"),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.survey-invitations-sent',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
