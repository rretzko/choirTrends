<?php

declare(strict_types=1);

use App\Models\Artist;
use App\Models\ArtistRepresentation;
use App\Models\DigitalProgram;
use App\Models\DigitalProgramSongSetting;
use App\Models\Program;
use App\Models\SongTitle;
use App\Models\SongTitleAssessment;
use App\Models\SongTitleDescription;
use App\Models\SongTitleDifficultyObservation;
use App\Models\SongTitleTag;
use App\Models\User;
use App\Models\UserSongFile;
use App\Models\UserSongLyrics;
use App\Services\CatalogMergeService;

function merger(): CatalogMergeService
{
    return app(CatalogMergeService::class);
}

// ── Song title merges ────────────────────────────────────────────────────────

test('merging a song moves its performances and enrichment to the keeper', function () {
    $keeper = SongTitle::factory()->create();
    $duplicate = SongTitle::factory()->create();
    $program = Program::factory()->create();
    $program->songTitles()->attach($duplicate->id, ['sort_order' => 1, 'ensemble_sort_order' => 1]);

    $description = SongTitleDescription::factory()->create(['song_title_id' => $duplicate->id]);
    $observation = SongTitleDifficultyObservation::factory()->create(['song_title_id' => $duplicate->id]);
    $tag = SongTitleTag::factory()->create(['song_title_id' => $duplicate->id, 'tag' => 'festival']);
    $assessment = SongTitleAssessment::factory()->create(['song_title_id' => $duplicate->id]);
    $lyrics = UserSongLyrics::factory()->create(['song_title_id' => $duplicate->id]);
    $file = UserSongFile::factory()->create(['song_title_id' => $duplicate->id]);
    $setting = DigitalProgramSongSetting::query()->create([
        'digital_program_id' => DigitalProgram::factory()->create()->id,
        'song_title_id' => $duplicate->id,
        'program_notes' => 'Notes',
    ]);

    $report = merger()->mergeSongTitle($keeper, $duplicate);

    expect($report['performances_moved'])->toBe(1)
        ->and(SongTitle::find($duplicate->id))->toBeNull()
        ->and($program->fresh()->songTitles->pluck('id')->all())->toBe([$keeper->id]);

    foreach ([$description, $observation, $tag, $assessment, $lyrics, $file, $setting] as $child) {
        expect($child->fresh()?->song_title_id)->toBe($keeper->id);
    }
});

test('merging a song keeps the keeper copy when both have the same unique child row', function () {
    $keeper = SongTitle::factory()->create();
    $duplicate = SongTitle::factory()->create();
    $director = User::factory()->create();
    $digitalProgram = DigitalProgram::factory()->create();
    $sharedProgram = Program::factory()->create();
    $sharedProgram->songTitles()->attach($keeper->id, ['sort_order' => 1, 'ensemble_sort_order' => 1]);
    $sharedProgram->songTitles()->attach($duplicate->id, ['sort_order' => 2, 'ensemble_sort_order' => 1]);

    $keeperLyrics = UserSongLyrics::factory()->create(['song_title_id' => $keeper->id, 'user_id' => $director->id, 'content' => 'keeper']);
    UserSongLyrics::factory()->create(['song_title_id' => $duplicate->id, 'user_id' => $director->id, 'content' => 'duplicate']);
    SongTitleAssessment::factory()->create(['song_title_id' => $keeper->id, 'grade_level_context' => 'high_school']);
    SongTitleAssessment::factory()->create(['song_title_id' => $duplicate->id, 'grade_level_context' => 'high_school']);
    DigitalProgramSongSetting::query()->create(['digital_program_id' => $digitalProgram->id, 'song_title_id' => $keeper->id]);
    DigitalProgramSongSetting::query()->create(['digital_program_id' => $digitalProgram->id, 'song_title_id' => $duplicate->id]);
    SongTitleTag::factory()->create(['song_title_id' => $keeper->id, 'tag' => 'Sacred']);
    SongTitleTag::factory()->create(['song_title_id' => $duplicate->id, 'tag' => 'sacred']);

    merger()->mergeSongTitle($keeper, $duplicate);

    expect(UserSongLyrics::query()->where('song_title_id', $keeper->id)->pluck('id')->all())->toBe([$keeperLyrics->id])
        ->and(SongTitleAssessment::query()->where('song_title_id', $keeper->id)->count())->toBe(1)
        ->and(DigitalProgramSongSetting::query()->where('song_title_id', $keeper->id)->count())->toBe(1)
        ->and(SongTitleTag::query()->where('song_title_id', $keeper->id)->count())->toBe(1)
        ->and($sharedProgram->fresh()->songTitles->pluck('id')->all())->toBe([$keeper->id]);
});

