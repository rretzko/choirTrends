<?php

declare(strict_types=1);

use App\Services\TurnstileVerificationService;
use Illuminate\Support\Facades\Http;

test('returns true when Cloudflare confirms success', function () {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => true]),
    ]);

    expect((new TurnstileVerificationService)->verify('a-valid-token', '203.0.113.5'))->toBeTrue();
});

test('returns false when Cloudflare reports failure', function () {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']]),
    ]);

    expect((new TurnstileVerificationService)->verify('a-bad-token', '203.0.113.5'))->toBeFalse();
});

test('returns false without making a request when the token is empty', function () {
    Http::fake();

    expect((new TurnstileVerificationService)->verify(null, '203.0.113.5'))->toBeFalse()
        ->and((new TurnstileVerificationService)->verify('', '203.0.113.5'))->toBeFalse();

    Http::assertNothingSent();
});

test('returns false when the verification request itself fails', function () {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response([], 500),
    ]);

    expect((new TurnstileVerificationService)->verify('a-token', '203.0.113.5'))->toBeFalse();
});
