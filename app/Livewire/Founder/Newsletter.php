<?php

declare(strict_types=1);

namespace App\Livewire\Founder;

use App\Mail\Newsletter as NewsletterMailable;
use App\Models\Newsletter as NewsletterModel;
use App\Models\User;
use Flux;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class Newsletter extends Component
{
    public ?int $newsletterId = null;

    public string $subject = '';

    public string $headline = '';

    public string $intro = '';

    public string $ctaText = '';

    public string $ctaUrl = '';

    public string $closing = '';

    /** @var array<int, array{title: string, body: string}> */
    public array $sections = [];

    public string $newSectionTitle = '';

    public string $newSectionBody = '';

    public ?int $editingSectionIndex = null;

    public int $recipientCount = 0;

    public function mount(?int $newsletter = null): void
    {
        $this->recipientCount = User::query()->whereNotNull('email_verified_at')->count();

        if ($newsletter) {
            $this->loadNewsletter(NewsletterModel::findOrFail($newsletter));
        } else {
            $latest = NewsletterModel::query()->whereNull('sent_at')->latest()->first();

            if ($latest) {
                $this->loadNewsletter($latest);
            } else {
                $this->ctaUrl = route('addProgram');
            }
        }
    }

    public function save(): void
    {
        $this->validateNewsletter();

        $newsletter = $this->newsletterId
            ? NewsletterModel::findOrFail($this->newsletterId)
            : new NewsletterModel;

        if ($newsletter->exists && $newsletter->isSent()) {
            session()->flash('error', __('Cannot edit a newsletter that has already been sent.'));

            return;
        }

        $newsletter->fill([
            'subject' => $this->subject,
            'headline' => $this->headline,
            'intro' => $this->intro,
            'sections' => $this->sections,
            'cta_text' => $this->ctaText,
            'cta_url' => $this->ctaUrl,
            'closing' => $this->closing,
        ]);

        $newsletter->save();
        $this->newsletterId = $newsletter->id;

        session()->flash('success', __('Newsletter saved.'));
    }

    public function createNew(): void
    {
        $this->reset([
            'newsletterId', 'subject', 'headline', 'intro',
            'sections', 'ctaText', 'closing',
        ]);
        $this->ctaUrl = route('addProgram');
    }

    public function loadExisting(int $id): void
    {
        $this->loadNewsletter(NewsletterModel::findOrFail($id));
    }

    public function addSection(): void
    {
        $this->validate([
            'newSectionTitle' => 'required|string|max:255',
            'newSectionBody' => 'required|string',
        ]);

        if ($this->editingSectionIndex !== null) {
            $this->sections[$this->editingSectionIndex] = [
                'title' => $this->newSectionTitle,
                'body' => $this->newSectionBody,
            ];
            $this->editingSectionIndex = null;
        } else {
            $this->sections[] = [
                'title' => $this->newSectionTitle,
                'body' => $this->newSectionBody,
            ];
        }

        $this->newSectionTitle = '';
        $this->newSectionBody = '';

        Flux::modal('section-form')->close();
    }

    public function editSection(int $index): void
    {
        $this->editingSectionIndex = $index;
        $this->newSectionTitle = $this->sections[$index]['title'];
        $this->newSectionBody = $this->sections[$index]['body'];

        Flux::modal('section-form')->show();
    }

    public function removeSection(int $index): void
    {
        unset($this->sections[$index]);
        $this->sections = array_values($this->sections);
    }

    public function openAddSection(): void
    {
        $this->editingSectionIndex = null;
        $this->newSectionTitle = '';
        $this->newSectionBody = '';

        Flux::modal('section-form')->show();
    }

    public function sendPreview(): void
    {
        $this->validateNewsletter();
        $this->save();

        /** @var User $founder */
        $founder = auth()->user();

        Mail::to($founder)->send(new NewsletterMailable(
            emailSubject: $this->subject,
            user: $founder,
            newsletterData: $this->buildData(),
        ));

        session()->flash('success', __('Preview sent to :email', ['email' => $founder->email]));
    }

    public function sendToAll(): void
    {
        $this->validateNewsletter();

        $newsletter = $this->newsletterId
            ? NewsletterModel::findOrFail($this->newsletterId)
            : null;

        if ($newsletter && $newsletter->isSent()) {
            session()->flash('error', __('This newsletter has already been sent.'));
            Flux::modal('confirm-send')->close();

            return;
        }

        $this->save();
        $newsletter = NewsletterModel::findOrFail($this->newsletterId);

        $users = User::query()->whereNotNull('email_verified_at')->get();
        $data = $this->buildData();
        $sent = 0;

        foreach ($users as $user) {
            try {
                Mail::to($user)->send(new NewsletterMailable(
                    emailSubject: $this->subject,
                    user: $user,
                    newsletterData: $data,
                ));
                $sent++;
            } catch (\Throwable) {
            }
        }

        $newsletter->update([
            'sent_at' => now(),
            'recipient_count' => $sent,
        ]);

        Flux::modal('confirm-send')->close();

        session()->flash('success', __('Newsletter sent to :count user(s).', ['count' => $sent]));
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        $newsletters = NewsletterModel::query()
            ->latest()
            ->get(['id', 'subject', 'sent_at', 'recipient_count', 'created_at']);

        return view('livewire.founder.newsletter', [
            'newsletters' => $newsletters,
        ])->layout('components.layouts.app', ['title' => __('Newsletter')]);
    }

    private function validateNewsletter(): void
    {
        $this->validate([
            'subject' => 'required|string|max:255',
            'headline' => 'required|string|max:255',
            'intro' => 'required|string',
            'sections' => 'required|array|min:1',
            'ctaText' => 'required|string|max:255',
            'ctaUrl' => 'required|url',
            'closing' => 'required|string',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildData(): array
    {
        return [
            'headline' => $this->headline,
            'intro' => $this->intro,
            'sections' => $this->sections,
            'cta_text' => $this->ctaText,
            'cta_url' => $this->ctaUrl,
            'closing' => $this->closing,
        ];
    }

    private function loadNewsletter(NewsletterModel $newsletter): void
    {
        $this->newsletterId = $newsletter->id;
        $this->subject = $newsletter->subject;
        $this->headline = $newsletter->headline;
        $this->intro = $newsletter->intro;
        /** @var array<int, array{title: string, body: string}> $sections */
        $sections = $newsletter->sections;
        $this->sections = $sections;
        $this->ctaText = $newsletter->cta_text;
        $this->ctaUrl = $newsletter->cta_url;
        $this->closing = $newsletter->closing;
    }
}