test('merging a song into itself does nothing', function () {
    $song = SongTitle::factory()->create();

    expect(merger()->mergeSongTitle($song, $song)['performances_moved'])->toBe(0)
        ->and($song->fresh())->not->toBeNull();
});

// ── Artist merges ────────────────────────────────────────────────────────────

test('merging artists reassigns composer and arranger roles', function () {
    $keeper = Artist::factory()->create();
    $duplicate = Artist::factory()->create();
    $composed = SongTitle::factory()->create(['composer_id' => $duplicate->id, 'arranger_id' => null]);
    $arranged = SongTitle::factory()->create(['composer_id' => null, 'arranger_id' => $duplicate->id]);
    $both = SongTitle::factory()->create(['composer_id' => $duplicate->id, 'arranger_id' => $duplicate->id]);

    $report = merger()->mergeArtist($keeper, $duplicate);

    expect($report['songs_reassigned'])->toBe(3)
        ->and($composed->fresh()->composer_id)->toBe($keeper->id)
        ->and($arranged->fresh()->arranger_id)->toBe($keeper->id)
        ->and($both->fresh()->only(['composer_id', 'arranger_id']))->toBe(['composer_id' => $keeper->id, 'arranger_id' => $keeper->id])
        ->and(Artist::find($duplicate->id))->toBeNull();
});

test('merging artists folds colliding songs together without losing enrichment', function () {
    $keeper = Artist::factory()->create(['artist_name' => 'George Frideric Handel']);
    $duplicate = Artist::factory()->create(['artist_name' => 'G. F. Handel']);
    $keeperSong = SongTitle::factory()->create(['song_title' => 'Hallelujah Chorus', 'composer_id' => $keeper->id, 'arranger_id' => null]);
    $duplicateSong = SongTitle::factory()->create(['song_title' => 'Hallelujah Chorus', 'composer_id' => $duplicate->id, 'arranger_id' => null]);
    $program = Program::factory()->create();
    $program->songTitles()->attach($duplicateSong->id, ['sort_order' => 1, 'ensemble_sort_order' => 1]);
    $description = SongTitleDescription::factory()->create(['song_title_id' => $duplicateSong->id]);

    $report = merger()->mergeArtist($keeper, $duplicate);

    expect($report)->toMatchArray(['songs_reassigned' => 0, 'songs_merged' => 1])
        ->and(SongTitle::find($duplicateSong->id))->toBeNull()
        ->and($program->fresh()->songTitles->pluck('id')->all())->toBe([$keeperSong->id])
        ->and($description->fresh()->song_title_id)->toBe($keeperSong->id);
});

test('merging artists moves publisher representations, dropping exact duplicates', function () {
    $keeper = Artist::factory()->create();
    $duplicate = Artist::factory()->create();
    $existing = ArtistRepresentation::factory()->create(['artist_id' => $keeper->id]);
    ArtistRepresentation::factory()->create([
        'artist_id' => $duplicate->id,
        'representation_category_id' => $existing->representation_category_id,
        'source_url' => $existing->source_url,
    ]);
    $unique = ArtistRepresentation::factory()->create(['artist_id' => $duplicate->id]);

    $report = merger()->mergeArtist($keeper, $duplicate);

    expect($report['representations_moved'])->toBe(1)
        ->and($unique->fresh()->artist_id)->toBe($keeper->id)
        ->and(ArtistRepresentation::query()->where('artist_id', $keeper->id)->count())->toBe(2);
});
