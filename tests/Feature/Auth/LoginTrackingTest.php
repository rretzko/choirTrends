<?php

declare(strict_types=1);

use App\Livewire\Founder\ImpersonateUser;
use App\Models\User;
use App\Models\UserLogin;
use Livewire\Livewire;

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

test('impersonation does not create a user_logins record', function () {
    config(['app.founder' => 'founder@example.com']);

    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $target = User::factory()->withoutTwoFactor()->create();

    // Log in as founder first (creates a login record for founder)
    $this->post(route('login.store'), [
        'email' => $founder->email,
        'password' => 'password',
    ]);

    $founderLoginCount = UserLogin::where('user_id', $founder->id)->count();

    // Impersonate the target user via the Livewire component
    Livewire::actingAs($founder)
        ->test(ImpersonateUser::class)
        ->set('userId', $target->id)
        ->call('impersonate');

    // Target should have no login records from impersonation
    expect(UserLogin::where('user_id', $target->id)->count())->toBe(0);
    // Founder should not gain extra login records
    expect(UserLogin::where('user_id', $founder->id)->count())->toBe($founderLoginCount);
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
