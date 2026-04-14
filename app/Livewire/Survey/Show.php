<?php

declare(strict_types=1);

namespace App\Livewire\Survey;

use App\Enums\SurveyReason;
use App\Mail\SurveyResponseReceived;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public int $userId;

    public string $userName = '';

    /** @var array<int, string> */
    public array $selectedReasons = [];

    public string $otherReason = '';

    public string $comments = '';

    public bool $submitted = false;

    public bool $alreadyResponded = false;

    public function mount(User|int $user): void
    {
        $resolvedUser = $user instanceof User ? $user : User::findOrFail($user);

        $this->userId = $resolvedUser->id;
        $this->userName = $resolvedUser->name;
        $this->alreadyResponded = $resolvedUser->surveyResponses()->exists();
    }

    public function submit(): void
    {
        $validValues = array_column(SurveyReason::cases(), 'value');

        $this->validate([
            'selectedReasons' => ['required', 'array', 'min:1'],
            'selectedReasons.*' => ['required', 'string', 'in:'.implode(',', $validValues)],
            'otherReason' => ['nullable', 'string', 'max:500'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ], [
            'selectedReasons.required' => 'Please select at least one reason.',
            'selectedReasons.min' => 'Please select at least one reason.',
        ]);

        if (in_array(SurveyReason::Other->value, $this->selectedReasons) && blank($this->otherReason)) {
            $this->addError('otherReason', 'Please describe your reason.');

            return;
        }

        $response = SurveyResponse::create([
            'user_id' => $this->userId,
            'reasons' => $this->selectedReasons,
            'other_reason' => in_array(SurveyReason::Other->value, $this->selectedReasons) ? $this->otherReason : null,
            'comments' => $this->comments ?: null,
        ]);

        $founderEmail = config('app.founder');
        if ($founderEmail) {
            Mail::to($founderEmail)->queue(new SurveyResponseReceived($response));
        }

        $this->submitted = true;
    }

    public function render(): View
    {
        return view('livewire.survey.show', [
            'reasons' => SurveyReason::cases(),
        ])->layout('components.layouts.auth', ['title' => 'Share Your Feedback']);
    }
}
