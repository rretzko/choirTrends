<?php

declare(strict_types=1);

use App\Mail\SpringConcertProgramNudge;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    config(['app.founder' => 'founder@example.com']);
});

test('queues nudge to verified user with no program since May 1st', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->artisan('programs:nudge-spring')->assertSuccessful();

    Mail::assertQueued(SpringConcertProgramNudge::class, function (SpringConcertProgramNudge $mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

test('does not queue nudge to unverified user', function () {
    User::factory()->withoutTwoFactor()->unverified()->create();

    $this->artisan('programs:nudge-spring')->assertSuccessful();

    Mail::assertNothingQueued();
});

test('does not queue nudge when user uploaded a program since May 1st', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    Program::factory()->create([
        'user_id' => $user->id,
        'created_at' => Carbon::create(now()->year, 5, 15),
    ]);

    $this->artisan('programs:nudge-spring')->assertSuccessful();

    Mail::assertNothingQueued();
});

test('queues nudge when user only has a program uploaded before May 1st', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    Program::factory()->create([
        'user_id' => $user->id,
        'created_at' => Carbon::create(now()->year, 4, 30),
    ]);

    $this->artisan('programs:nudge-spring')->assertSuccessful();

    Mail::assertQueued(SpringConcertProgramNudge::class, function (SpringConcertProgramNudge $mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

test('queues nudge to multiple eligible users', function () {
    User::factory()->withoutTwoFactor()->count(3)->create();

    $this->artisan('programs:nudge-spring')->assertSuccessful();

    Mail::assertQueued(SpringConcertProgramNudge::class, 3);
});

test('dry run reports count without queuing', function () {
    User::factory()->withoutTwoFactor()->count(2)->create();

    $this->artisan('programs:nudge-spring', ['--dry-run' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('2 user(s)');

    Mail::assertNothingQueued();
});

test('preview mode sends only to founder', function () {
    User::factory()->withoutTwoFactor()->count(3)->create();
    $founder = User::factory()->withoutTwoFactor()->create(['email' => 'founder@example.com']);

    $this->artisan('programs:nudge-spring', ['--preview' => true])->assertSuccessful();

    Mail::assertQueued(SpringConcertProgramNudge::class, 1);
    Mail::assertQueued(SpringConcertProgramNudge::class, function (SpringConcertProgramNudge $mail) use ($founder) {
        return $mail->hasTo($founder->email);
    });
});
