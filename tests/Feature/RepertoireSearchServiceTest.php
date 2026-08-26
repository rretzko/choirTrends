<?php

declare(strict_types=1);

use App\Enums\RepertoireQuerySource;
use App\Models\Program;
use App\Models\RepertoireQuery;
use App\Models\SongTitle;
use App\Models\SongTitleAssessment;
use App\Models\User;
use App\Services\RepertoireSearchService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.anthropic.api_key' => 'test-api-key',
        'services.anthropic.api_version' => '2023-06-01',
        'services.anthropic.repertoire_search_model' => 'claude-sonnet-4-6',
        'services.anthropic.repertoire_search_max_web_searches' => 6,
    ]);
});

function fakeSubmitResultsResponse(array $results, array $interpretation = []): array
{
    return [
        'content' => [
            [
                'type' => 'tool_use',
                'id' => 'toolu_01',
                'name' => 'submit_repertoire_results',
                'input' => [
                    'query_interpretation' => array_merge([
                        'voicing' => 'SATB',
                        'school_level' => 'high_school',
                        'primary_ask' => 'difficulty_balance',
                        'interpretation_notes' => 'Easy tenor/bass, challenging soprano/alto.',
                    ], $interpretation),
                    'results' => $results,
                ],
            ],
        ],
    ];
}

/**
 * Runs the full create-then-process flow, mirroring how the Livewire components
 * and queued job use the service in production.
 */
function runSearch(
    string $query,
    RepertoireQuerySource $source,
    ?User $user = null,
    ?string $ipAddress = null,
    bool $restrictToOwnCatalog = false
): RepertoireQuery {
    $service = new RepertoireSearchService;

    $repertoireQuery = $service->createPendingQuery($query, $source, $user, $ipAddress);

    return $service->process($repertoireQuery, $restrictToOwnCatalog);
}

test('persists a repertoire query with the validated results when a matched candidate is returned', function () {
    $program = Program::factory()->create();
    $songTitle = SongTitle::factory()->create(['song_title' => 'Ave Maria']);
    $program->songTitles()->attach($songTitle);

    Http::fake([
        'api.anthropic.com/*' => Http::response(fakeSubmitResultsResponse([
            [
                'song_title' => 'Ave Maria',
                'composer' => 'Franz Biebl',
                'arranger' => null,
                'voicing' => 'SATB',
                'source' => 'internal_catalog',
                'matched_song_title_id' => $songTitle->id,
                'difficulty_by_part' => [
                    'soprano' => 'challenging',
                    'alto' => 'challenging',
                    'tenor' => 'easy',
                    'bass' => 'easy',
                ],
                'difficulty_source' => 'ai',
                'song_description' => 'A serene Latin motet setting the Ave Maria text.',
                'description_source' => 'ai',
                'fit_rationale' => 'Easy tenor/bass lines with a more demanding soprano/alto divisi.',
                'youtube_url' => 'https://www.youtube.com/watch?v=abc123',
                'youtube_confidence' => 'found_via_search',
                'citation_urls' => [],
                'tags' => [],
            ],
        ]), 200),
    ]);

    $result = runSearch(
        'High school SATB pieces easy for the men, challenging for the ladies',
        RepertoireQuerySource::Welcome,
        ipAddress: '203.0.113.5',
    );

    expect($result)->toBeInstanceOf(RepertoireQuery::class)
        ->and($result->error)->toBeNull()
        ->and($result->ip_address)->toBe('203.0.113.5')
        ->and($result->source)->toBe(RepertoireQuerySource::Welcome)
        ->and($result->response['results'])->toHaveCount(1)
        ->and($result->response['results'][0]['matched_song_title_id'])->toBe($songTitle->id)
        ->and($result->response['results'][0]['source'])->toBe('internal_catalog');
});

