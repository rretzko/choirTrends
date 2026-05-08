<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\SurveyResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SurveyResponseReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SurveyResponse $surveyResponse) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Survey Response] from '.$this->surveyResponse->user->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.survey-response-received',
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
