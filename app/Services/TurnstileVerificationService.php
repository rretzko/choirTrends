<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileVerificationService
{
    public function verify(?string $token, ?string $ipAddress): bool
    {
        if (empty($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(10)->post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                array_filter([
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => $ipAddress,
                ])
            );

            return (bool) ($response->json('success') ?? false);
        } catch (\Exception $e) {
            Log::error('Turnstile verification request failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
