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
use App\Services\PublicProfileService;
use Illuminate\Support\Carbon;

/**
 * Performs each song at the given number of new schools.
 *
 * @param  array<int, SongTitle>  $songs
 */
function performAtSchools(array $songs, int $schoolCount, array $programAttributes = [], array $pivotAttributes = []): void
{
    foreach (range(1, $schoolCount) as $i) {
        performAtNewSchool($songs, [], $programAttributes, $pivotAttributes);
    }
}

function composerUrl(Artist $artist): string
{
    return route('composers.show', ['id' => $artist->id, 'slug' => $artist->publicSlug()]);
}

function songUrl(SongTitle $song): string
{
    return route('songs.show', ['id' => $song->id, 'slug' => $song->publicSlug()]);
}

// ── Composer pages ───────────────────────────────────────────────────────────

test('a public composer page shows stats, works and seo tags', function () {
    $composer = Artist::factory()->create(['artist_name' => 'Ola Gjeilo']);
    $song = SongTitle::factory()->create(['song_title' => 'Northern Lights', 'composer_id' => $composer->id]);
    performAtSchools([$song], 3);

    $this->get(composerUrl($composer))
        ->assertSuccessful()
        ->assertSee('<title>Ola Gjeilo — Choral Music Performance Trends | '.config('app.name').'</title>', false)
        ->assertSee('<link rel="canonical" href="'.composerUrl($composer).'">', false)
        ->assertSee('Ola Gjeilo&#039;s choral music has been performed at 3 schools in 3 concert performances. Most performed: Northern Lights.', false)
        ->assertSee('Northern Lights')
        ->assertSee('href="'.songUrl($song).'"', false)
        ->assertSee('"@type":"Person"', false)
        ->assertSee('"@type":"BreadcrumbList"', false);
});

test('composer url uses an ascii slug', function () {
    $composer = Artist::factory()->create(['artist_name' => 'Pärt Uusberg']);

    expect(composerUrl($composer))->toEndWith("/composers/{$composer->id}-part-uusberg");
});

test('a composer page with the wrong slug permanently redirects to the canonical url', function () {
    $composer = Artist::factory()->create(['artist_name' => 'Elaine Hagenberg']);
    performAtSchools([SongTitle::factory()->create(['composer_id' => $composer->id])], 3);

    $this->get(route('composers.show', ['id' => $composer->id, 'slug' => 'old-name']))
        ->assertRedirect(composerUrl($composer))
        ->assertStatus(301);
});

test('a composer below the school threshold has no public page', function () {
    $composer = Artist::factory()->create(['artist_name' => 'Little Known']);
    performAtSchools([SongTitle::factory()->create(['composer_id' => $composer->id])], 2);

    $this->get(composerUrl($composer))->assertNotFound();
});

test('placeholder artists have no public page', function () {
    $artist = Artist::factory()->create(['artist_name' => 'Traditional']);
    performAtSchools([SongTitle::factory()->create(['composer_id' => $artist->id])], 3);

    $this->get(composerUrl($artist))->assertNotFound();
});

test('an unknown composer id is not found', function () {
    $this->get(route('composers.show', ['id' => 999999, 'slug' => 'nobody']))->assertNotFound();
});

test('composer pages only list pieces that meet the threshold on their own', function () {
    $composer = Artist::factory()->create();
    $popular = SongTitle::factory()->create(['song_title' => 'Widely Sung', 'composer_id' => $composer->id]);
    $rare = SongTitle::factory()->create(['song_title' => 'Sung Once', 'composer_id' => $composer->id]);

    performAtSchools([$popular], 3);
    performAtNewSchool([$rare]);

    $this->get(composerUrl($composer))
        ->assertSee('Widely Sung')
        ->assertDontSee('Sung Once')
        ->assertSee('1 more piece by');
});

test('an arranger page lists their arrangements with the original composer', function () {
    $arranger = Artist::factory()->create(['artist_name' => 'Kirby Shaw']);
    $composer = Artist::factory()->create(['artist_name' => 'Paul Simon']);
    $song = SongTitle::factory()->create([
        'song_title' => 'Bridge Over Troubled Water',
        'composer_id' => $composer->id,
        'arranger_id' => $arranger->id,
    ]);
    performAtSchools([$song], 3);

    $profile = app(PublicProfileService::class)->artistProfile($arranger);

    expect($profile['isArranger'])->toBeTrue()
        ->and($profile['isComposer'])->toBeFalse()
        ->and($profile['arrangedPieces']->pluck('title')->all())->toBe(['Bridge Over Troubled Water']);

    $this->get(composerUrl($arranger))
        ->assertSee('Choral arranger')
        ->assertSee('Most-Performed Arrangements')
        ->assertSee('Paul Simon');
});

