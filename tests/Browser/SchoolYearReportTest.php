<?php

declare(strict_types=1);

use App\Models\Artist;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;

/**
 * Seeds a publishable 2025–26 school year: programs spread across the concert calendar,
 * a few widely sung pieces, and schools in several states.
 */
function seedReportSchoolYear(): void
{
    config(['trends.report_minimum_programs' => 5, 'trends.report_minimum_schools' => 5]);

    $hagenberg = Artist::factory()->create(['artist_name' => 'Elaine Hagenberg', 'artist_first_name' => 'Elaine', 'artist_last_name' => 'Hagenberg']);
    $gjeilo = Artist::factory()->create(['artist_name' => 'Ola Gjeilo', 'artist_first_name' => 'Ola', 'artist_last_name' => 'Gjeilo']);
    $byNight = SongTitle::query()->create(['song_title' => 'By Night', 'composer_id' => $hagenberg->id, 'arranger_id' => null]);
    $tundra = SongTitle::query()->create(['song_title' => 'Tundra', 'composer_id' => $gjeilo->id, 'arranger_id' => null]);

    $dates = ['2025-10-20', '2025-12-05', '2025-12-12', '2025-12-15', '2026-03-10', '2026-05-08', '2026-05-15', '2026-05-22'];
    $states = ['NJ', 'NJ', 'NJ', 'NY', 'PA', 'NJ', 'RI', 'NJ'];

    foreach ($dates as $index => $date) {
        $school = School::factory()->create(['geo_state' => $states[$index], 'country' => 'US']);
        $program = Program::factory()->for(User::factory())->for($school)->create(['event_date' => $date]);
        $program->songTitles()->attach($index % 2 === 0 ? $byNight->id : $tundra->id, ['sort_order' => 1, 'ensemble_sort_order' => 1]);
        $program->songTitles()->attach(SongTitle::query()->create(['song_title' => "Concert Piece {$index}"])->id, ['sort_order' => 2, 'ensemble_sort_order' => 1]);
    }
}

it('renders the school-year report without errors on desktop', function () {
    seedReportSchoolYear();

    $page = visit(route('reports.show', '2025-26'));

    $page->assertSee('2025–26 Choral Programming Report')
        ->assertSee('By Night')
        ->assertSee('Concert Calendar')
        ->assertSee('New Jersey')
        ->assertNoSmoke();

    $page->screenshot(filename: 'report-2025-26-desktop', fullPage: true);
});

it('renders the school-year report without errors on mobile in dark mode', function () {
    seedReportSchoolYear();

    $page = visit(route('reports.show', '2025-26'))
        ->on()->iPhone14Pro()
        ->inDarkMode();

    $page->assertSee('2025–26 Choral Programming Report')
        ->assertNoSmoke();

    $page->screenshot(filename: 'report-2025-26-mobile-dark', fullPage: true);
});
