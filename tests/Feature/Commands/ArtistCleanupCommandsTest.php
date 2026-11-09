<?php

declare(strict_types=1);

use App\Models\Artist;
use App\Models\SongTitle;
use App\Services\PublicTrendsService;

// ── artists:merge ────────────────────────────────────────────────────────────

test('artists:merge merges every duplicate into the keeper', function () {
    $keeper = Artist::factory()->create(['artist_name' => 'Wolfgang Amadeus Mozart']);
    $first = Artist::factory()->create(['artist_name' => 'W. A. Mozart']);
    $second = Artist::factory()->create(['artist_name' => 'W.A. Mozart']);
    $song = SongTitle::factory()->create(['composer_id' => $first->id]);

    $this->artisan('artists:merge', ['keeper' => $keeper->id, 'duplicates' => [$first->id, $second->id]])
        ->expectsOutputToContain('Keeper: #'.$keeper->id.' Wolfgang Amadeus Mozart')
        ->expectsOutputToContain('Merge complete.')
        ->assertSuccessful();

    expect(Artist::query()->whereKey([$first->id, $second->id])->count())->toBe(0)
        ->and($song->fresh()->composer_id)->toBe($keeper->id);
});

test('artists:merge --dry-run changes nothing', function () {
    $keeper = Artist::factory()->create();
    $duplicate = Artist::factory()->create();
    $song = SongTitle::factory()->create(['composer_id' => $duplicate->id]);

    $this->artisan('artists:merge', ['keeper' => $keeper->id, 'duplicates' => [$duplicate->id], '--dry-run' => true])
        ->expectsOutputToContain('1 song(s) reassigned')
        ->expectsOutputToContain('Dry run complete')
        ->assertSuccessful();

    expect(Artist::find($duplicate->id))->not->toBeNull()
        ->and($song->fresh()->composer_id)->toBe($duplicate->id);
});

test('artists:merge refuses unknown ids and changes nothing', function () {
    $keeper = Artist::factory()->create();
    $duplicate = Artist::factory()->create();

    $this->artisan('artists:merge', ['keeper' => $keeper->id, 'duplicates' => [$duplicate->id, 999999]])
        ->expectsOutputToContain('not found: 999999')
        ->assertFailed();

    expect(Artist::find($duplicate->id))->not->toBeNull();
});

test('artists:merge refuses a missing keeper', function () {
    $this->artisan('artists:merge', ['keeper' => 999999, 'duplicates' => [1]])->assertFailed();
});

test('artists:merge refuses the keeper listed as a duplicate', function () {
    $keeper = Artist::factory()->create();

    $this->artisan('artists:merge', ['keeper' => $keeper->id, 'duplicates' => [$keeper->id]])
        ->expectsOutputToContain('cannot also be listed')
        ->assertFailed();

    expect($keeper->fresh())->not->toBeNull();
});

test('artists:merge clears cached public trends', function () {
    $trends = app(PublicTrendsService::class);
    $calls = 0;
    $trends->remember('probe', function () use (&$calls) {
        return ++$calls;
    });

    $this->artisan('artists:merge', ['keeper' => Artist::factory()->create()->id, 'duplicates' => [Artist::factory()->create()->id]])->assertSuccessful();

    expect($trends->remember('probe', function () use (&$calls) {
        return ++$calls;
    }))->toBe(2);
});

// ── artists:rename ───────────────────────────────────────────────────────────

test('artists:rename fixes the name and re-splits first and last name', function () {
    $artist = Artist::factory()->create(['artist_name' => 'Craig Hella johnson', 'artist_first_name' => 'Craig Hella', 'artist_last_name' => 'johnson']);

    $this->artisan('artists:rename', ['artist' => $artist->id, 'name' => 'Craig Hella Johnson'])->assertSuccessful();

    expect($artist->fresh()->only(['artist_name', 'artist_first_name', 'artist_last_name']))->toBe([
        'artist_name' => 'Craig Hella Johnson',
        'artist_first_name' => 'Craig Hella',
        'artist_last_name' => 'Johnson',
    ]);
});

test('artists:rename with the current name re-parses just that artist', function () {
    $artist = Artist::factory()->create(['artist_name' => 'David Von Kampen', 'artist_first_name' => 'David Von', 'artist_last_name' => 'Kampen']);

    $this->artisan('artists:rename', ['artist' => $artist->id, 'name' => 'David Von Kampen'])->assertSuccessful();

    expect($artist->fresh()->artist_last_name)->toBe('Von Kampen');
});

