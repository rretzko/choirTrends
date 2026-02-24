<?php

use App\Enums\FeedbackStatus;
use App\Livewire\Founder\Issues;
use App\Models\Feedback;
use App\Models\FeedbackComment;
use App\Models\FeedbackEffort;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.founder' => 'founder@example.com']);
});

// --- Access Control ---

test('founder can access the issues page', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $this->actingAs($founder)
        ->get(route('founder.issues'))
        ->assertOk();
});

test('non-founder gets 403 on issues page', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('founder.issues'))
        ->assertForbidden();
});

test('guest is redirected from issues page', function () {
    $this->get(route('founder.issues'))
        ->assertRedirect(route('login'));
});

// --- Listing ---

test('issues page lists all feedback from all users', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $userA = User::factory()->withoutTwoFactor()->create();
    $userB = User::factory()->withoutTwoFactor()->create();

    Feedback::factory()->for($userA)->create(['body' => 'Feedback from user A']);
    Feedback::factory()->for($userB)->create(['body' => 'Feedback from user B']);

    Livewire::actingAs($founder)
        ->test(Issues::class)
        ->assertSee('Feedback from user A')
        ->assertSee('Feedback from user B');
});

// --- Filtering ---

test('issues can be filtered by type', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Feedback::factory()->bug()->create(['body' => 'A bug report here']);
    Feedback::factory()->enhancement()->create(['body' => 'An enhancement request']);

    Livewire::actingAs($founder)
        ->test(Issues::class)
        ->set('filterType', 'Bug')
        ->assertSee('A bug report here')
        ->assertDontSee('An enhancement request');
});

test('issues can be filtered by status', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Feedback::factory()->create(['body' => 'Open issue body', 'status' => FeedbackStatus::Open]);
    Feedback::factory()->create(['body' => 'Closed issue body', 'status' => FeedbackStatus::Closed]);

    Livewire::actingAs($founder)
        ->test(Issues::class)
        ->set('filterStatus', 'Open')
        ->assertSee('Open issue body')
        ->assertDontSee('Closed issue body');
});

// --- Sorting ---

test('issues sort by date descending by default', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Feedback::factory()->create(['body' => 'Older issue', 'created_at' => now()->subDays(5)]);
    Feedback::factory()->create(['body' => 'Newer issue', 'created_at' => now()]);

    $component = Livewire::actingAs($founder)
        ->test(Issues::class)
        ->assertSet('sortBy', 'created_at')
        ->assertSet('sortDirection', 'desc');

    $feedbacks = $component->viewData('feedbacks');
    expect($feedbacks->first()->body)->toBe('Newer issue');
});

test('issues can toggle sort direction', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(Issues::class)
        ->assertSet('sortDirection', 'desc')
        ->call('sort', 'created_at')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'created_at')
        ->assertSet('sortDirection', 'desc');
});

// --- Detail Panel ---

test('selecting feedback loads detail panel', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $feedback = Feedback::factory()->create(['body' => 'Detailed feedback body']);

    Livewire::actingAs($founder)
        ->test(Issues::class)
        ->call('selectFeedback', $feedback->id)
        ->assertSet('selectedFeedbackId', $feedback->id)
        ->assertSee('Detailed feedback body');
});

test('closing detail panel clears selection', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $feedback = Feedback::factory()->create();

    Livewire::actingAs($founder)
        ->test(Issues::class)
        ->call('selectFeedback', $feedback->id)
        ->assertSet('selectedFeedbackId', $feedback->id)
        ->call('closeDetail')
        ->assertSet('selectedFeedbackId', null);
});

test('display names always show real names for founder', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $user = User::factory()->withoutTwoFactor()->create(['name' => 'John Doe']);

    Feedback::factory()->for($user)->create(['body' => 'Some feedback']);

    Livewire::actingAs($founder)
        ->test(Issues::class)
        ->assertSee('John Doe');
});

// --- Status Management ---

test('founder can update feedback status', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $feedback = Feedback::factory()->create(['status' => FeedbackStatus::Open]);

    Livewire::actingAs($founder)
        ->test(Issues::class)
        ->call('selectFeedback', $feedback->id)
        ->set('selectedStatus', 'Closed')
        ->call('updateStatus');

    expect($feedback->fresh()->status)->toBe(FeedbackStatus::Closed);
});

// --- Comments ---

