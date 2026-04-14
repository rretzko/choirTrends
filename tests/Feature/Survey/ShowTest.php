<?php

declare(strict_types=1);

use App\Enums\SurveyReason;
use App\Livewire\Survey\Show;
use App\Mail\SurveyResponseReceived;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.founder' => 'founder@example.com']);
});

test('valid signed url renders the survey form', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $url = URL::signedRoute('survey.show', ['user' => $user->id]);

    $this->get($url)
        ->assertOk()
        ->assertSeeLivewire(Show::class);
});

test('unsigned url returns 403', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->get(route('survey.show', ['user' => $user->id]))
        ->assertForbidden();
});

test('submitting a valid response creates a survey response record', function () {
    Mail::fake();
    $user = User::factory()->withoutTwoFactor()->create();

    Livewire::test(Show::class, ['user' => $user])
        ->set('selectedReasons', [SurveyReason::NoTime->value])
        ->set('comments', 'Great site though!')
        ->call('submit')
        ->assertSet('submitted', true);

    expect(SurveyResponse::where('user_id', $user->id)->count())->toBe(1);

    $response = SurveyResponse::where('user_id', $user->id)->first();
    expect($response->reasons)->toBe([SurveyReason::NoTime->value])
        ->and($response->comments)->toBe('Great site though!');
});

test('submitting sends email to founder', function () {
    Mail::fake();
    $user = User::factory()->withoutTwoFactor()->create();

    Livewire::test(Show::class, ['user' => $user])
        ->set('selectedReasons', [SurveyReason::JustBrowsing->value])
        ->call('submit');

    Mail::assertQueued(SurveyResponseReceived::class, function (SurveyResponseReceived $mail) {
        return $mail->hasTo(config('app.founder'));
    });
});

test('selecting other requires other_reason text', function () {
    Mail::fake();
    $user = User::factory()->withoutTwoFactor()->create();

    Livewire::test(Show::class, ['user' => $user])
        ->set('selectedReasons', [SurveyReason::Other->value])
        ->set('otherReason', '')
        ->call('submit')
        ->assertHasErrors('otherReason');

    expect(SurveyResponse::where('user_id', $user->id)->count())->toBe(0);
});

test('selecting other with reason text succeeds', function () {
    Mail::fake();
    $user = User::factory()->withoutTwoFactor()->create();

    Livewire::test(Show::class, ['user' => $user])
        ->set('selectedReasons', [SurveyReason::Other->value])
        ->set('otherReason', 'I use a different tool')
        ->call('submit')
        ->assertSet('submitted', true);

    $response = SurveyResponse::where('user_id', $user->id)->first();
    expect($response->other_reason)->toBe('I use a different tool');
});

test('user who already responded sees already submitted message', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    SurveyResponse::factory()->create(['user_id' => $user->id]);

    Livewire::test(Show::class, ['user' => $user])
        ->assertSet('alreadyResponded', true)
        ->assertSee('Already Submitted');
});

test('at least one reason must be selected', function () {
    Mail::fake();
    $user = User::factory()->withoutTwoFactor()->create();

    Livewire::test(Show::class, ['user' => $user])
        ->set('selectedReasons', [])
        ->call('submit')
        ->assertHasErrors('selectedReasons');
});

test('multiple reasons can be selected', function () {
    Mail::fake();
    $user = User::factory()->withoutTwoFactor()->create();

    Livewire::test(Show::class, ['user' => $user])
        ->set('selectedReasons', [SurveyReason::NoTime->value, SurveyReason::NoDigitalCopy->value])
        ->call('submit')
        ->assertSet('submitted', true);

    $response = SurveyResponse::where('user_id', $user->id)->first();
    expect($response->reasons)->toBe([SurveyReason::NoTime->value, SurveyReason::NoDigitalCopy->value]);
});
