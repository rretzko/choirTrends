<?php

declare(strict_types=1);

use App\Enums\RepertoireQuerySource;
use App\Jobs\ProcessRepertoireSearch;
use App\Livewire\Guest\RepertoireFinder;
use App\Models\RepertoireQuery;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

/**
 * Livewire's test harness dispatches component calls through its own internal
 * RequestBroker, which always resolves request()->ip() to 127.0.0.1 regardless
 * of anything set on the outer test case or container — so these tests use that
 * fixed IP throughout rather than fighting it. The component logic itself is
 * IP-value-agnostic, so coverage is equivalent to using arbitrary IPs.
 */
const TEST_CLIENT_IP = '127.0.0.1';

function fakeTurnstileSuccess(): void
{
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => true]),
        'api.anthropic.com/*' => Http::response(['content' => []]),
    ]);
}

test('requires a query of at least 5 characters', function () {
    Queue::fake();

    Livewire::test(RepertoireFinder::class)
        ->set('query', 'hi')
        ->set('turnstileToken', 'token')
        ->call('askAi')
        ->assertHasErrors(['query' => 'min']);

    Queue::assertNotPushed(ProcessRepertoireSearch::class);
});

test('blocks the search and adds an error when turnstile verification fails', function () {
    Queue::fake();

    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => false]),
    ]);

    Livewire::test(RepertoireFinder::class)
        ->set('query', 'Something festive for a winter concert')
        ->set('turnstileToken', 'bad-token')
        ->call('askAi')
        ->assertHasErrors(['turnstileToken']);

    Queue::assertNotPushed(ProcessRepertoireSearch::class);
    expect(RepertoireQuery::count())->toBe(0);
});

test('a valid submission creates a guest query and dispatches the job', function () {
    Queue::fake();
    fakeTurnstileSuccess();

    Livewire::test(RepertoireFinder::class)
        ->set('query', 'Something festive for a winter concert')
        ->set('turnstileToken', 'good-token')
        ->call('askAi')
        ->assertSet('aiSearching', true)
        ->assertSet('turnstileToken', null);

    $repertoireQuery = RepertoireQuery::sole();

    expect($repertoireQuery->user_id)->toBeNull()
        ->and($repertoireQuery->ip_address)->toBe(TEST_CLIENT_IP)
        ->and($repertoireQuery->source)->toBe(RepertoireQuerySource::Welcome);

    Queue::assertPushed(ProcessRepertoireSearch::class, function (ProcessRepertoireSearch $job) use ($repertoireQuery) {
        return $job->repertoireQueryId === $repertoireQuery->id
            && $job->restrictToOwnCatalog === false;
    });
});

test('remainingQueries respects the configured guest query limit', function () {
    config(['services.repertoire_search.guest_query_limit' => 7]);

    RepertoireQuery::factory()->count(4)->create(['ip_address' => TEST_CLIENT_IP, 'user_id' => null]);

    Livewire::test(RepertoireFinder::class)
        ->assertSee(__(':count free searches left', ['count' => 3]));
});

test('blocks a fourth search from the same ip and shows the registration cta instead of the form', function () {
    config(['services.repertoire_search.guest_query_limit' => 3]);

    RepertoireQuery::factory()->count(3)->create(['ip_address' => TEST_CLIENT_IP, 'user_id' => null]);

    Queue::fake();
    fakeTurnstileSuccess();

    Livewire::test(RepertoireFinder::class)
        ->assertSee(__("You've used your free searches"))
        ->set('query', 'One more search please')
        ->set('turnstileToken', 'good-token')
        ->call('askAi')
        ->assertSet('aiSearching', false);

    Queue::assertNotPushed(ProcessRepertoireSearch::class);
    expect(RepertoireQuery::count())->toBe(3);
});

test('a failed attempt still counts toward the guest throttle', function () {
    config(['services.repertoire_search.guest_query_limit' => 3]);

    RepertoireQuery::factory()->count(2)->create(['ip_address' => TEST_CLIENT_IP, 'user_id' => null, 'error' => 'boom']);

    Queue::fake();
    fakeTurnstileSuccess();

    Livewire::test(RepertoireFinder::class)
        ->assertSee(__('1 free search left'))
        ->set('query', 'Another attempt')
        ->set('turnstileToken', 'good-token')
        ->call('askAi');

    expect(RepertoireQuery::count())->toBe(3);

    Livewire::test(RepertoireFinder::class)
        ->assertSee(__("You've used your free searches"));
});

test('turnstile-verified browser event sets the token', function () {
    Livewire::test(RepertoireFinder::class)
        ->call('setTurnstileToken', 'abc123')
        ->assertSet('turnstileToken', 'abc123')
        ->call('clearTurnstileToken')
        ->assertSet('turnstileToken', null);
});

test('polling picks up a completed search result from the cache', function () {
    $repertoireQuery = RepertoireQuery::factory()->create([
        'ip_address' => TEST_CLIENT_IP,
        'user_id' => null,
        'response' => ['query_interpretation' => [], 'results' => []],
    ]);

    $component = Livewire::test(RepertoireFinder::class)
        ->set('aiRequestId', 'guest-request-id')
        ->set('aiSearching', true);

    Cache::put('repertoire_search_guest-request-id', [
        'status' => 'completed',
        'repertoire_query_id' => $repertoireQuery->id,
    ], now()->addMinutes(5));

    $component->call('checkAiSearchStatus')
        ->assertSet('aiSearching', false)
        ->assertSet('aiResultQueryId', $repertoireQuery->id);
});

test('reset ai search clears state', function () {
    Livewire::test(RepertoireFinder::class)
        ->set('query', 'Something')
        ->set('aiResultQueryId', 1)
        ->set('aiError', 'oops')
        ->call('resetAiSearch')
        ->assertSet('query', '')
        ->assertSet('aiResultQueryId', null)
        ->assertSet('aiError', null);
});

test('renders successfully on a fresh guest visit', function () {
    Livewire::test(RepertoireFinder::class)
        ->assertStatus(200)
        ->assertSee(__('Ask about repertoire in plain language'));
});
