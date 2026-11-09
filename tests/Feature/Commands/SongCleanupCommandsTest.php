<?php

declare(strict_types=1);

use App\Console\Commands\SongsFindDuplicates;
use App\Models\Artist;
use App\Models\Program;
use App\Models\SongTitle;
use App\Models\SongTitleTag;
use App\Services\PublicTrendsService;

function performedSong(array $attributes, int $performances = 1): SongTitle
{
    $song = SongTitle::factory()->create($attributes);

    foreach (range(1, $performances) as $i) {
        Program::factory()->create()->songTitles()->attach($song->id, ['sort_order' => 1, 'ensemble_sort_order' => 1]);
    }

    return $song;
}

// ── Title normalization ──────────────────────────────────────────────────────

test('title variants of the same piece normalize to the same key', function (string $title, string $expected) {
    expect(SongsFindDuplicates::normalizeTitle($title))->toBe($expected);
})->with([
    ['The Hallelujah Chorus from The Messiah', 'hallelujah'],
    ['Hallelujah, from Messiah', 'hallelujah'],
    ['Chorus: Hallelujah', 'hallelujah'],
    ['Hallelujah Chorus', 'hallelujah'],
    ['Laudate Pueri (from Vespere solennes de confessore, K. 339', 'laudate pueri'],
    ['Laudate Pueri (from Vespere solennes de confessore), K. 339', 'laudate pueri'],
    ['III. Sequenz: 1. Dies irae', 'dies irae'],
    ['Ave Verum Corpus, K. 618', 'ave verum corpus'],
    ['Measure Me, Sky!', 'measure me sky'],
    ['Does The World Say?', 'does the world say'],
    ['Pärt-like Títle', 'part like title'],
]);

test('numbered works stay distinct', function () {
    expect(SongsFindDuplicates::normalizeTitle('Coronation Anthem No. 4'))
        ->not->toBe(SongsFindDuplicates::normalizeTitle('Coronation Anthem No. 1'))
        ->and(SongsFindDuplicates::normalizeTitle('Psalm 23'))->not->toBe(SongsFindDuplicates::normalizeTitle('Psalm 100'));
});

test('title notes are detected for keeper ranking', function (string $title, bool $hasNotes) {
    expect(SongsFindDuplicates::hasTitleNotes($title))->toBe($hasNotes);
})->with([
    ['Hallelujah, from Messiah', true],
    ['Make Our Garden Grow (from Candide)', true],
    ['Chorus: Hallelujah', true],
    ['Ave Verum Corpus, K. 618', true],
    ['The Road Home', false],
    ['Measure Me, Sky!', false],
]);

// ── songs:find-duplicates ────────────────────────────────────────────────────

test('find-duplicates groups variants by composer and suggests the most performed keeper', function () {
    $handel = Artist::factory()->create(['artist_name' => 'George Frideric Handel']);
    $popular = performedSong(['song_title' => 'Hallelujah Chorus', 'composer_id' => $handel->id, 'arranger_id' => null], 3);
    $variant = performedSong(['song_title' => 'Hallelujah, from Messiah', 'composer_id' => $handel->id, 'arranger_id' => null]);

    $this->artisan('songs:find-duplicates')
        ->expectsOutputToContain('George Frideric Handel')
        ->expectsOutputToContain("php artisan songs:merge {$popular->id} {$variant->id} --dry-run")
        ->expectsOutputToContain('1 likely duplicate group(s)')
        ->assertSuccessful();
});

test('find-duplicates prefers a clean, full title when performances tie', function () {
    $composer = Artist::factory()->create();
    $short = performedSong(['song_title' => 'Road Home', 'composer_id' => $composer->id, 'arranger_id' => null]);
    $full = performedSong(['song_title' => 'The Road Home', 'composer_id' => $composer->id, 'arranger_id' => null]);

    $this->artisan('songs:find-duplicates')
        ->expectsOutputToContain("songs:merge {$full->id} {$short->id}")
        ->assertSuccessful();
});

test('find-duplicates keeps different composers and different arrangers apart', function () {
    $mozart = Artist::factory()->create();
    $byrd = Artist::factory()->create();
    $arranger = Artist::factory()->create();
    performedSong(['song_title' => 'Ave Verum Corpus', 'composer_id' => $mozart->id, 'arranger_id' => null]);
    performedSong(['song_title' => 'Ave Verum Corpus', 'composer_id' => $byrd->id, 'arranger_id' => null]);
    performedSong(['song_title' => 'Ave Verum Corpus, K. 618', 'composer_id' => $mozart->id, 'arranger_id' => $arranger->id]);

    $this->artisan('songs:find-duplicates')
        ->expectsOutputToContain('No likely duplicate song titles found.')
        ->assertSuccessful();
});

test('find-duplicates ignores songs with no composer or arranger', function () {
    performedSong(['song_title' => 'Gloria', 'composer_id' => null, 'arranger_id' => null]);
    performedSong(['song_title' => 'Gloria!', 'composer_id' => null, 'arranger_id' => null]);

    $this->artisan('songs:find-duplicates')
        ->expectsOutputToContain('No likely duplicate song titles found.')
        ->assertSuccessful();
});

