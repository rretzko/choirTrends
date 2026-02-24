<?php

declare(strict_types=1);

namespace App\Livewire\Founder;

use App\Enums\FeedbackStatus;
use App\Models\Feedback;
use App\Models\FeedbackComment;
use App\Models\FeedbackEffort;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class Issues extends Component
{
    // Filters
    public string $filterType = '';

    public string $filterStatus = '';

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    // Selected feedback detail
    public ?int $selectedFeedbackId = null;

    public string $selectedStatus = '';

    public string $newComment = '';

    // Effort tracking
    public ?string $manualStartTime = null;

    public ?string $manualStopTime = null;

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function selectFeedback(int $feedbackId): void
    {
        $this->selectedFeedbackId = $feedbackId;
        $feedback = Feedback::find($feedbackId);

        if ($feedback) {
            $this->selectedStatus = $feedback->status->value;
        }

        $this->newComment = '';
        $this->manualStartTime = null;
        $this->manualStopTime = null;
    }

    public function closeDetail(): void
    {
        $this->selectedFeedbackId = null;
        $this->selectedStatus = '';
        $this->newComment = '';
        $this->manualStartTime = null;
        $this->manualStopTime = null;
    }

    public function updateStatus(): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        $feedback = Feedback::find($this->selectedFeedbackId);

        if (! $feedback) {
            return;
        }

        $feedback->update([
            'status' => FeedbackStatus::from($this->selectedStatus),
        ]);
    }

    public function addComment(): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        $feedback = Feedback::find($this->selectedFeedbackId);

        if (! $feedback) {
            return;
        }

        $this->validate([
            'newComment' => ['required', 'string', 'min:2'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        FeedbackComment::create([
            'feedback_id' => $feedback->id,
            'user_id' => $user->id,
            'body' => $this->newComment,
        ]);

        $this->newComment = '';
    }

    public function startTimer(): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        $feedback = Feedback::find($this->selectedFeedbackId);

        if (! $feedback) {
            return;
        }

        // Don't start if a timer is already running for this feedback
        $running = $feedback->efforts()->whereNull('stopped_at')->exists();

        if ($running) {
            return;
        }

        FeedbackEffort::create([
            'feedback_id' => $feedback->id,
            'started_at' => now(),
            'stopped_at' => null,
        ]);
    }

    public function stopTimer(): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        $feedback = Feedback::find($this->selectedFeedbackId);

        if (! $feedback) {
            return;
        }

        $runningEffort = $feedback->efforts()->whereNull('stopped_at')->first();

        if ($runningEffort) {
            $runningEffort->update([
                'stopped_at' => now(),
            ]);
        }
    }

    public function addManualEffort(): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        $feedback = Feedback::find($this->selectedFeedbackId);

        if (! $feedback) {
            return;
        }

        $this->validate([
            'manualStartTime' => ['required', 'date'],
            'manualStopTime' => ['required', 'date', 'after:manualStartTime'],
        ]);

        FeedbackEffort::create([
            'feedback_id' => $feedback->id,
            'started_at' => $this->manualStartTime,
            'stopped_at' => $this->manualStopTime,
        ]);

        $this->manualStartTime = null;
        $this->manualStopTime = null;
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        $query = Feedback::query()->with(['user', 'comments', 'efforts']);

        if ($this->filterType !== '') {
            $query->where('type', $this->filterType);
        }

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        $sortColumn = match ($this->sortBy) {
            'type' => 'type',
            'status' => 'status',
            default => 'created_at',
        };

        $query->orderBy($sortColumn, $this->sortDirection);

        /** @var Collection<int, Feedback> $feedbacks */
        $feedbacks = $query->get();

        $selectedFeedback = null;
        if ($this->selectedFeedbackId) {
            $selectedFeedback = Feedback::with(['user', 'comments.user', 'efforts'])->find($this->selectedFeedbackId);
        }

        $runningEffort = null;
        if ($selectedFeedback) {
            $runningEffort = $selectedFeedback->efforts->whereNull('stopped_at')->first();
        }

        return view('livewire.founder.issues', [
            'feedbacks' => $feedbacks,
            'selectedFeedback' => $selectedFeedback,
            'runningEffort' => $runningEffort,
        ])->layout('components.layouts.app', ['title' => __('Issues')]);
    }
}