test('composer stats combine composer and arranger roles', function () {
    $artist = Artist::factory()->create();
    performAtSchools([SongTitle::factory()->create(['composer_id' => $artist->id])], 2);
    performAtSchools([SongTitle::factory()->create(['arranger_id' => $artist->id])], 1);

    $profile = app(PublicProfileService::class)->artistProfile($artist);

    expect($profile['schoolCount'])->toBe(3)
        ->and($profile['pieceCount'])->toBe(2)
        ->and($profile['isComposer'])->toBeTrue()
        ->and($profile['isArranger'])->toBeTrue();
});

test('composer pages group performances by september to july school year', function () {
    $composer = Artist::factory()->create();
    $song = SongTitle::factory()->create(['composer_id' => $composer->id]);

    performAtNewSchool([$song], [], ['event_date' => '2025-09-01']);
    performAtNewSchool([$song], [], ['event_date' => '2026-07-31']);
    performAtNewSchool([$song], [], ['event_date' => '2025-08-31']);

    expect(app(PublicProfileService::class)->artistProfile($composer)['performancesBySchoolYear'])
        ->toBe(['2025–26' => 2, '2024–25' => 1]);
});

test('school year label boundaries', function (string $date, string $label) {
    expect(PublicProfileService::schoolYearLabel(Carbon::parse($date)))->toBe($label);
})->with([
    ['2025-08-31', '2024–25'],
    ['2025-09-01', '2025–26'],
    ['2026-01-15', '2025–26'],
    ['2099-12-01', '2099–00'],
]);

test('related composers are public composers sharing programs', function () {
    $main = Artist::factory()->create(['artist_name' => 'Main Composer']);
    $partner = Artist::factory()->create(['artist_name' => 'Partner Composer']);
    $obscure = Artist::factory()->create(['artist_name' => 'Obscure Composer']);
    $mainSong = SongTitle::factory()->create(['composer_id' => $main->id]);
    $partnerSong = SongTitle::factory()->create(['composer_id' => $partner->id]);
    $obscureSong = SongTitle::factory()->create(['composer_id' => $obscure->id]);

    performAtSchools([$mainSong, $partnerSong], 3);
    performAtNewSchool([$mainSong, $obscureSong]);

    $related = app(PublicProfileService::class)->artistProfile($main)['relatedArtists'];

    expect($related->pluck('name')->all())->toBe(['Partner Composer']);
});

test('composer page never reveals schools, directors or event names', function () {
    $composer = Artist::factory()->create();
    $song = SongTitle::factory()->create(['composer_id' => $composer->id]);

    foreach (range(1, 3) as $i) {
        performAtNewSchool([$song], ['school_name' => "Hidden School {$i}"], ['event_name' => "Private Gala {$i}", 'director_name' => "Director Person {$i}"]);
    }

    $this->get(composerUrl($composer))
        ->assertSuccessful()
        ->assertDontSee('Hidden School')
        ->assertDontSee('Private Gala')
        ->assertDontSee('Director Person');
});

// ── Composer index ───────────────────────────────────────────────────────────

test('composer index lists public artists alphabetically with links', function () {
    $zed = Artist::factory()->create(['artist_name' => 'Zed Zebra']);
    $amy = Artist::factory()->create(['artist_name' => 'Amy Apple']);
    $hidden = Artist::factory()->create(['artist_name' => 'Hidden Harmonies']);
    performAtSchools([
        SongTitle::factory()->create(['composer_id' => $zed->id]),
        SongTitle::factory()->create(['composer_id' => $amy->id]),
    ], 3);
    performAtNewSchool([SongTitle::factory()->create(['composer_id' => $hidden->id])]);

    $this->get(route('composers.index'))
        ->assertSuccessful()
        ->assertSee('<title>Choral Composers &amp; Arrangers | '.config('app.name').'</title>', false)
        ->assertSeeInOrder(['Amy Apple', 'Zed Zebra'])
        ->assertSee('href="'.composerUrl($amy).'"', false)
        ->assertDontSee('Hidden Harmonies');
});

// ── Song pages ───────────────────────────────────────────────────────────────

test('a public song page shows stats, voicing, difficulty, tags and ai summary', function () {
    $composer = Artist::factory()->create(['artist_name' => 'Ola Gjeilo']);
    $song = SongTitle::factory()->create(['song_title' => 'Tundra', 'composer_id' => $composer->id]);
    $ensemble = Ensemble::factory()->create(['type' => EnsembleType::Satb]);
    performAtSchools([$song], 3, [], ['ensemble_id' => $ensemble->id]);

    SongTitleDescription::factory()->create(['song_title_id' => $song->id, 'description' => 'An atmospheric Nordic landscape.']);
    SongTitleTag::factory()->create(['song_title_id' => $song->id, 'tag' => 'atmospheric']);
    SongTitleDifficultyObservation::factory()->create(['song_title_id' => $song->id, 'difficulty_label' => 'moderate', 'difficulty_value' => 2]);

    $this->get(songUrl($song))
        ->assertSuccessful()
        ->assertSee('<title>Tundra by Ola Gjeilo — Choral Performance Trends | '.config('app.name').'</title>', false)
        ->assertSee('<meta name="description" content="Tundra (by Ola Gjeilo) has been performed at 3 schools in 3 concert performances. Voicing: SATB. Difficulty: Moderate.">', false)
        ->assertSee('An atmospheric Nordic landscape.')
        ->assertSee('Summary generated with AI.')
        ->assertSee('atmospheric')
        ->assertSee('href="'.composerUrl($composer).'"', false)
        ->assertSee('"@type":"MusicComposition"', false);
});

