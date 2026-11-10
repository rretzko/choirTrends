<?php

declare(strict_types=1);

use App\Models\Artist;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use App\Services\SchoolYearReportService;

beforeEach(function () {
    config(['trends.report_minimum_programs' => 3, 'trends.report_minimum_schools' => 3]);
});

/**
 * One program at a new school on the given date, with the given songs.
 *
 * @param  array<int, SongTitle>  $songs
 */
function programOn(string $date, array $songs, string $state = 'NJ', string $country = 'US'): Program
{
    $school = School::factory()->create(['geo_state' => $state, 'country' => $country]);
    $program = Program::factory()->for(User::factory())->for($school)->create(['event_date' => $date]);

    foreach (array_values($songs) as $index => $song) {
        $program->songTitles()->attach($song->id, ['sort_order' => $index + 1, 'ensemble_sort_order' => 1]);
    }

    return $program;
}

function namedSong(string $title, ?Artist $composer = null): SongTitle
{
    return SongTitle::query()->create(['song_title' => $title, 'composer_id' => $composer?->id]);
}

// ── School-year parsing ──────────────────────────────────────────────────────

test('school year slugs parse only consecutive years', function (string $slug, ?int $startYear) {
    expect(SchoolYearReportService::startYearFromSlug($slug))->toBe($startYear);
})->with([
    ['2025-26', 2025],
    ['2099-00', 2099],
    ['2025-27', null],
    ['2025-2026', null],
    ['nonsense', null],
]);

test('region names come from state codes and countries', function (?string $state, ?string $country, string $name) {
    expect(SchoolYearReportService::regionName($state, $country))->toBe($name);
})->with([
    ['NJ', 'US', 'New Jersey'],
    ['nj', null, 'New Jersey'],
    ['RP', 'DE', 'Germany'],
    [null, null, ''],
]);

// ── The report page ──────────────────────────────────────────────────────────

test('the report counts only programs inside the september to july school year', function () {
    $song = namedSong('Inside');
    programOn('2025-09-01', [$song]);
    programOn('2026-07-31', [$song]);
    programOn('2026-01-10', [$song]);
    programOn('2025-08-31', [namedSong('Before')]);
    programOn('2026-08-01', [namedSong('After')]);

    $report = app(SchoolYearReportService::class)->report(2025);

    expect($report['stats'])->toBe(['programs' => 3, 'schools' => 3, 'directors' => 3, 'titles' => 1, 'performances' => 3])
        ->and(collect($report['months'])->pluck('programs', 'label')->only(['Sep', 'Jan', 'Jul'])->all())->toBe(['Sep' => 1, 'Jan' => 1, 'Jul' => 1])
        ->and($report['months'])->toHaveCount(11);
});

test('only pieces and composers reaching the school threshold within the year are named', function () {
    $hagenberg = Artist::factory()->create(['artist_name' => 'Elaine Hagenberg']);
    $quiet = Artist::factory()->create(['artist_name' => 'Quiet Composer']);
    $popular = namedSong('By Night', $hagenberg);
    $rare = namedSong('Rare Gem', $quiet);

    foreach (['2025-12-01', '2026-03-01', '2026-05-01'] as $date) {
        programOn($date, [$popular]);
    }
    programOn('2026-05-02', [$rare]);
    programOn('2024-12-01', [$rare]);
    programOn('2023-12-01', [$rare]);

    $this->get(route('reports.show', '2025-26'))
        ->assertSuccessful()
        ->assertSee('By Night')
        ->assertSee('Elaine Hagenberg')
        ->assertDontSee('Rare Gem')
        ->assertDontSee('Quiet Composer');
});

test('regions under the threshold are named without counts and schools without a location are left out', function () {
    $song = namedSong('Shared');
    foreach (range(1, 3) as $i) {
        programOn('2025-12-0'.$i, [$song], 'NJ');
    }
    programOn('2026-05-01', [$song], 'RI');
    programOn('2026-05-02', [$song], 'RP', 'DE');
    programOn('2026-05-03', [$song], '', '');

    $regions = app(SchoolYearReportService::class)->report(2025)['regions'];

    expect($regions)->toBe([
        ['name' => 'New Jersey', 'schools' => 3],
        ['name' => 'Germany', 'schools' => null],
        ['name' => 'Rhode Island', 'schools' => null],
    ]);

    $this->get(route('reports.show', '2025-26'))
        ->assertSee('3 schools')
        ->assertSee('Also: Germany and Rhode Island.');
});

test('the report page has seo tags, highlights and structured data', function () {
    $composer = Artist::factory()->create(['artist_name' => 'Jennifer Lucy Cook']);
    $time = namedSong('Time', $composer);
    foreach (['2026-05-01', '2026-05-08', '2026-05-15', '2025-12-10'] as $date) {
        programOn($date, [$time]);
    }

    $this->get(route('reports.show', '2025-26'))
        ->assertSuccessful()
        ->assertSee('<title>2025–26 Choral Programming Report | '.config('app.name').'</title>', false)
        ->assertSee('<link rel="canonical" href="'.route('reports.show', '2025-26').'">', false)
        ->assertSee('What school choirs sang in 2025–26: 4 concert programs from 4 schools, led by music from Jennifer Lucy Cook.', false)
        ->assertSee('The most widely programmed piece was “Time” by Jennifer Lucy Cook, sung by 4 different schools.', false)
        ->assertSee('May was the busiest concert month, with 3 of the year’s 4 programs.', false)
        ->assertSee('"@type":"Dataset"', false)
        ->assertSee('"temporalCoverage":"2025-09/2026-07"', false)
        ->assertSee('View as table');
});

test('the report never names schools, directors or events', function () {
    $song = namedSong('Shared');
    foreach (range(1, 3) as $i) {
        $program = programOn("2026-05-0{$i}", [$song]);
        $program->school->update(['school_name' => "Hidden High {$i}"]);
        $program->update(['event_name' => "Private Gala {$i}", 'director_name' => "Director Person {$i}"]);
    }

    $this->get(route('reports.show', '2025-26'))
        ->assertSuccessful()
        ->assertDontSee('Hidden High')
        ->assertDontSee('Private Gala')
        ->assertDontSee('Director Person');
});

test('a school year below the publishing threshold has no report', function () {
    programOn('2025-12-01', [namedSong('Lonely')]);

    $this->get(route('reports.show', '2025-26'))->assertNotFound();
    $this->get(route('reports.index'))->assertSee('No reports yet');
});

test('invalid school year slugs are not found', function () {
    $this->get('/reports/2025-27')->assertNotFound();
    $this->get('/reports/2025')->assertNotFound();
});

test('the reports index links to published years, and they are in the sitemap', function () {
    $song = namedSong('Shared');
    foreach (range(1, 3) as $i) {
        programOn("2026-05-0{$i}", [$song]);
    }

    $this->get(route('reports.index'))
        ->assertSuccessful()
        ->assertSee('href="'.route('reports.show', '2025-26').'"', false)
        ->assertSee('2025–26 Choral Programming Report');

    $this->get('/sitemap.xml')->assertSee('<loc>'.route('reports.show', '2025-26').'</loc>', false);
});

test('public headers and the footer link to the reports', function () {
    $this->get('/')->assertSee('href="'.route('reports.index').'"', false);
    $this->get(route('trends'))->assertSee('href="'.route('reports.index').'"', false);
});
