<?php

declare(strict_types=1);

use App\Enums\AuthorshipType;
use App\Enums\EnsembleType;
use App\Models\Artist;
use App\Models\Ensemble;
use App\Models\SongTitle;
use App\Models\SongTitleDescription;
use App\Models\SongTitleDifficultyObservation;
use App\Models\SongTitleTag;
use App\Models\User;
use App\Services\SongEnrichmentService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.anthropic.api_key' => 'test-key']);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function fakeSongProfileResponse(array $overrides = []): array
{
    return [
        'stop_reason' => 'tool_use',
        'content' => [
            ['type' => 'text', 'text' => 'Researching…'],
            [
                'type' => 'tool_use',
                'name' => 'submit_song_profile',
                'input' => array_merge([
                    'piece_identified' => true,
                    'song_description' => 'A luminous setting of a winter text with sustained harmonies.',
                    'tags' => ['Atmospheric', 'winter', 'a cappella', 'Ola Gjeilo', 'winter', 'Concert', 'festival'],
                    'difficulty_by_part' => ['soprano' => 'moderate', 'alto' => 'moderate', 'tenor' => 'easy', 'bass' => 'unknown'],
                    'difficulty_source' => 'ai',
                    'citation_urls' => ['https://example.com/tundra'],
                    'notes' => 'Profiled the SATB edition.',
                ], $overrides),
            ],
        ],
    ];
}

function enrichableSong(): SongTitle
{
    $composer = Artist::factory()->create(['artist_name' => 'Ola Gjeilo', 'artist_first_name' => 'Ola', 'artist_last_name' => 'Gjeilo']);
    $song = SongTitle::factory()->create(['song_title' => 'Tundra', 'composer_id' => $composer->id, 'arranger_id' => null]);
    $ensemble = Ensemble::factory()->create(['type' => EnsembleType::Satb]);

    foreach (range(1, 3) as $i) {
        performAtNewSchool([$song], [], [], ['ensemble_id' => $ensemble->id]);
    }

    return $song;
}

// ── Service ──────────────────────────────────────────────────────────────────

test('enrichment records an ai description, cleaned tags and difficulty', function () {
    Http::fake(['api.anthropic.com/*' => Http::response(fakeSongProfileResponse())]);
    $song = enrichableSong();

    $result = app(SongEnrichmentService::class)->enrich($song);

    expect($result['identified'])->toBeTrue()
        ->and($result['recorded'])->toBe(['description', 'tags', 'difficulty'])
        ->and(SongTitleDescription::query()->where('song_title_id', $song->id)->sole()->only(['description', 'authorship_type']))
        ->toBe(['description' => 'A luminous setting of a winter text with sustained harmonies.', 'authorship_type' => AuthorshipType::Ai])
        ->and(SongTitleTag::query()->where('song_title_id', $song->id)->pluck('tag')->sort()->values()->all())
        ->toBe(['a cappella', 'atmospheric', 'winter'])
        ->and(SongTitleDifficultyObservation::query()->where('song_title_id', $song->id)->count())->toBe(3);
});

test('the request tells claude which voicings school choirs performed', function () {
    Http::fake(['api.anthropic.com/*' => Http::response(fakeSongProfileResponse())]);

    app(SongEnrichmentService::class)->enrich(enrichableSong());

    Http::assertSent(function (Request $request): bool {
        $message = $request['messages'][0]['content'];

        return str_contains($message, 'Title: Tundra')
            && str_contains($message, 'Composer: Ola Gjeilo')
            && str_contains($message, 'school choirs used when performing it (from their concert programs): SATB')
            && collect($request['tools'])->pluck('name')->all() === ['web_search', 'submit_song_profile']
            && ! array_key_exists('temperature', $request->data());
    });
});

test('nothing is recorded when the piece cannot be identified', function () {
    Http::fake(['api.anthropic.com/*' => Http::response(fakeSongProfileResponse([
        'piece_identified' => false,
        'notes' => 'Several unrelated pieces share this title.',
    ]))]);
    $song = enrichableSong();

    $result = app(SongEnrichmentService::class)->enrich($song);

    expect($result)->toBe(['identified' => false, 'recorded' => [], 'notes' => 'Several unrelated pieces share this title.'])
        ->and(SongTitleDescription::query()->where('song_title_id', $song->id)->exists())->toBeFalse()
        ->and(SongTitleTag::query()->where('song_title_id', $song->id)->exists())->toBeFalse();
});

test('enrichment only fills gaps unless asked to refresh', function () {
    Http::fake(['api.anthropic.com/*' => Http::response(fakeSongProfileResponse())]);
    $song = enrichableSong();
    SongTitleDescription::factory()->create(['song_title_id' => $song->id, 'description' => 'Existing AI summary.']);

    $result = app(SongEnrichmentService::class)->enrich($song);

    expect($result['recorded'])->toBe(['tags', 'difficulty'])
        ->and(SongTitleDescription::query()->where('song_title_id', $song->id)->sole()->description)->toBe('Existing AI summary.');
});

