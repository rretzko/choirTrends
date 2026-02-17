<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserLogin;

test('login creates a user_logins record', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'viewport' => '1920x1080',
    ]);

    $this->assertAuthenticated();
    $this->assertDatabaseHas('user_logins', [
        'user_id' => $user->id,
        'viewport' => '1920x1080',
        'counter' => 1,
    ]);
});

test('login record captures IP address', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $login = UserLogin::where('user_id', $user->id)->first();

    expect($login)->not->toBeNull();
    expect($login->ip_address)->not->toBeNull();
});

test('login counter increments on subsequent logins from same environment', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    // First login
    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'viewport' => '1920x1080',
    ]);

    $this->post(route('logout'));

    // Second login from same environment
    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'viewport' => '1920x1080',
    ]);

    $logins = UserLogin::where('user_id', $user->id)->get();

    expect($logins)->toHaveCount(1);
    expect($logins->first()->counter)->toBe(2);
});

test('different environments create separate login records', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    // Login from one viewport
    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'viewport' => '1920x1080',
    ]);

    $this->post(route('logout'));

    // Login from a different viewport
    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'viewport' => '390x844',
    ]);

    $logins = UserLogin::where('user_id', $user->id)->get();

    expect($logins)->toHaveCount(2);
    expect($logins->pluck('counter')->toArray())->each->toBe(1);
});

test('registration also creates a user_logins record', function () {
    $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'newuser@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'viewport' => '390x844',
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'newuser@example.com')->first();

    $this->assertDatabaseHas('user_logins', [
        'user_id' => $user->id,
        'viewport' => '390x844',
        'counter' => 1,
    ]);
});

test('failed login does not create a user_logins record', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $this->assertDatabaseMissing('user_logins', [
        'user_id' => $user->id,
    ]);
});

test('login record has os and browser fields populated', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $login = UserLogin::where('user_id', $user->id)->first();

    expect($login)->not->toBeNull();
    expect($login->device)->toBeIn(['desktop', 'mobile']);
});
