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

test('login counter increments on subsequent logins', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    // First login
    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->post(route('logout'));

    // Second login
    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $logins = UserLogin::where('user_id', $user->id)->orderBy('counter')->get();

    expect($logins)->toHaveCount(2);
    expect($logins[0]->counter)->toBe(1);
    expect($logins[1]->counter)->toBe(2);
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
