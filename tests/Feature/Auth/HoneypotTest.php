<?php

use App\Models\User;
use Spatie\Honeypot\EncryptedTime;

test('login form contains honeypot fields', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('valid_from');
});

test('register form contains honeypot fields', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('valid_from');
});

test('login is blocked when honeypot field is filled', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $validFrom = (string) EncryptedTime::create(now()->subMinutes(1));

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'my_name' => 'I am a bot',
        'valid_from' => $validFrom,
    ]);

    $this->assertGuest();
});

test('register is blocked when honeypot field is filled', function () {
    $validFrom = (string) EncryptedTime::create(now()->subMinutes(1));

    $response = $this->post(route('register.store'), [
        'name' => 'Bot User',
        'email' => 'bot@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'my_name' => 'I am a bot',
        'valid_from' => $validFrom,
    ]);

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);
});

test('login succeeds with valid honeypot fields', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $validFrom = (string) EncryptedTime::create(now()->subMinutes(1));

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'my_name' => '',
        'valid_from' => $validFrom,
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('register succeeds with valid honeypot fields', function () {
    $validFrom = (string) EncryptedTime::create(now()->subMinutes(1));

    $response = $this->post(route('register.store'), [
        'name' => 'Real User',
        'email' => 'real@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'my_name' => '',
        'valid_from' => $validFrom,
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['email' => 'real@example.com']);
});
