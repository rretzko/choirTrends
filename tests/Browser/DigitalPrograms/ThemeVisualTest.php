<?php

use App\Models\DigitalProgram;
use App\Models\DigitalProgramHonor;
use App\Models\DigitalProgramRoster;
use App\Models\DigitalProgramSongSetting;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use App\Models\UserSongLyrics;

/**
 * Builds a fully-populated published DigitalProgram for visual testing.
 * Includes: event header, welcome message, songs with one set of lyrics,
 * acknowledgments, sponsors, honor legend, and a mixed-voice roster.
 */
function richPublishedProgram(string $theme): DigitalProgram
{
    $user = User::factory()->create();
    $school = School::factory()->create(['school_name' => 'Hillcrest High School']);

    $program = Program::factory()->for($user)->for($school)->create([
        'event_name' => 'Annual Spring Concert',
        'event_date' => '2026-05-15',
        'director_name' => 'Dr. Sarah Mitchell',
    ]);

    $song1 = SongTitle::factory()->create(['song_title' => 'Lux Aurumque']);
    $song2 = SongTitle::factory()->create(['song_title' => 'The Road Home']);
    $song3 = SongTitle::factory()->create(['song_title' => 'Beautiful Dreamer']);

    $program->songTitles()->attach($song1->id, ['sort_order' => 1, 'ensemble_sort_order' => 1]);
    $program->songTitles()->attach($song2->id, ['sort_order' => 2, 'ensemble_sort_order' => 1]);
    $program->songTitles()->attach($song3->id, ['sort_order' => 3, 'ensemble_sort_order' => 1]);

    UserSongLyrics::create([
        'user_id' => $user->id,
        'song_title_id' => $song1->id,
        'content' => "Lux, aurumque.\nO, nunc in pulvere dormimus.",
        'source' => 'manual',
    ]);

    $dp = DigitalProgram::factory()->for($user)->published()->create([
        'program_id' => $program->id,
        'theme' => $theme,
        'print_orientation' => 'Portrait',
        'welcome_message' => 'Welcome to our Annual Spring Concert! Thank you for joining us this evening. '
            .'The Hillcrest Choir has worked tirelessly to bring you a memorable performance.',
        'acknowledgments' => "Thank you to our wonderful parents, staff, and community supporters.\n\n"
            .'Special thanks to Principal Johnson and the Hillcrest administration.',
        'sponsor_text' => "Platinum: Hillcrest Arts Foundation · Community Bank\nSilver: Main Street Music",
        'lyrics_copyright_acknowledged' => true,
        'student_names_acknowledged' => true,
    ]);

    DigitalProgramSongSetting::create([
        'digital_program_id' => $dp->id,
        'song_title_id' => $song1->id,
        'show_lyrics' => true,
    ]);

    $leader = DigitalProgramHonor::create(['digital_program_id' => $dp->id, 'ensemble_id' => null, 'label' => 'Section Leader',  'sort_order' => 1]);
    $allState = DigitalProgramHonor::create(['digital_program_id' => $dp->id, 'ensemble_id' => null, 'label' => 'All-State Choir', 'sort_order' => 2]);
    $officer = DigitalProgramHonor::create(['digital_program_id' => $dp->id, 'ensemble_id' => null, 'label' => 'Officer',         'sort_order' => 3]);

    $students = [
        ['Emma Adams',    'Soprano I',  [$leader->id, $allState->id]],
        ['Sarah Brown',   'Soprano I',  []],
        ['Anna Chen',     'Soprano II', [$officer->id]],
        ['Maria Davis',   'Alto',       [$leader->id]],
        ['Olivia Evans',  'Alto',       []],
        ['James Ford',    'Tenor',      [$allState->id]],
        ['Michael Green', 'Bass',       [$leader->id]],
        ['David Harris',  'Bass',       []],
    ];

    foreach ($students as $i => [$name, $voice, $honorIds]) {
        $roster = DigitalProgramRoster::create([
            'digital_program_id' => $dp->id,
            'ensemble_id' => null,
            'student_name' => $name,
            'voice_part' => $voice,
            'sort_order' => $i + 1,
        ]);

        if (! empty($honorIds)) {
            $roster->honors()->attach($honorIds);
        }
    }

    return $dp;
}

// ─── Smoke test all 6 themes simultaneously ───────────────────────────────────
// Uses minimal factory programs to avoid SongTitle factory unique-value exhaustion.

it('all six themes pass smoke testing on a mobile viewport', function () {
    $urls = collect(['WinterConcert', 'SpringFestival', 'Graduation', 'Holiday', 'Formal', 'Minimalist'])
        ->map(fn (string $theme) => route('program.public',
            DigitalProgram::factory()->published()->create(['theme' => $theme])->slug
        ))
        ->all();

    $pages = visit($urls)->on()->iPhone14Pro()->inDarkMode();
    $pages->assertNoSmoke();
});

// ─── Per-theme visual capture: mobile dark (primary use case) ────────────────
// Screenshots saved to tests/Browser/Screenshots/ for manual visual review.
// assertScreenshotMatches() is intentionally omitted: the QR code embeds the
// program's random slug, making pixel-perfect baselines unstable across runs.
// Pixel regression can be added once the visual design is locked down.

it('renders theme without errors on mobile in dark mode', function (string $theme) {
    $dp = richPublishedProgram($theme);

    $page = visit(route('program.public', $dp->slug))
        ->on()->iPhone14Pro()
        ->inDarkMode();

    $page->assertSee('Hillcrest High School')
        ->assertSee('Annual Spring Concert')
        ->assertSee('Dr. Sarah Mitchell')
        ->assertSee('Lux Aurumque')
        ->assertSee('Emma Adams')
        ->assertSee('Section Leader')
        ->assertNoSmoke();

    $page->screenshot(filename: "visual-{$theme}-mobile-dark", fullPage: true);
})->with(['WinterConcert', 'SpringFestival', 'Graduation', 'Holiday', 'Formal', 'Minimalist']);

// ─── Per-theme visual capture: desktop (testing / pre-performance) ────────────

it('renders theme without errors on desktop', function (string $theme) {
    $dp = richPublishedProgram($theme);

    $page = visit(route('program.public', $dp->slug));

    $page->assertSee('Hillcrest High School')
        ->assertSee('Annual Spring Concert')
        ->assertSee('Thank you for joining us')
        ->assertSee('Lux Aurumque')
        ->assertNoSmoke();

    $page->screenshot(filename: "visual-{$theme}-desktop", fullPage: true);
})->with(['WinterConcert', 'SpringFestival', 'Graduation', 'Holiday', 'Formal', 'Minimalist']);

// ─── Specific content rendering across themes ─────────────────────────────────

it('displays lyrics, sponsors, and QR code on all themes', function (string $theme) {
    $dp = richPublishedProgram($theme);

    $page = visit(route('program.public', $dp->slug))
        ->on()->iPhone14Pro()
        ->inDarkMode();

    // Lyrics section
    $page->assertSee('Lux, aurumque.');

    // Acknowledgments and sponsors
    $page->assertSee('Thank you to our wonderful parents')
        ->assertSee('Hillcrest Arts Foundation');

    // Honor legend
    $page->assertSee('Section Leader')
        ->assertSee('All-State Choir')
        ->assertSee('Officer');

    // QR code and print button in footer
    $page->assertSourceHas('<svg')
        ->assertSee('Print Program')
        ->assertNoSmoke();
})->with(['WinterConcert', 'SpringFestival', 'Graduation', 'Holiday', 'Formal', 'Minimalist']);
