<?php

declare(strict_types=1);

namespace App\Livewire\Feedback;

use App\Enums\FeedbackStatus;
use App\Models\Feedback;
use App\Models\FeedbackComment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public string $filterType = '';

    public string $filterScope = 'my';

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    public ?Feedback $selectedFeedback = null;

    public string $newComment = '';

    public string $selectedStatus = '';

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function showDetails(int $feedbackId): void
    {
        $this->selectedFeedback = Feedback::with(['user.privacy', 'comments.user'])->find($feedbackId);

        if ($this->selectedFeedback) {
            $this->selectedStatus = $this->selectedFeedback->status->value;
        }

        $this->newComment = '';

        $this->modal('feedback-details')->show();
    }

    public function updateStatus(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user->isFounder() || ! $this->selectedFeedback) {
            return;
        }

        $this->selectedFeedback->update([
            'status' => FeedbackStatus::from($this->selectedStatus),
        ]);
    }

    public function addComment(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user->isFounder() || ! $this->selectedFeedback) {
            return;
        }

        $this->validate([
            'newComment' => ['required', 'string', 'min:2'],
        ]);

        FeedbackComment::create([
            'feedback_id' => $this->selectedFeedback->id,
            'user_id' => $user->id,
            'body' => $this->newComment,
        ]);

        $this->newComment = '';

        $this->selectedFeedback->load('comments.user');
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
            'isFounder' => $isFounder,
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
