<?php

use App\Enums\ReferralSource;
use App\Models\User;
use App\Services\TurnstileVerificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);
});

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
        'cf-turnstile-response' => 'test-token',
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
        'cf-turnstile-response' => 'test-token',
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
        'cf-turnstile-response' => 'test-token',
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
        'cf-turnstile-response' => 'test-token',
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
        'cf-turnstile-response' => 'test-token',
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
        'cf-turnstile-response' => 'test-token',
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
        'cf-turnstile-response' => 'test-token',
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
        'cf-turnstile-response' => 'test-token',
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
        'cf-turnstile-response' => 'test-token',
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
        'cf-turnstile-response' => 'test-token',
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
        'cf-turnstile-response' => 'test-token',
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
        'cf-turnstile-response' => 'test-token',
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
        'cf-turnstile-response' => 'test-token',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticated();
});

test('registration is blocked for an email with 6 or more consecutive digits', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'No Thanks',
        'email' => 'nothanks849278194@gmail.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'viewport' => '1920x1080',
        'cf-turnstile-response' => 'test-token',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'nothanks849278194@gmail.com']);
});

test('registration is blocked for a non-gmail email with 6 or more consecutive digits', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Some Bot',
        'email' => 'user123456@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'viewport' => '1920x1080',
        'cf-turnstile-response' => 'test-token',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'user123456@example.com']);
});

test('registration accepts an email with fewer than 6 consecutive digits', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane123@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'viewport' => '1920x1080',
        'cf-turnstile-response' => 'test-token',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticated();
});

test('registration is blocked after rate limit is exceeded', function () {
    $key = 'register|127.0.0.1';
    RateLimiter::clear($key);

    RateLimiter::hit($key, 3600);
    RateLimiter::hit($key, 3600);
    RateLimiter::hit($key, 3600);

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'user@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'viewport' => '1920x1080',
        'cf-turnstile-response' => 'test-token',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertDatabaseMissing('users', ['email' => 'user@example.com']);

    RateLimiter::clear($key);
});

/**
 * @return array<string, string>
 */
function registrationPayload(string $name, string $email = 'person@example.com'): array
{
    return [
        'name' => $name,
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
        'viewport' => '1920x1080',
        'cf-turnstile-response' => 'test-token',
    ];
}

test('keyboard-mash bot names are rejected', function (string $name) {
    $this->post(route('register.store'), registrationPayload($name))
        ->assertSessionHasErrors(['name' => 'Please enter your real first and last name.']);

    $this->assertGuest();
})->with([
    'Bnbvqzqn Mgldnj',
    'Yvrxce Jbhfwszuc',
    'Pxggz Rnzparhcl',
    'Mxlt Ousbzgu',
    'Mrnc Abxjusibk',
    'Lnpug Koybavpxf',
    'Rvjdw Bitoisr',
    'Vruawd Jvhvyv',
]);

test('real names with unusual consonant clusters are accepted', function (string $name) {
    $this->post(route('register.store'), registrationPayload($name))
        ->assertSessionHasNoErrors();

    $this->assertAuthenticated();
})->with([
    'John Brzozowski',
    'Michael Prachthauser',
    'Maura Schwartz',
    'Gina Strohbusch',
    'Thomas McNichols',
    'Bobby Yapchanyk',
    'Dwight Weaver',
    'Miguel Ángel Santaella',
    'Rich Campbell',
    'Mrinal Mkhitaryan',
]);

test('registration page renders the turnstile widget', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('register-turnstile')
        ->assertSee('challenges.cloudflare.com/turnstile/v0/api.js', false);
});

test('registration is rejected without a turnstile token', function () {
    $payload = registrationPayload('Jane Doe');
    unset($payload['cf-turnstile-response']);

    $this->post(route('register.store'), $payload)
        ->assertSessionHasErrors(['cf-turnstile-response' => 'Please complete the verification check and try again.']);

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'person@example.com']);
});

test('registration is rejected when turnstile verification fails', function () {
    $this->mock(TurnstileVerificationService::class)
        ->shouldReceive('verify')
        ->once()
        ->with('test-token', '127.0.0.1')
        ->andReturnFalse();

    $this->post(route('register.store'), registrationPayload('Jane Doe'))
        ->assertSessionHasErrors('cf-turnstile-response');

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'person@example.com']);
});
