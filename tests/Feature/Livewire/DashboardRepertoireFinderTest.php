<?php

declare(strict_types=1);

use App\Enums\RepertoireQuerySource;
use App\Jobs\ProcessRepertoireSearch;
use App\Livewire\Dashboard\RepertoireFinder;
use App\Models\Program;
use App\Models\RepertoireQuery;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'services.anthropic.api_key' => 'test-api-key',
        'services.anthropic.api_version' => '2023-06-01',
        'services.anthropic.repertoire_search_model' => 'claude-sonnet-4-6',
        'services.anthropic.repertoire_search_max_web_searches' => 6,
        'services.repertoire_search.dashboard_stale_query_limit' => 2,
    ]);
});

test('unlimited queries and no advisory when the user has uploaded within 6 months', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    Program::factory()->for($user)->for($school)->create(['created_at' => now()->subMonths(1)]);

    $this->actingAs($user);

    Livewire::test(RepertoireFinder::class)
        ->assertSet('aiError', null)
        ->assertViewHas('isRecentUpload', true)
        ->assertViewHas('remainingQueries', null)
        ->assertDontSee(__('Keep your unlimited access'));

    Queue::fake();

    Livewire::test(RepertoireFinder::class)
        ->set('query', 'Something festive for a winter concert')
        ->call('askAi')
        ->assertSet('aiSearching', true)
        ->assertSet('aiError', null);

    $repertoireQuery = RepertoireQuery::sole();
    expect($repertoireQuery->user_id)->toBe($user->id)
        ->and($repertoireQuery->source)->toBe(RepertoireQuerySource::Dashboard);
});

test('shows the advisory message with the last upload date when stale', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    Program::factory()->for($user)->for($school)->create(['created_at' => now()->subMonths(8)]);

    $this->actingAs($user);

    $lastUploadDate = $user->fresh()->programs()->latest('created_at')->value('created_at');
    $formattedDate = Carbon::parse($lastUploadDate)->format('l, F j, Y');

    Livewire::test(RepertoireFinder::class)
        ->assertViewHas('isRecentUpload', false)
        ->assertSee(__('Your last program was uploaded on :date. Continue to receive unlimited queries by uploading at least one program every six months.', ['date' => $formattedDate]));
});

test('shows the never-uploaded advisory message when the user has no programs at all', function () {
    $user = User::factory()->create(['created_at' => now()->subYear()]);

    $this->actingAs($user);

    Livewire::test(RepertoireFinder::class)
        ->assertViewHas('isRecentUpload', false)
        ->assertViewHas('lastUploadDate', null)
        ->assertSee(__("You haven't uploaded a program yet. Upload at least one program every six months to receive unlimited queries."));
});

test('stale user is limited to the configured query count and blocked once exhausted', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    Program::factory()->for($user)->for($school)->create(['created_at' => now()->subMonths(8)]);

    RepertoireQuery::factory()->forUser($user)->create([
        'source' => RepertoireQuerySource::Dashboard,
        'created_at' => now()->subDay(),
    ]);
    RepertoireQuery::factory()->forUser($user)->create([
        'source' => RepertoireQuerySource::Dashboard,
        'created_at' => now()->subHours(2),
    ]);

    $this->actingAs($user);

    Queue::fake();

    Livewire::test(RepertoireFinder::class)
        ->assertViewHas('remainingQueries', 0)
        ->set('query', 'One more search please')
        ->call('askAi')
        ->assertSet('aiSearching', false)
        ->assertSet('aiError', __('You have used your queries for now. Upload a program to restore unlimited access.'));

    Queue::assertNotPushed(ProcessRepertoireSearch::class);
});

test('a new upload while stale restores unlimited access and resets the used query count', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    Program::factory()->for($user)->for($school)->create(['created_at' => now()->subMonths(8)]);

    RepertoireQuery::factory()->forUser($user)->create([
        'source' => RepertoireQuerySource::Dashboard,
        'created_at' => now()->subDay(),
    ]);
    RepertoireQuery::factory()->forUser($user)->create([
        'source' => RepertoireQuerySource::Dashboard,
        'created_at' => now()->subHours(2),
    ]);

    $this->actingAs($user);

    Livewire::test(RepertoireFinder::class)
        ->assertViewHas('remainingQueries', 0);

    // Uploading a new program brings the user back within the 6-month window.
    Program::factory()->for($user)->for($school)->create(['created_at' => now()]);

    Queue::fake();

    Livewire::test(RepertoireFinder::class)
        ->assertViewHas('isRecentUpload', true)
        ->assertViewHas('remainingQueries', null)
        ->set('query', 'Something festive for a spring concert')
        ->call('askAi')
        ->assertSet('aiSearching', true)
        ->assertSet('aiError', null);

    Queue::assertPushed(ProcessRepertoireSearch::class);
});

test('founder always has unlimited queries regardless of upload history', function () {
    config(['app.founder' => 'founder@example.com']);
    $user = User::factory()->founder()->create();

    $this->actingAs($user);

    Livewire::test(RepertoireFinder::class)
        ->assertViewHas('isRecentUpload', true)
        ->assertViewHas('remainingQueries', null)
        ->assertDontSee(__('Keep your unlimited access'));
});

test('askAi restricts the candidate catalog to the user\'s own programs when they have not unlocked All', function () {
    $user = User::factory()->create(['created_at' => now()->subDays(30)]); // past the grace period with 0 programs => red status
    $this->actingAs($user);

    Queue::fake();

    Livewire::test(RepertoireFinder::class)
        ->set('query', 'Something festive for a winter concert')
        ->call('askAi');

    Queue::assertPushed(ProcessRepertoireSearch::class, function (ProcessRepertoireSearch $job) {
        return $job->restrictToOwnCatalog === true;
    });
});

test('renders successfully on the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeLivewire(RepertoireFinder::class);
});
