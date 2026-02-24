<?php

use App\Enums\FeedbackStatus;
use App\Enums\FeedbackType;
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

test('tab defaults to history', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSet('tab', 'history');
});

test('tab can be set via url parameter', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    Livewire::test(Index::class, ['tab' => 'report'])
        ->assertSet('tab', 'report');
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

// Inline editing tests

test('user can start editing own feedback', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $feedback = Feedback::factory()->for($user)->bug()->create(['body' => 'Original body']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('startEditing', $feedback->id)
        ->assertSet('editingFeedbackId', $feedback->id)
        ->assertSet('editBody', 'Original body')
        ->assertSet('editType', 'Bug');
});

test('user cannot edit another users feedback', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $otherUser = User::factory()->withoutTwoFactor()->create();
    $feedback = Feedback::factory()->for($otherUser)->create(['body' => 'Not mine']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('startEditing', $feedback->id)
        ->assertSet('editingFeedbackId', null);
});

test('user can save edits to own feedback', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $feedback = Feedback::factory()->for($user)->bug()->create(['body' => 'Original body']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('startEditing', $feedback->id)
        ->set('editBody', 'Updated body text here')
        ->set('editType', 'Enhancement')
        ->call('saveEdit')
        ->assertSet('editingFeedbackId', null);

    $feedback->refresh();
    expect($feedback->body)->toBe('Updated body text here')
        ->and($feedback->type)->toBe(FeedbackType::Enhancement);
});

test('user can cancel editing', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $feedback = Feedback::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('startEditing', $feedback->id)
        ->assertSet('editingFeedbackId', $feedback->id)
        ->call('cancelEditing')
        ->assertSet('editingFeedbackId', null)
        ->assertSet('editBody', '')
        ->assertSet('editType', '');
});

// Inline commenting tests

test('user can start commenting on own feedback', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $feedback = Feedback::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('startCommenting', $feedback->id)
        ->assertSet('commentingFeedbackId', $feedback->id);
});

test('user cannot comment on another users feedback', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $otherUser = User::factory()->withoutTwoFactor()->create();
    $feedback = Feedback::factory()->for($otherUser)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('startCommenting', $feedback->id)
        ->assertSet('commentingFeedbackId', null);
});

test('user can submit a comment on own feedback', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $feedback = Feedback::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('startCommenting', $feedback->id)
        ->set('userComment', 'Here is my comment')
        ->call('submitUserComment');

    expect(FeedbackComment::count())->toBe(1);
    expect(FeedbackComment::first()->body)->toBe('Here is my comment');
    expect(FeedbackComment::first()->user_id)->toBe($user->id);
});

test('user comment requires minimum length', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $feedback = Feedback::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('startCommenting', $feedback->id)
        ->set('userComment', 'X')
        ->call('submitUserComment')
        ->assertHasErrors(['userComment']);

    expect(FeedbackComment::count())->toBe(0);
});

test('user can cancel commenting', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $feedback = Feedback::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('startCommenting', $feedback->id)
        ->assertSet('commentingFeedbackId', $feedback->id)
        ->call('cancelCommenting')
        ->assertSet('commentingFeedbackId', null)
        ->assertSet('userComment', '');
});