test('find-duplicates can be limited to one artist', function () {
    $handel = Artist::factory()->create(['artist_name' => 'Handel Person']);
    $other = Artist::factory()->create(['artist_name' => 'Other Person']);
    performedSong(['song_title' => 'Hallelujah', 'composer_id' => $handel->id, 'arranger_id' => null]);
    performedSong(['song_title' => 'Hallelujah Chorus', 'composer_id' => $handel->id, 'arranger_id' => null]);
    performedSong(['song_title' => 'Winter Wish', 'composer_id' => $other->id, 'arranger_id' => null]);
    performedSong(['song_title' => 'A Winter Wish', 'composer_id' => $other->id, 'arranger_id' => null]);

    $this->artisan('songs:find-duplicates', ['--artist' => $handel->id])
        ->expectsOutputToContain('Handel Person')
        ->doesntExpectOutputToContain('Other Person')
        ->assertSuccessful();
});

// ── songs:merge ──────────────────────────────────────────────────────────────

test('songs:merge moves performances and enrichment to the keeper', function () {
    $keeper = performedSong(['song_title' => 'Hallelujah Chorus']);
    $duplicate = performedSong(['song_title' => 'Hallelujah, from Messiah'], 2);
    $tag = SongTitleTag::factory()->create(['song_title_id' => $duplicate->id]);

    $this->artisan('songs:merge', ['keeper' => $keeper->id, 'duplicates' => [$duplicate->id]])
        ->expectsOutputToContain('2 performance(s) moved')
        ->expectsOutputToContain('Merge complete.')
        ->assertSuccessful();

    expect(SongTitle::find($duplicate->id))->toBeNull()
        ->and($keeper->programs()->count())->toBe(3)
        ->and($tag->fresh()->song_title_id)->toBe($keeper->id);
});

test('songs:merge --dry-run changes nothing', function () {
    $keeper = performedSong(['song_title' => 'Measure Me Sky']);
    $duplicate = performedSong(['song_title' => 'Measure Me, Sky!']);

    $this->artisan('songs:merge', ['keeper' => $keeper->id, 'duplicates' => [$duplicate->id], '--title' => 'Measure Me, Sky!', '--dry-run' => true])
        ->expectsOutputToContain('Dry run complete')
        ->assertSuccessful();

    expect(SongTitle::find($duplicate->id))->not->toBeNull()
        ->and($keeper->fresh()->song_title)->toBe('Measure Me Sky');
});

test('songs:merge --title can give the keeper a duplicate title once the duplicate is merged', function () {
    $composer = Artist::factory()->create();
    $keeper = performedSong(['song_title' => 'Measure Me Sky', 'composer_id' => $composer->id, 'arranger_id' => null], 2);
    $duplicate = performedSong(['song_title' => 'Measure Me, Sky!', 'composer_id' => $composer->id, 'arranger_id' => null]);

    $this->artisan('songs:merge', ['keeper' => $keeper->id, 'duplicates' => [$duplicate->id], '--title' => 'Measure Me, Sky!'])
        ->expectsOutputToContain('retitled keeper')
        ->assertSuccessful();

    expect($keeper->fresh()->song_title)->toBe('Measure Me, Sky!');
});

test('songs:merge --title refuses a title another un-merged song already has and rolls back', function () {
    $composer = Artist::factory()->create();
    $keeper = performedSong(['song_title' => 'Road Home', 'composer_id' => $composer->id, 'arranger_id' => null]);
    $duplicate = performedSong(['song_title' => 'Road Home!', 'composer_id' => $composer->id, 'arranger_id' => null]);
    performedSong(['song_title' => 'The Road Home', 'composer_id' => $composer->id, 'arranger_id' => null]);

    $this->artisan('songs:merge', ['keeper' => $keeper->id, 'duplicates' => [$duplicate->id], '--title' => 'The Road Home'])
        ->expectsOutputToContain('list it as a duplicate instead')
        ->assertFailed();

    expect(SongTitle::find($duplicate->id))->not->toBeNull()
        ->and($keeper->fresh()->song_title)->toBe('Road Home');
});

test('songs:merge refuses unknown ids and the keeper as a duplicate', function () {
    $keeper = performedSong(['song_title' => 'Keeper']);

    $this->artisan('songs:merge', ['keeper' => $keeper->id, 'duplicates' => [999999]])->assertFailed();
    $this->artisan('songs:merge', ['keeper' => $keeper->id, 'duplicates' => [$keeper->id]])->assertFailed();
    $this->artisan('songs:merge', ['keeper' => 999999, 'duplicates' => [$keeper->id]])->assertFailed();

    expect($keeper->fresh())->not->toBeNull();
});

test('songs:merge clears cached public trends', function () {
    $trends = app(PublicTrendsService::class);
    $calls = 0;
    $trends->remember('probe', function () use (&$calls) {
        return ++$calls;
    });

    $this->artisan('songs:merge', ['keeper' => performedSong(['song_title' => 'A'])->id, 'duplicates' => [performedSong(['song_title' => 'B'])->id]])->assertSuccessful();

    expect($trends->remember('probe', function () use (&$calls) {
        return ++$calls;
    }))->toBe(2);
});
