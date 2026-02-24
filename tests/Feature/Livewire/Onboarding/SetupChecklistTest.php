<?php

use App\Livewire\Onboarding\SetupChecklist;
use App\Mail\OrientationEmail;
use App\Models\Program;
use App\Models\School;
use App\Models\User;
use App\Models\UserPrivacy;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

test('new user sees checklist with all items incomplete', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(SetupChecklist::class)
        ->assertSet('hasSchool', false)
        ->assertSet('hasProgram', false)
        ->assertSet('hasPrivacy', false)
        ->assertSet('isComplete', false)
        ->assertSet('isDismissed', false)
        ->assertSee('Get Started')
        ->assertStatus(200);
});

test('checklist reflects school completion', function () {
    Mail::fake();

    $user = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);

    $this->actingAs($user);

    Livewire::test(SetupChecklist::class)
        ->assertSet('hasSchool', true)
        ->assertSet('hasProgram', false)
        ->assertSet('hasPrivacy', false)
        ->assertSet('isComplete', false)
        ->assertStatus(200);
});

test('checklist reflects program completion', function () {
    Mail::fake();

    $user = User::factory()->create();
    Program::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(SetupChecklist::class)
        ->assertSet('hasProgram', true)
        ->assertSet('hasSchool', false)
        ->assertSet('hasPrivacy', false)
        ->assertSet('isComplete', false)
        ->assertStatus(200);
});

test('checklist reflects privacy completion', function () {
    Mail::fake();

    $user = User::factory()->create();
    UserPrivacy::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(SetupChecklist::class)
        ->assertSet('hasPrivacy', true)
        ->assertSet('hasSchool', false)
        ->assertSet('hasProgram', false)
        ->assertSet('isComplete', false)
        ->assertStatus(200);
});

test('checklist auto-hides when all items complete', function () {
    Mail::fake();

    $user = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);
    Program::factory()->for($user)->create();
    UserPrivacy::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(SetupChecklist::class)
        ->assertSet('hasSchool', true)
        ->assertSet('hasProgram', true)
        ->assertSet('hasPrivacy', true)
        ->assertSet('isComplete', true)
        ->assertDontSee('Get Started')
        ->assertStatus(200);
});

test('user can dismiss checklist early', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(SetupChecklist::class)
        ->assertSet('isDismissed', false)
        ->call('dismiss')
        ->assertSet('isDismissed', true);

    expect($user->fresh()->onboarding_dismissed_at)->not->toBeNull();
});

test('dismissed checklist stays hidden on next visit', function () {
    $user = User::factory()->onboardingDismissed()->create();

    $this->actingAs($user);

    Livewire::test(SetupChecklist::class)
        ->assertSet('isDismissed', true)
        ->assertDontSee('Get Started')
        ->assertStatus(200);
});

test('checklist renders on dashboard page', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('/dashboard')
        ->assertSeeLivewire(SetupChecklist::class)
        ->assertOk();
});

test('orientation email is sent on first checklist display', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(SetupChecklist::class);

    Mail::assertSent(OrientationEmail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email)
            && $mail->user->id === $user->id;
    });

    expect($user->fresh()->orientation_email_sent_at)->not->toBeNull();
});

test('orientation email is not sent on subsequent visits', function () {
    Mail::fake();

    $user = User::factory()->orientationEmailSent()->create();

    $this->actingAs($user);

    Livewire::test(SetupChecklist::class);

    Mail::assertNotSent(OrientationEmail::class);
});

test('orientation email is not sent when impersonating a user', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['impersonating_from' => 1]);

    Livewire::test(SetupChecklist::class);

    Mail::assertNotSent(OrientationEmail::class);
    expect($user->fresh()->orientation_email_sent_at)->toBeNull();
});

test('orientation email is not sent when checklist is dismissed', function () {
    Mail::fake();

    $user = User::factory()->onboardingDismissed()->create();

    $this->actingAs($user);

    Livewire::test(SetupChecklist::class);

    Mail::assertNotSent(OrientationEmail::class);
});
