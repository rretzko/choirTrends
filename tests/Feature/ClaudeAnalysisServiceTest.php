<?php

declare(strict_types=1);

use App\Services\ClaudeAnalysisService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.anthropic.api_key' => 'test-api-key',
        'services.anthropic.api_version' => '2023-06-01',
        'services.anthropic.model' => 'claude-3-haiku-20240307',
    ]);
});

test('overloaded error is retried and returns user-friendly message on final failure', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'type' => 'error',
            'error' => [
                'type' => 'overloaded_error',
                'message' => 'Overloaded',
            ],
        ], 529),
    ]);

    $service = new ClaudeAnalysisService;

    $service->analyzeProgram('Sample program text content');
})->throws(\Exception::class, 'The AI service is temporarily busy. Please try again in a few minutes.');

test('rate limit error is retried and returns user-friendly message on final failure', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'type' => 'error',
            'error' => [
                'type' => 'rate_limit_error',
                'message' => 'Rate limit exceeded',
            ],
        ], 429),
    ]);

    $service = new ClaudeAnalysisService;

    $service->analyzeProgram('Sample program text content');
})->throws(\Exception::class, 'Too many requests. Please wait a moment and try again.');

test('non-retryable error throws immediately with descriptive message', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'type' => 'error',
            'error' => [
                'type' => 'invalid_request_error',
                'message' => 'The request body is invalid.',
            ],
        ], 400),
    ]);

    $service = new ClaudeAnalysisService;

    $service->analyzeProgram('Sample program text content');
})->throws(\Exception::class, 'Failed to analyze program: The request body is invalid.');

test('overloaded error recovers on retry', function () {
    $attempt = 0;

    Http::fake(function () use (&$attempt) {
        $attempt++;

        if ($attempt <= 2) {
            return Http::response([
                'type' => 'error',
                'error' => [
                    'type' => 'overloaded_error',
                    'message' => 'Overloaded',
                ],
            ], 529);
        }

        return Http::response([
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode([
                        'event_name' => 'Spring Concert',
                        'event_date' => '2026-03-15',
                        'school_name' => 'Test High School',
                        'director_name' => 'John Smith',
                        'ensembles' => [
                            [
                                'name' => 'Concert Choir',
                                'songs' => [
                                    ['title' => 'Ave Maria', 'composer' => 'Bach', 'arranger' => '', 'notes' => ''],
                                ],
                            ],
                        ],
                    ]),
                ],
            ],
        ], 200);
    });

    $service = new ClaudeAnalysisService;

    $result = $service->analyzeProgram('Sample program text content');

    expect($result)
        ->toBeArray()
        ->and($result['event_name'])->toBe('Spring Concert')
        ->and($result['ensembles'])->toHaveCount(1);
});