test('nulls out a hallucinated matched_song_title_id and demotes the result to web_knowledge', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response(fakeSubmitResultsResponse([
            [
                'song_title' => 'Some Piece Not In The Catalog',
                'composer' => 'A Composer',
                'arranger' => null,
                'voicing' => 'SATB',
                'source' => 'internal_catalog',
                'matched_song_title_id' => 999999,
                'difficulty_by_part' => [
                    'soprano' => 'moderate', 'alto' => 'moderate', 'tenor' => 'moderate', 'bass' => 'moderate',
                ],
                'difficulty_source' => 'ai',
                'song_description' => null,
                'description_source' => null,
                'fit_rationale' => 'Fits the request.',
                'youtube_url' => null,
                'youtube_confidence' => null,
                'citation_urls' => [],
                'tags' => [],
            ],
        ]), 200),
    ]);

    $result = runSearch('Something festive', RepertoireQuerySource::SongTitles);

    expect($result->response['results'][0]['matched_song_title_id'])->toBeNull()
        ->and($result->response['results'][0]['source'])->toBe('web_knowledge');
});

test('strips a non-youtube url and its confidence', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response(fakeSubmitResultsResponse([
            [
                'song_title' => 'Some Piece',
                'composer' => null,
                'arranger' => null,
                'voicing' => 'SATB',
                'source' => 'web_knowledge',
                'matched_song_title_id' => null,
                'difficulty_by_part' => [
                    'soprano' => 'unknown', 'alto' => 'unknown', 'tenor' => 'unknown', 'bass' => 'unknown',
                ],
                'difficulty_source' => null,
                'song_description' => null,
                'description_source' => null,
                'fit_rationale' => 'Fits the request.',
                'youtube_url' => 'https://not-youtube.example.com/watch?v=abc123',
                'youtube_confidence' => 'found_via_search',
                'citation_urls' => [],
                'tags' => [],
            ],
        ]), 200),
    ]);

    $result = runSearch('Something festive', RepertoireQuerySource::SongTitles);

    expect($result->response['results'][0]['youtube_url'])->toBeNull()
        ->and($result->response['results'][0]['youtube_confidence'])->toBeNull();
});

test('caches an assessment for a matched song title for reuse on future queries', function () {
    $program = Program::factory()->create();
    $songTitle = SongTitle::factory()->create();
    $program->songTitles()->attach($songTitle);

    Http::fake([
        'api.anthropic.com/*' => Http::response(fakeSubmitResultsResponse([
            [
                'song_title' => $songTitle->song_title,
                'composer' => null,
                'arranger' => null,
                'voicing' => 'SATB',
                'source' => 'internal_catalog',
                'matched_song_title_id' => $songTitle->id,
                'difficulty_by_part' => [
                    'soprano' => 'challenging', 'alto' => 'challenging', 'tenor' => 'easy', 'bass' => 'easy',
                ],
                'difficulty_source' => 'ai',
                'song_description' => null,
                'description_source' => null,
                'fit_rationale' => 'Fits the request.',
                'youtube_url' => 'https://youtu.be/abc123',
                'youtube_confidence' => 'found_via_search',
                'citation_urls' => [],
                'tags' => [],
            ],
        ]), 200),
    ]);

    runSearch('Easy for men, hard for ladies', RepertoireQuerySource::Welcome);

    $assessment = SongTitleAssessment::where('song_title_id', $songTitle->id)->first();

    expect($assessment)->not->toBeNull()
        ->and($assessment->voicing)->toBe('SATB')
        ->and($assessment->difficulty_by_part['tenor'])->toBe('easy')
        ->and($assessment->youtube_url)->toBe('https://youtu.be/abc123');
});

test('records the error on the existing query record when the AI never calls the results tool', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['type' => 'text', 'text' => 'I was unable to find a clear match.'],
            ],
        ], 200),
    ]);

    $result = runSearch('Gibberish query', RepertoireQuerySource::Welcome, ipAddress: '198.51.100.9');

    expect($result->error)->not->toBeNull()
        ->and($result->response)->toBeNull()
        ->and($result->ip_address)->toBe('198.51.100.9');
});