test('song pages do not show publisher copy or director-written content', function () {
    $song = SongTitle::factory()->create();
    performAtSchools([$song], 3);

    SongTitleDescription::factory()->create(['song_title_id' => $song->id, 'description' => 'Publisher marketing copy.', 'authorship_type' => AuthorshipType::Publisher->value]);
    SongTitleDescription::factory()->create(['song_title_id' => $song->id, 'description' => 'My private rehearsal notes.', 'authorship_type' => AuthorshipType::User->value, 'authorship_id' => User::factory()]);
    SongTitleTag::factory()->create(['song_title_id' => $song->id, 'tag' => 'my-private-tag', 'authorship_type' => AuthorshipType::User->value, 'authorship_id' => User::factory()]);

    $this->get(songUrl($song))
        ->assertSuccessful()
        ->assertDontSee('Publisher marketing copy.')
        ->assertDontSee('My private rehearsal notes.')
        ->assertDontSee('my-private-tag')
        ->assertDontSee('Summary generated with AI.');
});

test('unknown voicings are not shown', function () {
    $song = SongTitle::factory()->create();
    performAtSchools([$song], 3, [], ['ensemble_id' => Ensemble::factory()->create(['type' => EnsembleType::Unknown])->id]);

    expect(app(PublicProfileService::class)->songProfile($song)['voicings'])->toBe([]);
});

test('a song below the school threshold has no public page', function () {
    $song = SongTitle::factory()->create();
    performAtSchools([$song], 2);

    $this->get(songUrl($song))->assertNotFound();
});

test('a song page with the wrong slug permanently redirects to the canonical url', function () {
    $song = SongTitle::factory()->create(['song_title' => 'Does The World Say?']);
    performAtSchools([$song], 3);

    expect(songUrl($song))->toEndWith("/songs/{$song->id}-does-the-world-say");

    $this->get(route('songs.show', ['id' => $song->id, 'slug' => 'stale']))
        ->assertRedirect(songUrl($song))
        ->assertStatus(301);
});

test('a song with a placeholder composer shows the name without a link', function () {
    $traditional = Artist::factory()->create(['artist_name' => 'Traditional']);
    $song = SongTitle::factory()->create(['composer_id' => $traditional->id]);
    performAtSchools([$song], 3);

    $this->get(songUrl($song))
        ->assertSuccessful()
        ->assertSee('Traditional')
        ->assertDontSee('href="'.composerUrl($traditional).'"', false);
});

test('often programmed with lists only public pieces from shared programs', function () {
    $song = SongTitle::factory()->create();
    $partner = SongTitle::factory()->create(['song_title' => 'Frequent Partner']);
    $rare = SongTitle::factory()->create(['song_title' => 'Rare Partner']);

    performAtSchools([$song, $partner], 3);
    performAtNewSchool([$song, $rare]);

    $this->get(songUrl($song))
        ->assertSee('Frequent Partner')
        ->assertDontSee('Rare Partner');
});

// ── Linking and sitemap ──────────────────────────────────────────────────────

test('trends page links pieces and public composers to their pages', function () {
    $composer = Artist::factory()->create();
    $song = SongTitle::factory()->create(['composer_id' => $composer->id]);
    performAtSchools([$song], 3);

    $this->get(route('trends'))
        ->assertSee('href="'.songUrl($song).'"', false)
        ->assertSee('href="'.composerUrl($composer).'"', false)
        ->assertSee('href="'.route('composers.index').'"', false);
});

test('sitemap lists the composer index, public composers and public songs only', function () {
    $public = Artist::factory()->create();
    $private = Artist::factory()->create();
    $publicSong = SongTitle::factory()->create(['composer_id' => $public->id]);
    $privateSong = SongTitle::factory()->create(['composer_id' => $private->id]);
    performAtSchools([$publicSong], 3);
    performAtNewSchool([$privateSong]);

    $this->get('/sitemap.xml')
        ->assertSee('<loc>'.route('composers.index').'</loc>', false)
        ->assertSee('<loc>'.composerUrl($public).'</loc>', false)
        ->assertSee('<loc>'.songUrl($publicSong).'</loc>', false)
        ->assertDontSee(composerUrl($private), false)
        ->assertDontSee(songUrl($privateSong), false);
});

test('public composer and song pages are indexable', function () {
    $composer = Artist::factory()->create();
    $song = SongTitle::factory()->create(['composer_id' => $composer->id]);
    performAtSchools([$song], 3);

    foreach ([composerUrl($composer), songUrl($song), route('composers.index')] as $url) {
        expect($this->get($url)->headers->get('X-Robots-Tag'))->toBeNull();
    }
});
