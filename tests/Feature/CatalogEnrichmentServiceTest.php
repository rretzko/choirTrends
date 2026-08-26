<?php

declare(strict_types=1);

use App\Enums\AuthorshipType;
use App\Enums\SongTitleOrigin;
use App\Models\Artist;
use App\Models\RepertoireQuery;
use App\Models\SongTitle;
use App\Models\SongTitleDescription;
use App\Models\SongTitleDifficultyObservation;
use App\Models\SongTitleTag;
use App\Services\CatalogEnrichmentService;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function fakeEnrichmentResult(array $overrides = []): array
{
    return array_merge([
        'song_title' => 'A Brand New Piece',
        'composer' => 'Jane Composer',
        'arranger' => null,
        'voicing' => 'SATB',
        'source' => 'web_knowledge',
        'matched_song_title_id' => null,
        'difficulty_by_part' => [
            'soprano' => 'challenging',
            'alto' => 'moderate',
            'tenor' => 'unknown',
            'bass' => 'n/a',
        ],
        'difficulty_source' => 'ai',
        'fit_rationale' => 'Fits the request.',
        'song_description' => 'A lyrical piece for mixed choir.',
        'description_source' => 'ai',
        'youtube_url' => null,
        'youtube_confidence' => null,
        'citation_urls' => [],
        'tags' => ['sacred', 'Contemporary'],
    ], $overrides);
}

function makeQueryWithResults(array $results): RepertoireQuery
{
    return RepertoireQuery::factory()->create([
        'response' => ['query_interpretation' => [], 'results' => $results],
    ]);
}

test('creates a new ai_discovered SongTitle for an unmatched result with a parsed composer', function () {
    $query = makeQueryWithResults([fakeEnrichmentResult()]);

    (new CatalogEnrichmentService)->enrich($query);

    $songTitle = SongTitle::where('song_title', 'A Brand New Piece')->sole();

    expect($songTitle->origin)->toBe(SongTitleOrigin::AiDiscovered)
        ->and($songTitle->composer->artist_name)->toBe('Jane Composer')
        ->and($songTitle->composer->artist_last_name)->toBe('Composer');
});

test('does not duplicate an existing SongTitle when the same title/composer/arranger already exists', function () {
    $composer = Artist::factory()->create(['artist_name' => 'Jane Composer']);
    $existing = SongTitle::factory()->create([
        'song_title' => 'A Brand New Piece',
        'composer_id' => $composer->id,
        'arranger_id' => null,
        'origin' => SongTitleOrigin::Performed,
    ]);

    $query = makeQueryWithResults([fakeEnrichmentResult()]);

    (new CatalogEnrichmentService)->enrich($query);

    expect(SongTitle::where('song_title', 'A Brand New Piece')->count())->toBe(1)
        ->and($existing->fresh()->origin)->toBe(SongTitleOrigin::Performed);
});

test('records one difficulty observation per rated voice part, skipping n/a and unknown', function () {
    $query = makeQueryWithResults([fakeEnrichmentResult()]);

    (new CatalogEnrichmentService)->enrich($query);

    $songTitle = SongTitle::where('song_title', 'A Brand New Piece')->sole();
    $observations = SongTitleDifficultyObservation::where('song_title_id', $songTitle->id)->get();

    expect($observations)->toHaveCount(2)
        ->and($observations->firstWhere('voice_part', 'soprano')->difficulty_value)->toBe(3)
        ->and($observations->firstWhere('voice_part', 'alto')->difficulty_value)->toBe(2)
        ->and($observations->firstWhere('voice_part', 'tenor'))->toBeNull()
        ->and($observations->firstWhere('voice_part', 'bass'))->toBeNull();
});

test('appends a new AI difficulty observation on every enrichment run rather than overwriting', function () {
    $query = makeQueryWithResults([fakeEnrichmentResult()]);
    $service = new CatalogEnrichmentService;

    $service->enrich($query);
    $service->enrich($query);

    $songTitle = SongTitle::where('song_title', 'A Brand New Piece')->sole();

    expect(SongTitleDifficultyObservation::where('song_title_id', $songTitle->id)->count())->toBe(4);
});

test('records authorship_type=publisher when difficulty_source is publisher', function () {
    $query = makeQueryWithResults([fakeEnrichmentResult(['difficulty_source' => 'publisher'])]);

    (new CatalogEnrichmentService)->enrich($query);

    $songTitle = SongTitle::where('song_title', 'A Brand New Piece')->sole();
    $observation = SongTitleDifficultyObservation::where('song_title_id', $songTitle->id)->first();

    expect($observation->authorship_type)->toBe(AuthorshipType::Publisher);
});

test('does not create a duplicate tag row for the same song/tag/authorship on a second run', function () {
    $query = makeQueryWithResults([fakeEnrichmentResult()]);
    $service = new CatalogEnrichmentService;

    $service->enrich($query);
    $service->enrich($query);

    $songTitle = SongTitle::where('song_title', 'A Brand New Piece')->sole();
    $tags = SongTitleTag::where('song_title_id', $songTitle->id)->pluck('tag')->sort()->values();

    expect($tags->all())->toBe(['contemporary', 'sacred']);
});

test('overwrites the description in place on a second run rather than appending', function () {
    $query = makeQueryWithResults([fakeEnrichmentResult(['song_description' => 'First description.'])]);
    $service = new CatalogEnrichmentService;
    $service->enrich($query);

    $secondQuery = makeQueryWithResults([fakeEnrichmentResult(['song_description' => 'Updated description.'])]);
    $service->enrich($secondQuery);

    $songTitle = SongTitle::where('song_title', 'A Brand New Piece')->sole();
    $descriptions = SongTitleDescription::where('song_title_id', $songTitle->id)->get();

    expect($descriptions)->toHaveCount(1)
        ->and($descriptions->first()->description)->toBe('Updated description.');
});

test('resolves the existing SongTitle by matched_song_title_id instead of creating a new one', function () {
    $songTitle = SongTitle::factory()->create(['origin' => SongTitleOrigin::Performed]);

    $query = makeQueryWithResults([fakeEnrichmentResult([
        'source' => 'internal_catalog',
        'matched_song_title_id' => $songTitle->id,
    ])]);

    (new CatalogEnrichmentService)->enrich($query);

    expect(SongTitle::count())->toBe(1)
        ->and(SongTitleDifficultyObservation::where('song_title_id', $songTitle->id)->count())->toBe(2);
});