test('associates the query with an authenticated user and no ip when searched from Song Titles', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    Http::fake([
        'api.anthropic.com/*' => Http::response(fakeSubmitResultsResponse([]), 200),
    ]);

    $result = runSearch('Anything', RepertoireQuerySource::SongTitles, user: $user);

    expect($result->user_id)->toBe($user->id)
        ->and($result->source)->toBe(RepertoireQuerySource::SongTitles);
});

test('createPendingQuery persists immediately, before the AI call runs', function () {
    $repertoireQuery = (new RepertoireSearchService)->createPendingQuery(
        'A query in flight',
        RepertoireQuerySource::Welcome,
        ipAddress: '203.0.113.20',
    );

    expect($repertoireQuery->exists)->toBeTrue()
        ->and($repertoireQuery->response)->toBeNull()
        ->and($repertoireQuery->error)->toBeNull()
        ->and(RepertoireQuery::guestQueriesFrom('203.0.113.20')->count())->toBe(1);
});

test('guestQueriesFrom scope counts only unauthenticated queries from a given ip', function () {
    RepertoireQuery::factory()->count(3)->create(['ip_address' => '203.0.113.10', 'user_id' => null]);
    RepertoireQuery::factory()->create(['ip_address' => '203.0.113.99', 'user_id' => null]);
    RepertoireQuery::factory()->forUser()->create(['ip_address' => null]);

    expect(RepertoireQuery::guestQueriesFrom('203.0.113.10')->count())->toBe(3);
});

test('defaults description_source to ai when song_description is present but description_source is omitted', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response(fakeSubmitResultsResponse([
            [
                'song_title' => 'Some Piece', 'composer' => null, 'arranger' => null, 'voicing' => 'SATB',
                'source' => 'web_knowledge', 'matched_song_title_id' => null,
                'difficulty_by_part' => ['soprano' => 'unknown', 'alto' => 'unknown', 'tenor' => 'unknown', 'bass' => 'unknown'],
                'difficulty_source' => null,
                'song_description' => 'A lyrical, accessible piece for mixed choir.',
                'fit_rationale' => 'Fits the request.', 'youtube_url' => null, 'youtube_confidence' => null,
                'citation_urls' => [], 'tags' => [],
            ],
        ]), 200),
    ]);

    $result = runSearch('Something festive', RepertoireQuerySource::SongTitles);

    expect($result->response['results'][0]['description_source'])->toBe('ai');
});

test('forces description_source to null when song_description is null', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response(fakeSubmitResultsResponse([
            [
                'song_title' => 'Some Piece', 'composer' => null, 'arranger' => null, 'voicing' => 'SATB',
                'source' => 'web_knowledge', 'matched_song_title_id' => null,
                'difficulty_by_part' => ['soprano' => 'unknown', 'alto' => 'unknown', 'tenor' => 'unknown', 'bass' => 'unknown'],
                'difficulty_source' => null,
                'song_description' => null,
                'description_source' => 'publisher',
                'fit_rationale' => 'Fits the request.', 'youtube_url' => null, 'youtube_confidence' => null,
                'citation_urls' => [], 'tags' => [],
            ],
        ]), 200),
    ]);

    $result = runSearch('Something festive', RepertoireQuerySource::SongTitles);

    expect($result->response['results'][0]['description_source'])->toBeNull();
});

test('forces difficulty_source to null when all four parts are unknown or n/a', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response(fakeSubmitResultsResponse([
            [
                'song_title' => 'Some Piece', 'composer' => null, 'arranger' => null, 'voicing' => 'SATB',
                'source' => 'web_knowledge', 'matched_song_title_id' => null,
                'difficulty_by_part' => ['soprano' => 'unknown', 'alto' => 'n/a', 'tenor' => 'unknown', 'bass' => 'n/a'],
                'difficulty_source' => 'ai',
                'song_description' => null, 'description_source' => null,
                'fit_rationale' => 'Fits the request.', 'youtube_url' => null, 'youtube_confidence' => null,
                'citation_urls' => [], 'tags' => [],
            ],
        ]), 200),
    ]);

    $result = runSearch('Something festive', RepertoireQuerySource::SongTitles);

    expect($result->response['results'][0]['difficulty_source'])->toBeNull();
});
