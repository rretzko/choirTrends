<?php

use App\Enums\FeedbackStatus;
use App\Livewire\Feedback\Index;
use App\Models\Feedback;
use App\Models\FeedbackComment;
use App\Models\User;
use App\Models\UserPrivacy;
use Livewire\Livewire;

test('feedback index page can be rendered', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    $this->get(route('feedback.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

test('guests cannot access feedback index page', function () {
    $this->get(route('feedback.index'))
        ->assertRedirect(route('login'));
});

test('feedback index shows only my feedback by default', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $otherUser = User::factory()->withoutTwoFactor()->create();

    $myFeedback = Feedback::factory()->for($user)->create(['body' => 'My specific feedback']);
    $otherFeedback = Feedback::factory()->for($otherUser)->create(['body' => 'Other user feedback']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSet('filterScope', 'my')
        ->assertSee('My specific feedback')
        ->assertDontSee('Other user feedback');
});

test('feedback index shows all feedback when scope is all', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $otherUser = User::factory()->withoutTwoFactor()->create();

    $myFeedback = Feedback::factory()->for($user)->create(['body' => 'My feedback item']);
    $otherFeedback = Feedback::factory()->for($otherUser)->create(['body' => 'Other feedback item']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filterScope', 'all')
        ->assertSee('My feedback item')
        ->assertSee('Other feedback item');
});

test('feedback index can filter by type', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $bug = Feedback::factory()->for($user)->bug()->create(['body' => 'A bug report here']);
    $enhancement = Feedback::factory()->for($user)->enhancement()->create(['body' => 'An enhancement request']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filterType', 'Bug')
        ->assertSee('A bug report here')
        ->assertDontSee('An enhancement request');
});

test('feedback index sorts by date descending by default', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $older = Feedback::factory()->for($user)->create([
        'body' => 'Older feedback',
        'created_at' => now()->subDays(5),
    ]);
    $newer = Feedback::factory()->for($user)->create([
        'body' => 'Newer feedback',
        'created_at' => now(),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Index::class)
        ->assertSet('sortBy', 'created_at')
        ->assertSet('sortDirection', 'desc');

    $feedbacks = $component->viewData('feedbacks');
    expect($feedbacks->first()->body)->toBe('Newer feedback');
});

test('feedback index can toggle sort direction', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSet('sortDirection', 'desc')
        ->call('sort', 'created_at')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'created_at')
        ->assertSet('sortDirection', 'desc');
});

test('clicking feedback shows detail modal', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $feedback = Feedback::factory()->for($user)->create(['body' => 'Detailed feedback body']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('showDetails', $feedback->id)
        ->assertSet('selectedFeedback.id', $feedback->id);
});

test('submitter name is masked when user has name privacy enabled', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $otherUser = User::factory()->withoutTwoFactor()->create(['name' => 'Private Person']);

    UserPrivacy::factory()->create([
        'user_id' => $otherUser->id,
        'name' => true,
    ]);

    Feedback::factory()->for($otherUser)->create(['body' => 'Private feedback']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filterScope', 'all')
        ->assertSee('User'.$otherUser->id)
        ->assertDontSee('Private Person');
});

test('submitter name is shown when viewing own feedback with privacy enabled', function () {
    $user = User::factory()->withoutTwoFactor()->create(['name' => 'My Real Name']);

    UserPrivacy::factory()->create([
        'user_id' => $user->id,
        'name' => true,
    ]);

    Feedback::factory()->for($user)->create(['body' => 'My private feedback']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('My Real Name');
});

test('founder can see real names even with privacy enabled', function () {
    config(['app.founder' => 'founder@example.com']);

    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $otherUser = User::factory()->withoutTwoFactor()->create(['name' => 'Hidden User']);

    UserPrivacy::factory()->create([
        'user_id' => $otherUser->id,
        'name' => true,
    ]);

    Feedback::factory()->for($otherUser)->create(['body' => 'Feedback from hidden user']);

    $this->actingAs($founder);

    Livewire::test(Index::class)
        ->set('filterScope', 'all')
        ->assertSee('Hidden User');
});

test('founder can update feedback status', function () {
    config(['app.founder' => 'founder@example.com']);

    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $feedback = Feedback::factory()->for($founder)->create(['status' => FeedbackStatus::Open]);

    $this->actingAs($founder);

    Livewire::test(Index::class)
        ->call('showDetails', $feedback->id)
        ->set('selectedStatus', 'Closed')
        ->call('updateStatus');

    expect($feedback->fresh()->status)->toBe(FeedbackStatus::Closed);
});

test('non-founder cannot update feedback status', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $feedback = Feedback::factory()->for($user)->create(['status' => FeedbackStatus::Open]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('showDetails', $feedback->id)
        ->set('selectedStatus', 'Closed')
        ->call('updateStatus');

    expect($feedback->fresh()->status)->toBe(FeedbackStatus::Open);
});

test('founder can add a comment', function () {
    config(['app.founder' => 'founder@example.com']);

    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $feedback = Feedback::factory()->for($founder)->create();

    $this->actingAs($founder);

    Livewire::test(Index::class)
        ->call('showDetails', $feedback->id)
        ->set('newComment', 'Working on this now')
        ->call('addComment');

    expect(FeedbackComment::count())->toBe(1);
    expect(FeedbackComment::first()->body)->toBe('Working on this now');
});

test('non-founder cannot add a comment', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $feedback = Feedback::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('showDetails', $feedback->id)
        ->set('newComment', 'I should not be able to comment')
        ->call('addComment');

    expect(FeedbackComment::count())->toBe(0);
});

test('feedback detail modal shows comments', function () {
    config(['app.founder' => 'founder@example.com']);

    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $feedback = Feedback::factory()->for($founder)->create();
    FeedbackComment::factory()->create([
        'feedback_id' => $feedback->id,
        'user_id' => $founder->id,
        'body' => 'This is a founder comment',
    ]);

    $this->actingAs($founder);

    Livewire::test(Index::class)
        ->call('showDetails', $feedback->id)
        ->assertSee('This is a founder comment');
});

test('feedback index displays status badges', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    Feedback::factory()->for($user)->create(['status' => FeedbackStatus::Open, 'body' => 'Open feedback']);
    Feedback::factory()->for($user)->create(['status' => FeedbackStatus::Closed, 'body' => 'Closed feedback']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('Open')
        ->assertSee('Closed');
});

test('feedback index displays type badges', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    Feedback::factory()->for($user)->bug()->create(['body' => 'A bug report']);
    Feedback::factory()->for($user)->kudo()->create(['body' => 'Great work on this']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('Bug')
        ->assertSee('Kudo');
});
