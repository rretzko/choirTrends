<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shared retry/backoff logic for calling the Anthropic Messages API.
 *
 * Requires the consuming class to define $apiKey and $apiVersion properties.
 */
trait CallsAnthropicApi
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function postToAnthropic(array $payload, string $failureContext = 'Anthropic API request failed'): array
    {
        $maxRetries = 2;
        $baseDelay = 2; // seconds
        $attempt = 0;

        while (true) {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
                'content-type' => 'application/json',
            ])->timeout(90)->post('https://api.anthropic.com/v1/messages', array_filter($payload, fn ($value) => $value !== null));

            if ($response->successful()) {
                return $response->json();
            }

            $error = $response->json()['error'] ?? null;
            $errorType = $error['type'] ?? 'unknown';
            $isRetryable = $error && in_array($errorType, [
                'rate_limit_error',
                'overloaded_error',
            ], true);

            if ($attempt >= $maxRetries || ! $isRetryable) {
                $message = match ($errorType) {
                    'overloaded_error' => 'The AI service is temporarily busy. Please try again in a few minutes.',
                    'rate_limit_error' => 'Too many requests. Please wait a moment and try again.',
                    default => "{$failureContext}: ".($error['message'] ?? 'Unknown error'),
                };

                throw new \Exception($message);
            }

            $delay = $errorType === 'overloaded_error'
                ? ($baseDelay * pow(2, $attempt)) + 3
                : $baseDelay * pow(2, $attempt);

            Log::info('Retryable Anthropic API error, retrying', [
                'error_type' => $errorType,
                'attempt' => $attempt + 1,
                'max_retries' => $maxRetries,
                'delay_seconds' => $delay,
            ]);

            sleep($delay);
            $attempt++;
        }
    }
}
