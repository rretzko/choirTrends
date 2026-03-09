<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\QuickTip;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class QuickTipEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public QuickTip $quickTip, public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ChoirTrends Quick Tip: '.strip_tags($this->quickTip->header).' in 90 Seconds',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quick-tip',
            with: [
                'unsubscribeUrl' => URL::signedRoute('quick-tips.unsubscribe', ['user' => $this->user->id]),
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
