<?php

declare(strict_types=1);

use App\Enums\RepertoireQuerySource;
use App\Jobs\ProcessRepertoireSearch;
use App\Livewire\SongTitles\Index;
use App\Models\RepertoireQuery;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('ask ai requires a query of at least 5 characters', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Queue::fake();

    Livewire::test(Index::class)
        ->set('aiQuery', 'hi')
        ->call('askAi')
        ->assertHasErrors(['aiQuery' => 'min']);

    Queue::assertNotPushed(ProcessRepertoireSearch::class);
});

test('ask ai dispatches a queued search for the authenticated user without restricting the catalog when compliant', function () {
    $user = User::factory()->create(); // fresh user is within the grace period, so canViewAll() is true
    $this->actingAs($user);

    Queue::fake();

    Livewire::test(Index::class)
        ->set('aiQuery', 'High school SATB pieces easy for the men, hard for the ladies')
        ->call('askAi')
        ->assertSet('aiSearching', true);

    $repertoireQuery = RepertoireQuery::sole();

    expect($repertoireQuery->user_id)->toBe($user->id)
        ->and($repertoireQuery->source)->toBe(RepertoireQuerySource::SongTitles);

    Queue::assertPushed(ProcessRepertoireSearch::class, function (ProcessRepertoireSearch $job) use ($repertoireQuery) {
        return $job->repertoireQueryId === $repertoireQuery->id
            && $job->restrictToOwnCatalog === false;
    });
});

test('ask ai restricts the candidate catalog to the user\'s own programs when they have not unlocked All', function () {
    $user = User::factory()->create(['created_at' => now()->subDays(30)]); // past the grace period with 0 programs => red status
    $this->actingAs($user);

    Queue::fake();

    Livewire::test(Index::class)
        ->set('aiQuery', 'Something festive for a winter concert')
        ->call('askAi');

    Queue::assertPushed(ProcessRepertoireSearch::class, function (ProcessRepertoireSearch $job) {
        return $job->restrictToOwnCatalog === true;
    });
});

test('polling picks up a completed search result from the cache', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $repertoireQuery = RepertoireQuery::factory()->forUser($user)->create([
        'response' => ['query_interpretation' => [], 'results' => []],
    ]);

    $component = Livewire::test(Index::class)
        ->set('aiRequestId', 'test-request-id')
        ->set('aiSearching', true);

    Cache::put('repertoire_search_test-request-id', [
        'status' => 'completed',
        'repertoire_query_id' => $repertoireQuery->id,
    ], now()->addMinutes(5));

    $component->call('checkAiSearchStatus')
        ->assertSet('aiSearching', false)
        ->assertSet('aiResultQueryId', $repertoireQuery->id);
});

test('polling surfaces a failed search from the cache', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire::test(Index::class)
        ->set('aiRequestId', 'test-request-id-2')
        ->set('aiSearching', true);

    Cache::put('repertoire_search_test-request-id-2', [
        'status' => 'failed',
        'error' => 'The AI service is temporarily busy. Please try again in a few minutes.',
    ], now()->addMinutes(5));

    $component->call('checkAiSearchStatus')
        ->assertSet('aiSearching', false)
        ->assertSet('aiError', 'The AI service is temporarily busy. Please try again in a few minutes.');
});

test('reset ai search clears search state', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('aiQuery', 'Something')
        ->set('aiResultQueryId', 1)
        ->set('aiError', 'oops')
        ->call('resetAiSearch')
        ->assertSet('aiQuery', '')
        ->assertSet('aiResultQueryId', null)
        ->assertSet('aiError', null);
});

test('shows the AI disclaimer when results are present', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $repertoireQuery = RepertoireQuery::factory()->forUser($user)->create([
        'response' => [
            'query_interpretation' => [],
            'results' => [
                [
                    'song_title' => 'Ave Maria',
                    'composer' => 'Franz Biebl',
                    'arranger' => null,
                    'voicing' => 'SATB',
                    'source' => 'web_knowledge',
                    'matched_song_title_id' => null,
                    'difficulty_by_part' => ['soprano' => 'unknown', 'alto' => 'unknown', 'tenor' => 'unknown', 'bass' => 'unknown'],
                    'fit_rationale' => 'Fits the request.',
                    'youtube_url' => null,
                    'youtube_confidence' => null,
                    'citation_urls' => [],
                    'tags' => [],
                ],
            ],
        ],
    ]);

    Livewire::test(Index::class)
        ->set('aiResultQueryId', $repertoireQuery->id)
        ->assertSee(__('AI-estimated difficulty and matches — not a substitute for reviewing the score yourself.'));
});

test('does not show the AI disclaimer when there are no results', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $repertoireQuery = RepertoireQuery::factory()->forUser($user)->create([
        'response' => ['query_interpretation' => [], 'results' => []],
    ]);

    Livewire::test(Index::class)
        ->set('aiResultQueryId', $repertoireQuery->id)
        ->assertDontSee(__('AI-estimated difficulty and matches — not a substitute for reviewing the score yourself.'));
});

test('switching to the ai search tab still renders successfully', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('mode', 'ai_search')
        ->assertStatus(200)
        ->assertSee(__('Ask about repertoire'));
});
