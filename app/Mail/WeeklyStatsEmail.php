<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\StatSnapshot;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyStatsEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, StatSnapshot|null>  $comparisons
     */
    public function __construct(public StatSnapshot $snapshot, public array $comparisons) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ChoirTrends Stats',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-stats',
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