test('founder can add a comment', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $feedback = Feedback::factory()->create();

    Livewire::actingAs($founder)
        ->test(Issues::class)
        ->call('selectFeedback', $feedback->id)
        ->set('newComment', 'Working on this now')
        ->call('addComment');

    expect(FeedbackComment::count())->toBe(1);
    expect(FeedbackComment::first()->body)->toBe('Working on this now');
    expect(FeedbackComment::first()->user_id)->toBe($founder->id);
});

test('comment validation requires minimum length', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $feedback = Feedback::factory()->create();

    Livewire::actingAs($founder)
        ->test(Issues::class)
        ->call('selectFeedback', $feedback->id)
        ->set('newComment', 'X')
        ->call('addComment')
        ->assertHasErrors(['newComment']);

    expect(FeedbackComment::count())->toBe(0);
});

test('detail panel shows existing comments', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $feedback = Feedback::factory()->create();

    FeedbackComment::factory()->create([
        'feedback_id' => $feedback->id,
        'user_id' => $founder->id,
        'body' => 'Existing comment text',
    ]);

    Livewire::actingAs($founder)
        ->test(Issues::class)
        ->call('selectFeedback', $feedback->id)
        ->assertSee('Existing comment text');
});

// --- Effort Tracking: Timer ---

test('start timer creates effort entry with null stopped_at', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $feedback = Feedback::factory()->create();

    Livewire::actingAs($founder)
        ->test(Issues::class)
        ->call('selectFeedback', $feedback->id)
        ->call('startTimer');

    $effort = FeedbackEffort::first();
    expect($effort)->not->toBeNull()
        ->and($effort->feedback_id)->toBe($feedback->id)
        ->and($effort->started_at)->not->toBeNull()
        ->and($effort->stopped_at)->toBeNull();
});

test('stop timer sets stopped_at on running entry', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $feedback = Feedback::factory()->create();

    $effort = FeedbackEffort::factory()->running()->create(['feedback_id' => $feedback->id]);

    Livewire::actingAs($founder)
        ->test(Issues::class)
        ->call('selectFeedback', $feedback->id)
        ->call('stopTimer');

    expect($effort->fresh()->stopped_at)->not->toBeNull();
});

test('cannot start timer if one is already running for this feedback', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $feedback = Feedback::factory()->create();

    FeedbackEffort::factory()->running()->create(['feedback_id' => $feedback->id]);

    Livewire::actingAs($founder)
        ->test(Issues::class)
        ->call('selectFeedback', $feedback->id)
        ->call('startTimer');

    expect(FeedbackEffort::where('feedback_id', $feedback->id)->count())->toBe(1);
});

// --- Effort Tracking: Manual Entry ---

test('can add manual effort entry with valid start and stop', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $feedback = Feedback::factory()->create();

    $start = now()->subHours(2)->format('Y-m-d\TH:i');
    $stop = now()->subHour()->format('Y-m-d\TH:i');

    Livewire::actingAs($founder)
        ->test(Issues::class)
        ->call('selectFeedback', $feedback->id)
        ->set('manualStartTime', $start)
        ->set('manualStopTime', $stop)
        ->call('addManualEffort')
        ->assertHasNoErrors();

    expect(FeedbackEffort::count())->toBe(1);
    expect(FeedbackEffort::first()->stopped_at)->not->toBeNull();
});

test('manual effort validation requires stop after start', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $feedback = Feedback::factory()->create();

    $start = now()->format('Y-m-d\TH:i');
    $stop = now()->subHours(2)->format('Y-m-d\TH:i');

    Livewire::actingAs($founder)
        ->test(Issues::class)
        ->call('selectFeedback', $feedback->id)
        ->set('manualStartTime', $start)
        ->set('manualStopTime', $stop)
        ->call('addManualEffort')
        ->assertHasErrors(['manualStopTime']);

    expect(FeedbackEffort::count())->toBe(0);
});

test('manual effort validation requires both fields', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $feedback = Feedback::factory()->create();

    Livewire::actingAs($founder)
        ->test(Issues::class)
        ->call('selectFeedback', $feedback->id)
        ->set('manualStartTime', null)
        ->set('manualStopTime', null)
        ->call('addManualEffort')
        ->assertHasErrors(['manualStartTime', 'manualStopTime']);
});

// --- Total Effort Display ---

test('total effort displays correctly', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $feedback = Feedback::factory()->create();

    FeedbackEffort::factory()->create([
        'feedback_id' => $feedback->id,
        'started_at' => now()->subMinutes(90),
        'stopped_at' => now(),
    ]);

    Livewire::actingAs($founder)
        ->test(Issues::class)
        ->assertSee('1:30');
});
