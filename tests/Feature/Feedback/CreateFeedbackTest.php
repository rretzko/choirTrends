<?php

use App\Enums\FeedbackStatus;
use App\Enums\FeedbackType;
use App\Livewire\Feedback\Index;
use App\Mail\FeedbackSubmitted;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('report tab renders the create form', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    Livewire::test(Index::class, ['tab' => 'report'])
        ->assertSee('Reported By')
        ->assertSee('Request Type')
        ->assertSee('Submit Feedback');
});

test('old feedback/create route redirects to report tab', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    $this->get('/feedback/create')
        ->assertRedirect('/feedback?tab=report');
});

test('user can submit feedback from report tab', function () {
    Mail::fake();

    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    Livewire::test(Index::class, ['tab' => 'report'])
        ->set('type', 'Bug')
        ->set('body', 'Something is broken on the dashboard')
        ->set('fromPage', '/dashboard')
        ->call('submit')
        ->assertSet('tab', 'history');

    $feedback = Feedback::first();

    expect($feedback)->not->toBeNull()
        ->and($feedback->user_id)->toBe($user->id)
        ->and($feedback->type)->toBe(FeedbackType::Bug)
        ->and($feedback->body)->toBe('Something is broken on the dashboard')
        ->and($feedback->from_page)->toBe('/dashboard')
        ->and($feedback->status)->toBe(FeedbackStatus::Open);
});

test('feedback submission sends email to founder', function () {
    Mail::fake();
    config(['app.founder' => 'founder@example.com']);

    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    Livewire::test(Index::class, ['tab' => 'report'])
        ->set('type', 'Enhancement')
        ->set('body', 'Please add dark mode to the settings page')
        ->call('submit');

    Mail::assertSent(FeedbackSubmitted::class, function ($mail) {
        return $mail->hasTo('founder@example.com');
    });
});

test('feedback body is required', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    Livewire::test(Index::class, ['tab' => 'report'])
        ->set('type', 'Bug')
        ->set('body', '')
        ->call('submit')
        ->assertHasErrors(['body']);

    expect(Feedback::count())->toBe(0);
});

test('feedback body must be at least 5 characters', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    Livewire::test(Index::class, ['tab' => 'report'])
        ->set('type', 'Bug')
        ->set('body', 'Hi')
        ->call('submit')
        ->assertHasErrors(['body']);
});

test('feedback type must be valid', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    Livewire::test(Index::class, ['tab' => 'report'])
        ->set('type', 'InvalidType')
        ->set('body', 'This has an invalid type')
        ->call('submit')
        ->assertHasErrors(['type']);
});

test('user can upload a file with feedback', function () {
    Mail::fake();
    Storage::fake('public');

    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    $file = UploadedFile::fake()->image('screenshot.png');

    Livewire::test(Index::class, ['tab' => 'report'])
        ->set('type', 'Bug')
        ->set('body', 'Here is a screenshot of the issue')
        ->set('file', $file)
        ->call('submit')
        ->assertSet('tab', 'history');

    $feedback = Feedback::first();

    expect($feedback->file_path)->not->toBeNull();

    Storage::disk('public')->assertExists($feedback->file_path);
});

test('user can set feedback type via setType method', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    Livewire::test(Index::class, ['tab' => 'report'])
        ->assertSet('type', 'Bug')
        ->call('setType', 'Enhancement')
        ->assertSet('type', 'Enhancement')
        ->call('setType', 'Kudo')
        ->assertSet('type', 'Kudo');
});

test('all feedback types can be submitted', function (string $type) {
    Mail::fake();

    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    Livewire::test(Index::class, ['tab' => 'report'])
        ->set('type', $type)
        ->set('body', "Testing {$type} feedback submission")
        ->call('submit')
        ->assertSet('tab', 'history');

    $feedback = Feedback::latest()->first();
    expect($feedback->type)->toBe(FeedbackType::from($type));
})->with(['Bug', 'Enhancement', 'Kudo', 'Comment']);

test('founder can submit private feedback', function () {
    Mail::fake();
    config(['app.founder' => 'founder@example.com']);

    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $this->actingAs($founder);

    Livewire::test(Index::class, ['tab' => 'report'])
        ->set('type', 'Bug')
        ->set('body', 'This is a private note from the founder')
        ->set('isPrivate', true)
        ->call('submit')
        ->assertSet('tab', 'history');

    $feedback = Feedback::first();

    expect($feedback)->not->toBeNull()
        ->and($feedback->is_private)->toBeTrue();
});

test('non-founder cannot submit private feedback', function () {
    Mail::fake();

    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    Livewire::test(Index::class, ['tab' => 'report'])
        ->set('type', 'Bug')
        ->set('body', 'Trying to make this private')
        ->set('isPrivate', true)
        ->call('submit');

    $feedback = Feedback::first();

    expect($feedback->is_private)->toBeFalse();
});

test('private checkbox is not visible to non-founder', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    Livewire::test(Index::class, ['tab' => 'report'])
        ->assertDontSee('Private (visible only on Issues page)');
});

test('private checkbox is visible to founder', function () {
    config(['app.founder' => 'founder@example.com']);

    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $this->actingAs($founder);

    Livewire::test(Index::class, ['tab' => 'report'])
        ->assertSee('Private (visible only on Issues page)');
});

test('from page is optional and can be empty', function () {
    Mail::fake();

    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    Livewire::test(Index::class, ['tab' => 'report'])
        ->set('type', 'Bug')
        ->set('body', 'Bug without from page info')
        ->set('fromPage', '')
        ->call('submit')
        ->assertSet('tab', 'history');

    $feedback = Feedback::first();
    expect($feedback->from_page)->toBeNull();
});
