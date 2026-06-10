<?php

declare(strict_types=1);

use App\Mail\NewUserRegistered;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('email is sent to founder when a user is created', function () {
    Mail::fake();
    config(['app.founder' => 'founder@example.com']);

    User::factory()->withoutTwoFactor()->create(['name' => 'Jane Doe']);

    Mail::assertSent(NewUserRegistered::class, function ($mail) {
        return $mail->hasTo('founder@example.com')
            && $mail->user->name === 'Jane Doe';
    });
});

test('email contains correct user data', function () {
    Mail::fake();
    config(['app.founder' => 'founder@example.com']);

    $user = User::factory()->withoutTwoFactor()->create([
        'name' => 'John Smith',
        'email' => 'john@example.com',
    ]);

    Mail::assertSent(NewUserRegistered::class, function ($mail) use ($user) {
        return $mail->user->id === $user->id
            && $mail->user->email === 'john@example.com';
    });
});

test('email is not sent when founder config is null', function () {
    Mail::fake();
    config(['app.founder' => null]);

    User::factory()->withoutTwoFactor()->create();

    Mail::assertNotSent(NewUserRegistered::class);
});

test('email is not sent when the new user is an assistant account', function () {
    Mail::fake();
    config(['app.founder' => 'founder@example.com']);

    $director = User::factory()->withoutTwoFactor()->create();
    Mail::fake();

    User::factory()->withoutTwoFactor()->assistant($director)->create();

    Mail::assertNotSent(NewUserRegistered::class);
});

test('email subject includes user name', function () {
    config(['app.founder' => 'founder@example.com']);

    $user = User::factory()->withoutTwoFactor()->make(['name' => 'Jane Doe']);

    $mailable = new NewUserRegistered($user);

    $mailable->assertHasSubject('[New User] Jane Doe has registered');
});
