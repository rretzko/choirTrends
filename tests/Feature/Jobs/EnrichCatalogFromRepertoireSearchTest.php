<?php

declare(strict_types=1);

use App\Enums\RepertoireQuerySource;
use App\Jobs\EnrichCatalogFromRepertoireSearch;
use App\Jobs\ProcessRepertoireSearch;
use App\Services\RepertoireSearchService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    config([
        'services.anthropic.api_key' => 'test-api-key',
        'services.anthropic.api_version' => '2023-06-01',
        'services.anthropic.repertoire_search_model' => 'claude-sonnet-4-6',
        'services.anthropic.repertoire_search_max_web_searches' => 6,
    ]);
});

function fakeSubmitResultsHttpResponse(array $results): void
{
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                [
                    'type' => 'tool_use',
                    'id' => 'toolu_01',
                    'name' => 'submit_repertoire_results',
                    'input' => [
                        'query_interpretation' => [
                            'voicing' => null, 'school_level' => null,
                            'primary_ask' => 'other', 'interpretation_notes' => 'n/a',
                        ],
                        'results' => $results,
                    ],
                ],
            ],
        ], 200),
    ]);
}

test('dispatches EnrichCatalogFromRepertoireSearch after a successful process() call', function () {
    fakeSubmitResultsHttpResponse([]);
    Queue::fake([EnrichCatalogFromRepertoireSearch::class]);

    $repertoireQuery = (new RepertoireSearchService)->createPendingQuery(
        'Anything festive', RepertoireQuerySource::Welcome, ipAddress: '203.0.113.1'
    );

    (new ProcessRepertoireSearch(Str::uuid()->toString(), $repertoireQuery->id))
        ->handle(app(RepertoireSearchService::class));

    Queue::assertPushed(
        EnrichCatalogFromRepertoireSearch::class,
        fn (EnrichCatalogFromRepertoireSearch $job) => $job->repertoireQueryId === $repertoireQuery->id
    );
});

test('does not dispatch EnrichCatalogFromRepertoireSearch when the search resulted in an error', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'text', 'text' => 'no structured result']],
        ], 200),
    ]);
    Queue::fake([EnrichCatalogFromRepertoireSearch::class]);

    $repertoireQuery = (new RepertoireSearchService)->createPendingQuery(
        'Gibberish', RepertoireQuerySource::Welcome, ipAddress: '203.0.113.2'
    );

    (new ProcessRepertoireSearch(Str::uuid()->toString(), $repertoireQuery->id))
        ->handle(app(RepertoireSearchService::class));

    Queue::assertNotPushed(EnrichCatalogFromRepertoireSearch::class);
});

test('a failure in the enrichment job does not affect the repertoire_search cache status written by ProcessRepertoireSearch', function () {
    fakeSubmitResultsHttpResponse([]);

    $repertoireQuery = (new RepertoireSearchService)->createPendingQuery(
        'Anything festive', RepertoireQuerySource::Welcome, ipAddress: '203.0.113.3'
    );
    $requestId = Str::uuid()->toString();

    (new ProcessRepertoireSearch($requestId, $repertoireQuery->id))
        ->handle(app(RepertoireSearchService::class));

    expect(Cache::get("repertoire_search_{$requestId}")['status'])->toBe('completed');

    // Simulate the enrichment job failing outright — it must never touch this cache key.
    (new EnrichCatalogFromRepertoireSearch(999999))->failed(new Exception('boom'));

    expect(Cache::get("repertoire_search_{$requestId}")['status'])->toBe('completed');
});
