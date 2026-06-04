<?php

use App\Models\DigitalProgram;
use App\Models\DigitalProgramHonor;
use App\Models\DigitalProgramRoster;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;

it('displays a published program to a guest on a mobile viewport', function () {
    $user = User::factory()->create();
    $school = School::factory()->create(['school_name' => 'Riverside Academy']);
    $program = Program::factory()->for($user)->for($school)->create([
        'event_name' => 'Winter Showcase',
        'director_name' => 'Mr. Carter',
    ]);
    $song = SongTitle::factory()->create(['song_title' => 'Lux Aurumque']);
    $program->songTitles()->attach($song->id, ['sort_order' => 1, 'ensemble_sort_order' => 1]);

    $dp = DigitalProgram::factory()->for($user)->published()->create([
        'program_id' => $program->id,
        'theme' => 'WinterConcert',
        'welcome_message' => 'Thank you for joining us tonight.',
    ]);

    // A guest visiting on a phone (primary use case — dim performance environment)
    $page = visit(route('program.public', $dp->slug))
        ->on()->iPhone14Pro()
        ->inDarkMode();

    $page->assertSee('Riverside Academy')
        ->assertSee('Winter Showcase')
        ->assertSee('Mr. Carter')
        ->assertSee('Thank you for joining us tonight.')
        ->assertSee('Lux Aurumque')
        ->assertSee('/p/'.$dp->slug)          // permanent URL in footer
        ->assertNoJavascriptErrors();

    // QR code SVG should be embedded
    $page->assertSourceHas('<svg');
});

it('displays a published program with roster and honor legend', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->published()->create([
        'theme' => 'Formal',
    ]);

    $honor = DigitalProgramHonor::create([
        'digital_program_id' => $dp->id,
        'ensemble_id' => null,
        'label' => 'Section Leader',
        'sort_order' => 1,
    ]);

    $roster = DigitalProgramRoster::create([
        'digital_program_id' => $dp->id,
        'ensemble_id' => null,
        'student_name' => 'Alice Johnson',
        'voice_part' => 'Soprano I',
        'sort_order' => 1,
    ]);
    $roster->honors()->attach($honor->id);

    $page = visit(route('program.public', $dp->slug));

    $page->assertSee('Alice Johnson')
        ->assertSee('Soprano I')
        ->assertSee('Section Leader')
        ->assertNoJavascriptErrors();
});

it('returns 404 for an unpublished program', function () {
    $dp = DigitalProgram::factory()->create(['is_published' => false]);

    $page = visit(route('program.public', $dp->slug));

    $page->assertSee('404');
});

it('shows the print program button', function () {
    $dp = DigitalProgram::factory()->published()->create();

    $page = visit(route('program.public', $dp->slug));

    $page->assertSee('Print Program')
        ->assertNoJavascriptErrors();
});
