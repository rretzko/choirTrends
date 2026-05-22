<?php

use App\Enums\ReferralSource;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'viewport' => '1920x1080',
    ]);

    $response->assertSessionHasNoErrors();

    $this->assertAuthenticated();
});

test('new users must verify email before accessing dashboard', function () {
    $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'viewport' => '1920x1080',
    ]);

    $this->assertAuthenticated();

    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('verification.notice'));
});

test('registration page displays email verification disclosure', function () {
    $this->get(route('register'))
        ->assertSee('A verification email will be sent');
});

test('registration page displays how did you find us field', function () {
    $this->get(route('register'))
        ->assertSee('How did you find us?');
});

test('new users can register with a referral source', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'referral_source' => ReferralSource::Facebook->value,
        'viewport' => '1920x1080',
    ]);

    $response->assertSessionHasNoErrors();

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'jane@example.com',
        'referral_source' => ReferralSource::Facebook->value,
    ]);
});

test('new users can register with a referral source and detail', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'referral_source' => ReferralSource::Referral->value,
        'referral_detail' => 'John Smith',
        'viewport' => '1920x1080',
    ]);

    $response->assertSessionHasNoErrors();

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'jane@example.com',
        'referral_source' => ReferralSource::Referral->value,
        'referral_detail' => 'John Smith',
    ]);
});

test('new users can register without a referral source', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'viewport' => '1920x1080',
    ]);

    $response->assertSessionHasNoErrors();

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'jane@example.com',
        'referral_source' => null,
    ]);
});

test('registration rejects invalid referral source', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'referral_source' => 'InvalidSource',
        'viewport' => '1920x1080',
    ]);

    $response->assertSessionHasErrors('referral_source');
});

test('registration is blocked when viewport is missing', function () {
    $this->post(route('register.store'), [
        'name' => 'Bot User',
        'email' => 'bot@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);
});

test('registration is blocked when viewport is empty', function () {
    $this->post(route('register.store'), [
        'name' => 'Bot User',
        'email' => 'bot@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'viewport' => '',
    ]);

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);
});

test('registration is blocked when viewport has invalid format', function () {
    $this->post(route('register.store'), [
        'name' => 'Bot User',
        'email' => 'bot@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'viewport' => 'not-a-viewport',
    ]);

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);
});

test('registration is blocked for a single-word name', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'ScfhJBEmNAfHafAHzdlLAHx',
        'email' => 'bot@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'viewport' => '1920x1080',
    ]);

    $response->assertSessionHasErrors('name');
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);
});

test('registration is blocked for a name with invalid characters', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'B0t Us3r!',
        'email' => 'bot@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'viewport' => '1920x1080',
    ]);

    $response->assertSessionHasErrors('name');
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);
});

test('registration accepts names with hyphens and apostrophes', function () {
    $response = $this->post(route('register.store'), [
        'name' => "Mary-Jane O'Brien",
        'email' => 'maryjane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'viewport' => '1920x1080',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticated();
});

test('registration is blocked for a suspicious gmail dot pattern', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'a.mu.li.g.o.d.8.6@gmail.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'viewport' => '1920x1080',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'a.mu.li.g.o.d.8.6@gmail.com']);
});

test('registration is blocked when normalized gmail matches an existing verified account', function () {
    User::factory()->withoutTwoFactor()->create([
        'email' => 'johnsmith@gmail.com',
        'email_verified_at' => now(),
    ]);

    $response = $this->post(route('register.store'), [
        'name' => 'John Smith',
        'email' => 'john.smith@gmail.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'viewport' => '1920x1080',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertDatabaseMissing('users', ['email' => 'john.smith@gmail.com']);
});

test('registration is allowed when normalized gmail matches only an unverified account', function () {
    User::factory()->withoutTwoFactor()->create([
        'email' => 'johnsmith@gmail.com',
        'email_verified_at' => null,
    ]);

    $response = $this->post(route('register.store'), [
        'name' => 'John Smith',
        'email' => 'john.smith@gmail.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'viewport' => '1920x1080',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticated();
});

test('registration accepts a normal gmail address with two dot segments', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Smith',
        'email' => 'john.smith@gmail.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'viewport' => '1920x1080',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticated();
});

test('registration is blocked after rate limit is exceeded', function () {
    $key = 'register|127.0.0.1';
    RateLimiter::clear($key);

    RateLimiter::hit($key, 60);
    RateLimiter::hit($key, 60);
    RateLimiter::hit($key, 60);

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'user@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'viewport' => '1920x1080',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertDatabaseMissing('users', ['email' => 'user@example.com']);

    RateLimiter::clear($key);
});
