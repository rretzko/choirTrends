<?php

declare(strict_types=1);

use App\Models\Artist;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use App\Services\PublicTrendsService;

test('trends page is public, indexable and in the sitemap', function () {
    $response = $this->get(route('trends'));

    $response->assertSuccessful();
    expect($response->headers->get('X-Robots-Tag'))->toBeNull();

    $this->get('/sitemap.xml')->assertSee('<loc>'.route('trends').'</loc>', false);
});

test('trends page has seo title, description and canonical url', function () {
    performAtNewSchool([SongTitle::factory()->create()]);

    $this->get(route('trends'))
        ->assertSee('<title>Trending Choral Repertoire | '.config('app.name').'</title>', false)
        ->assertSee('<meta name="description" content="The most-performed choral pieces, composers and arrangers in school concert programs, based on 1 programs from 1 schools.', false)
        ->assertSee('<link rel="canonical" href="'.route('trends').'">', false);
});

test('a piece performed by enough schools is listed with its composer and arranger', function () {
    $composer = Artist::factory()->create(['artist_name' => 'Elaine Hagenberg']);
    $arranger = Artist::factory()->create(['artist_name' => 'Jay Althouse']);
    $song = SongTitle::factory()->create([
        'song_title' => 'Muusika Nova',
        'composer_id' => $composer->id,
        'arranger_id' => $arranger->id,
    ]);

    foreach (range(1, 3) as $i) {
        performAtNewSchool([$song]);
    }

    $this->get(route('trends'))
        ->assertSee('Muusika Nova')
        ->assertSee('Elaine Hagenberg')
        ->assertSee('Jay Althouse')
        ->assertSee('"@type":"MusicComposition"', false);
});

test('a piece performed by fewer schools than the threshold is hidden', function () {
    $song = SongTitle::factory()->create(['song_title' => 'Rarely Sung Anthem']);

    performAtNewSchool([$song]);
    performAtNewSchool([$song]);

    $this->get(route('trends'))->assertDontSee('Rarely Sung Anthem');
});

test('repeat performances at the same school do not count toward the threshold', function () {
    $song = SongTitle::factory()->create(['song_title' => 'Home School Favorite']);
    $school = School::factory()->create();

    foreach (range(1, 4) as $i) {
        $program = Program::factory()->for(User::factory())->for($school)->create();
        $program->songTitles()->attach($song->id, ['sort_order' => 1, 'ensemble_sort_order' => 1]);
    }

    expect(app(PublicTrendsService::class)->topPieces())->toBeEmpty();
});

test('the threshold is read from config', function () {
    config(['trends.minimum_schools' => 2]);
    $song = SongTitle::factory()->create(['song_title' => 'Two School Song']);

    performAtNewSchool([$song]);
    performAtNewSchool([$song]);

    expect(app(PublicTrendsService::class)->topPieces()->pluck('title'))->toContain('Two School Song');
});

test('pieces are ranked by number of schools', function () {
    $popular = SongTitle::factory()->create(['song_title' => 'Popular Piece']);
    $lessPopular = SongTitle::factory()->create(['song_title' => 'Less Popular Piece']);

    foreach (range(1, 3) as $i) {
        performAtNewSchool([$popular, $lessPopular]);
    }
    performAtNewSchool([$popular]);

    $pieces = app(PublicTrendsService::class)->topPieces();

    expect($pieces->pluck('title')->all())->toBe(['Popular Piece', 'Less Popular Piece'])
        ->and($pieces->first()->schoolCount)->toBe(4);
});

test('composers are aggregated across their pieces and need enough schools', function () {
    $composer = Artist::factory()->create(['artist_name' => 'Ola Gjeilo']);
    $first = SongTitle::factory()->create(['composer_id' => $composer->id]);
    $second = SongTitle::factory()->create(['composer_id' => $composer->id]);

    performAtNewSchool([$first]);
    performAtNewSchool([$second]);
    performAtNewSchool([$first, $second]);

    $composers = app(PublicTrendsService::class)->topComposers();

    expect($composers)->toHaveCount(1)
        ->and($composers->first()->name)->toBe('Ola Gjeilo')
        ->and($composers->first()->schoolCount)->toBe(3)
        ->and($composers->first()->pieceCount)->toBe(2);
});

test('placeholder artists like traditional and folk song are not ranked', function (string $name) {
    $artist = Artist::factory()->create(['artist_name' => $name]);
    $song = SongTitle::factory()->create(['composer_id' => $artist->id, 'arranger_id' => $artist->id]);

    foreach (range(1, 3) as $i) {
        performAtNewSchool([$song]);
    }

    $trends = app(PublicTrendsService::class);

    expect($trends->topComposers())->toBeEmpty()
        ->and($trends->topArrangers())->toBeEmpty();
})->with([
    'Traditional',
    'Trad. Scottish',
    'Anonymous',
    'Mexican folk song',
    'Spiritual',
    'Traditional English Carol',
    'Appalachian Folk Hymn',
]);

test('real artists with placeholder-like words in their names are still ranked', function () {
    $artist = Artist::factory()->create(['artist_name' => 'Carol Barnett']);
    $song = SongTitle::factory()->create(['composer_id' => $artist->id]);

    foreach (range(1, 3) as $i) {
        performAtNewSchool([$song]);
    }

    expect(app(PublicTrendsService::class)->topComposers()->pluck('name'))->toContain('Carol Barnett');
});

test('trends page never reveals schools, directors or event names', function () {
    $song = SongTitle::factory()->create(['song_title' => 'Shared Piece']);

    foreach (range(1, 3) as $i) {
        performAtNewSchool(
            [$song],
            ['school_name' => "Secret High School {$i}"],
            ['event_name' => "Private Gala {$i}", 'director_name' => "Director Person {$i}"],
        );
    }

    $this->get(route('trends'))
        ->assertSee('Shared Piece')
        ->assertDontSee('Secret High School')
        ->assertDontSee('Private Gala')
        ->assertDontSee('Director Person');
});

test('trends page shows an empty state when nothing meets the threshold', function () {
    $this->get(route('trends'))
        ->assertSuccessful()
        ->assertSee('Not enough data yet');
});

test('trends page invites guests to register but not signed-in users', function () {
    $this->get(route('trends'))->assertSee('Add your concerts to the trends');

    $this->actingAs(User::factory()->withoutTwoFactor()->create())
        ->get(route('trends'))
        ->assertDontSee('Add your concerts to the trends');
});

test('homepage links to the trends page', function () {
    $this->get('/')->assertSee('href="'.route('trends').'"', false);
});