test('refresh replaces earlier machine-made data but keeps director data and the publisher description', function () {
    Http::fake(['api.anthropic.com/*' => Http::response(fakeSongProfileResponse())]);
    $song = enrichableSong();
    SongTitleDescription::factory()->create(['song_title_id' => $song->id, 'description' => 'An SSAA treble work.']);
    $publisherDescription = SongTitleDescription::factory()->create(['song_title_id' => $song->id, 'description' => 'Publisher copy.', 'authorship_type' => AuthorshipType::Publisher->value]);
    SongTitleTag::factory()->create(['song_title_id' => $song->id, 'tag' => 'ssaa']);
    $machinePublisherTag = SongTitleTag::factory()->create(['song_title_id' => $song->id, 'tag' => 'treble', 'authorship_type' => AuthorshipType::Publisher->value]);
    $director = User::factory()->create();
    $directorTag = SongTitleTag::factory()->create(['song_title_id' => $song->id, 'tag' => 'my-tag', 'authorship_type' => AuthorshipType::User->value, 'authorship_id' => $director->id]);
    $directorObservation = SongTitleDifficultyObservation::factory()->create([
        'song_title_id' => $song->id,
        'authorship_type' => AuthorshipType::User->value,
        'authorship_id' => $director->id,
    ]);

    app(SongEnrichmentService::class)->enrich($song, replaceExistingAi: true);

    expect(SongTitleDescription::query()->where('song_title_id', $song->id)->where('authorship_type', AuthorshipType::Ai->value)->pluck('description')->all())
        ->toBe(['A luminous setting of a winter text with sustained harmonies.'])
        ->and(SongTitleTag::query()->where('song_title_id', $song->id)->pluck('tag')->all())->not->toContain('ssaa')
        ->and($machinePublisherTag->fresh())->toBeNull()
        ->and($publisherDescription->fresh())->not->toBeNull()
        ->and($directorTag->fresh())->not->toBeNull()
        ->and($directorObservation->fresh())->not->toBeNull();
});

test('refresh replaces machine-made publisher-labelled difficulty instead of duplicating it', function () {
    Http::fake(['api.anthropic.com/*' => Http::response(fakeSongProfileResponse())]);
    $song = enrichableSong();
    SongTitleDifficultyObservation::factory()->count(4)->create([
        'song_title_id' => $song->id,
        'authorship_type' => AuthorshipType::Publisher->value,
        'authorship_id' => null,
    ]);

    app(SongEnrichmentService::class)->enrich($song, replaceExistingAi: true);

    expect(SongTitleDifficultyObservation::query()->where('song_title_id', $song->id)->count())->toBe(3);
});

test('a refusal or missing tool call is reported as an error', function (array $response) {
    Http::fake(['api.anthropic.com/*' => Http::response($response)]);

    app(SongEnrichmentService::class)->enrich(enrichableSong());
})->with([
    'refusal' => [['stop_reason' => 'refusal', 'content' => []]],
    'no tool call' => [['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => 'I could not find it.']]]],
])->throws(RuntimeException::class);

// ── songs:enrich command ─────────────────────────────────────────────────────

test('songs:enrich --public-missing --dry-run lists public songs lacking data without calling the ai', function () {
    Http::fake();
    $song = enrichableSong();
    $hidden = SongTitle::factory()->create(['song_title' => 'Rarely Sung']);
    performAtNewSchool([$hidden]);

    $this->artisan('songs:enrich', ['--public-missing' => true, '--dry-run' => true])
        ->expectsOutputToContain(sprintf('#%-5d %-50s missing: description, tags, difficulty', $song->id, 'Tundra'))
        ->doesntExpectOutputToContain('Rarely Sung')
        ->expectsOutputToContain('1 song(s) would be researched')
        ->assertSuccessful();

    Http::assertNothingSent();
});

test('songs:enrich --public-missing skips songs that already have full data', function () {
    Http::fake();
    $song = enrichableSong();
    SongTitleDescription::factory()->create(['song_title_id' => $song->id]);
    SongTitleTag::factory()->create(['song_title_id' => $song->id]);
    SongTitleDifficultyObservation::factory()->create(['song_title_id' => $song->id]);

    $this->artisan('songs:enrich', ['--public-missing' => true])
        ->expectsOutputToContain('No songs need enrichment.')
        ->assertSuccessful();

    Http::assertNothingSent();
});

test('songs:enrich researches the given songs and reports what was recorded', function () {
    Http::fake(['api.anthropic.com/*' => Http::response(fakeSongProfileResponse())]);
    $song = enrichableSong();

    $this->artisan('songs:enrich', ['songs' => [$song->id]])
        ->expectsOutputToContain('recorded description, tags, difficulty')
        ->expectsOutputToContain('Done: 1 of 1 song(s) enriched.')
        ->assertSuccessful();
});

test('songs:enrich keeps going when one song fails', function () {
    Http::fakeSequence('api.anthropic.com/*')
        ->push(['error' => ['type' => 'invalid_request_error', 'message' => 'bad']], 400)
        ->push(fakeSongProfileResponse());
    $first = enrichableSong();
    $second = SongTitle::factory()->create(['song_title' => 'Second Piece']);

    $this->artisan('songs:enrich', ['songs' => [$first->id, $second->id]])
        ->expectsOutputToContain('failed')
        ->expectsOutputToContain('Done: 1 of 2 song(s) enriched.')
        ->assertSuccessful();
});

test('songs:enrich requires ids or --public-missing and rejects unknown ids', function () {
    $this->artisan('songs:enrich')->assertFailed();
    $this->artisan('songs:enrich', ['songs' => [999999]])->expectsOutputToContain('not found: 999999')->assertFailed();
});

test('song pages show the enriched summary and difficulty', function () {
    Http::fake(['api.anthropic.com/*' => Http::response(fakeSongProfileResponse())]);
    $song = enrichableSong();

    $this->artisan('songs:enrich', ['songs' => [$song->id]])->assertSuccessful();

    $this->get(route('songs.show', ['id' => $song->id, 'slug' => $song->publicSlug()]))
        ->assertSuccessful()
        ->assertSee('A luminous setting of a winter text with sustained harmonies.')
        ->assertSee('Summary generated with AI.')
        ->assertSee('Moderate');
});