test('artists:rename --dry-run changes nothing', function () {
    $artist = Artist::factory()->create(['artist_name' => 'Craig Hella johnson']);

    $this->artisan('artists:rename', ['artist' => $artist->id, 'name' => 'Craig Hella Johnson', '--dry-run' => true])
        ->expectsOutputToContain('→ "Craig Hella Johnson"')
        ->assertSuccessful();

    expect($artist->fresh()->artist_name)->toBe('Craig Hella johnson');
});

test('artists:rename refuses a name another artist already has and suggests a merge', function () {
    $existing = Artist::factory()->create(['artist_name' => 'Ralph Vaughan Williams']);
    $typo = Artist::factory()->create(['artist_name' => 'Ralph Vaughn Williams']);

    $this->artisan('artists:rename', ['artist' => $typo->id, 'name' => 'ralph vaughan williams'])
        ->expectsOutputToContain("artists:merge {$existing->id} {$typo->id}")
        ->assertFailed();

    expect($typo->fresh()->artist_name)->toBe('Ralph Vaughn Williams');
});

// ── artists:find-duplicates ──────────────────────────────────────────────────

test('artists:find-duplicates groups initials with full names and suggests the full name as keeper', function () {
    $full = Artist::factory()->create(['artist_name' => 'George Frideric Handel', 'artist_first_name' => 'George Frideric', 'artist_last_name' => 'Handel']);
    $initials = Artist::factory()->create(['artist_name' => 'G. F. Handel', 'artist_first_name' => 'G. F.', 'artist_last_name' => 'Handel']);
    Artist::factory()->create(['artist_name' => 'Unrelated Person', 'artist_first_name' => 'Unrelated', 'artist_last_name' => 'Person']);
    SongTitle::factory()->count(3)->create(['composer_id' => $initials->id]);

    $this->artisan('artists:find-duplicates')
        ->expectsOutputToContain("php artisan artists:merge {$full->id} {$initials->id} --dry-run")
        ->expectsOutputToContain('1 likely duplicate group(s)')
        ->doesntExpectOutputToContain('Unrelated Person')
        ->assertSuccessful();
});

test('artists:find-duplicates matches collaborations that differ only in punctuation', function () {
    $and = Artist::factory()->create(['artist_name' => 'Benj Pasek and Justin Paul', 'artist_first_name' => null, 'artist_last_name' => 'Benj Pasek and Justin Paul']);
    $ampersand = Artist::factory()->create(['artist_name' => 'Benj Pasek & Justin Paul', 'artist_first_name' => null, 'artist_last_name' => 'Benj Pasek & Justin Paul']);

    $this->artisan('artists:find-duplicates')
        ->expectsOutputToContain('Benj Pasek and Justin Paul')
        ->expectsOutputToContain('Benj Pasek & Justin Paul')
        ->assertSuccessful();
});

test('artists:find-duplicates reports when there are none', function () {
    Artist::factory()->create(['artist_name' => 'Only One', 'artist_first_name' => 'Only', 'artist_last_name' => 'One']);

    $this->artisan('artists:find-duplicates')
        ->expectsOutputToContain('No likely duplicates found.')
        ->assertSuccessful();
});

// ── app:fix-artist-name-parsing ──────────────────────────────────────────────

test('the global re-parse refuses to run without --dry-run or --force', function () {
    $artist = Artist::factory()->create(['artist_name' => 'David Von Kampen', 'artist_first_name' => 'David Von', 'artist_last_name' => 'Kampen']);

    $this->artisan('app:fix-artist-name-parsing')->assertFailed();

    expect($artist->fresh()->artist_last_name)->toBe('Kampen');
});

test('the global re-parse --dry-run lists changes without saving them', function () {
    $artist = Artist::factory()->create(['artist_name' => 'David Von Kampen', 'artist_first_name' => 'David Von', 'artist_last_name' => 'Kampen']);

    $this->artisan('app:fix-artist-name-parsing', ['--dry-run' => true])
        ->expectsOutputToContain('[DRY RUN]')
        ->assertSuccessful();

    expect($artist->fresh()->artist_last_name)->toBe('Kampen');
});
