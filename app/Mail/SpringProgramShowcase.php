<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Ensemble;
use App\Models\Program;
use App\Models\SongTitle;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SpringProgramShowcase extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public Program $program) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'Rick Retzko, ChoirTrends'),
            subject: 'Is this your concert program?',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.spring-program-showcase',
            with: [
                'firstName' => explode(' ', $this->user->name)[0],
                'programName' => $this->program->event_name,
                'songsByEnsemble' => $this->songsByEnsemble(),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Songs grouped by ensemble in program order, mirroring the program details modal.
     *
     * @return list<array{ensembleName: string|null, songs: list<SongTitle>}>
     */
    private function songsByEnsemble(): array
    {
        $songTitles = $this->program->songTitles()->with(['composer', 'arranger'])->get();

        $ensembles = Ensemble::query()
            ->whereIn('id', $songTitles->pluck('pivot.ensemble_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $groups = [];

        foreach ($songTitles as $songTitle) {
            $groups[$songTitle->pivot->ensemble_id ?? ''][] = $songTitle;
        }

        $result = [];

        foreach ($groups as $ensembleId => $songs) {
            $result[] = [
                'ensembleName' => $ensembles->get($ensembleId)?->ensemble_name,
                'songs' => $songs,
            ];
        }

        return $result;
    }
}
