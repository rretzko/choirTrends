<?php

use App\Enums\ReferralSource;

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
    ]);

    $response->assertSessionHasErrors('referral_source');
});
