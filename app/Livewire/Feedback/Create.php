<?php

declare(strict_types=1);

namespace App\Livewire\Feedback;

use App\Enums\FeedbackType;
use App\Mail\FeedbackSubmitted;
use App\Models\Feedback;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    public string $fromPage = '';

    public string $type = 'Bug';

    public string $body = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $file = null;

    public function mount(): void
    {
        $referer = request()->headers->get('referer');

        if ($referer) {
            $this->fromPage = $referer;
        }
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function submit(): void
    {
        $this->validate([
            'type' => ['required', 'in:Bug,Enhancement,Kudo,Comment'],
            'body' => ['required', 'string', 'min:5'],
            'file' => ['nullable', 'file', 'max:5120'],
            'fromPage' => ['nullable', 'string', 'max:255'],
        ]);

        $filePath = null;
        if ($this->file) {
            $filePath = $this->file->store('feedback-files', 'public');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $feedback = Feedback::create([
            'user_id' => $user->id,
            'from_page' => $this->fromPage ?: null,
            'type' => FeedbackType::from($this->type),
            'body' => $this->body,
            'file_path' => $filePath,
        ]);

        $founderEmail = config('app.founder');
        if ($founderEmail) {
            Mail::to($founderEmail)->send(new FeedbackSubmitted($feedback));
        }

        session()->flash('success', __('Feedback submitted successfully!'));

        $this->redirect(route('feedback.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.feedback.create')
            ->layout('components.layouts.app', ['title' => __('Submit Feedback')]);
    }
}
