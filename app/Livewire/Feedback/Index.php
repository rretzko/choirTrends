<?php

declare(strict_types=1);

namespace App\Livewire\Feedback;

use App\Enums\FeedbackType;
use App\Mail\FeedbackSubmitted;
use App\Models\Feedback;
use App\Models\FeedbackComment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithFileUploads;

    #[Url]
    public string $tab = 'history';

    // History tab — filters & sorting
    public string $filterType = '';

    public string $filterScope = 'my';

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    // Report tab — create form
    public string $fromPage = '';

    public string $type = 'Bug';

    public string $body = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $file = null;

    // History tab — editing
    public ?int $editingFeedbackId = null;

    public string $editBody = '';

    public string $editType = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $editFile = null;

    // History tab — user comments
    public string $userComment = '';

    public ?int $commentingFeedbackId = null;

    public function mount(): void
    {
        $referer = request()->headers->get('referer');

        if ($referer && $this->tab === 'report') {
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

        $this->reset(['fromPage', 'type', 'body', 'file']);
        $this->type = 'Bug';

        session()->flash('success', __('Feedback submitted successfully!'));

        $this->tab = 'history';
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function startEditing(int $feedbackId): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $feedback = Feedback::find($feedbackId);

        if (! $feedback || $feedback->user_id !== $user->id) {
            return;
        }

        $this->editingFeedbackId = $feedbackId;
        $this->editBody = $feedback->body;
        $this->editType = $feedback->type->value;
        $this->editFile = null;
        $this->cancelCommenting();
    }

    public function cancelEditing(): void
    {
        $this->editingFeedbackId = null;
        $this->editBody = '';
        $this->editType = '';
        $this->editFile = null;
    }

    public function saveEdit(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $feedback = Feedback::find($this->editingFeedbackId);

        if (! $feedback || $feedback->user_id !== $user->id) {
            return;
        }

        $this->validate([
            'editType' => ['required', 'in:Bug,Enhancement,Kudo,Comment'],
            'editBody' => ['required', 'string', 'min:5'],
            'editFile' => ['nullable', 'file', 'max:5120'],
        ]);

        $data = [
            'type' => FeedbackType::from($this->editType),
            'body' => $this->editBody,
        ];

        if ($this->editFile) {
            $data['file_path'] = $this->editFile->store('feedback-files', 'public');
        }

        $feedback->update($data);

        $this->cancelEditing();
    }

    public function startCommenting(int $feedbackId): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $feedback = Feedback::find($feedbackId);

        if (! $feedback || $feedback->user_id !== $user->id) {
            return;
        }

        $this->commentingFeedbackId = $feedbackId;
        $this->userComment = '';
        $this->cancelEditing();
    }

    public function cancelCommenting(): void
    {
        $this->commentingFeedbackId = null;
        $this->userComment = '';
    }

    public function submitUserComment(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $feedback = Feedback::find($this->commentingFeedbackId);

        if (! $feedback || $feedback->user_id !== $user->id) {
            return;
        }

        $this->validate([
            'userComment' => ['required', 'string', 'min:2'],
        ]);

        FeedbackComment::create([
            'feedback_id' => $feedback->id,
            'user_id' => $user->id,
            'body' => $this->userComment,
        ]);

        $this->cancelCommenting();
    }

    public function render(): View
    {
        $query = Feedback::query()->with(['user.privacy', 'comments']);

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if ($this->filterScope === 'my') {
            $query->where('user_id', $currentUser->id);
        }

        if ($this->filterType !== '') {
            $query->where('type', $this->filterType);
        }

        $sortColumn = match ($this->sortBy) {
            'type' => 'type',
            'status' => 'status',
            default => 'created_at',
        };

        $query->orderBy($sortColumn, $this->sortDirection);

        /** @var Collection<int, Feedback> $feedbacks */
        $feedbacks = $query->get();

        $isFounder = $currentUser->isFounder();

        /** @var array<int, string> $displayNames */
        $displayNames = [];
        foreach ($feedbacks as $feedback) {
            $displayNames[$feedback->id] = $this->getDisplayName($feedback, $currentUser->id, $isFounder);
        }

        return view('livewire.feedback.index', [
            'feedbacks' => $feedbacks,
            'displayNames' => $displayNames,
        ])->layout('components.layouts.app', ['title' => __('Feedback')]);
    }

    private function getDisplayName(Feedback $feedback, int $currentUserId, bool $isFounder): string
    {
        if ($feedback->user_id === $currentUserId || $isFounder) {
            return $feedback->user->name;
        }

        if ($feedback->user->privacy?->name) {
            return 'User'.$feedback->user_id;
        }

        return $feedback->user->name;
    }
}
