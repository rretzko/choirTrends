<?php

use App\Models\DigitalProgram;
use App\Models\Program;
use App\Models\School;
use App\Models\User;

it('completes the guided wizard happy path and publishes a digital program', function () {
    $user = User::factory()->create([
        'email' => 'wizard@example.com',
        'password' => bcrypt('password'),
    ]);
    $school = School::factory()->create(['school_name' => 'Lincoln High School']);
    $program = Program::factory()->for($user)->for($school)->create([
        'event_name' => 'Spring Choral Concert',
        'director_name' => 'Jane Smith',
    ]);

    $this->actingAs($user);

    $page = visit(route('digital-programs.create.guided'));

    // Step 1 — verify wizard renders
    $page->assertSee('Choose a Program')
        ->assertSee('Use an Existing Program')
        ->assertNoJavascriptErrors();

    // Choose existing program path and select the program
    $page->click('Use an Existing Program')
        ->select('[wire\\:model="selectedProgramId"]', (string) $program->id)
        ->pressAndWaitFor('Next')
        ->assertSee('Choose a Style')
        ->assertNoJavascriptErrors();

    // Step 2 — pick Winter Concert theme
    $page->click('Winter Concert')
        ->pressAndWaitFor('Next')
        ->assertSee('Program Content')
        ->assertNoJavascriptErrors();

    // Step 3 — add a welcome message and advance
    $page->type('[wire\\:model="welcomeMessage"]', 'Welcome to our spring concert!')
        ->pressAndWaitFor('Next')
        ->assertSee('Song Settings')
        ->assertNoJavascriptErrors();

    // Step 4 — no lyrics, advance
    $page->pressAndWaitFor('Next')
        ->assertSee('Student Roster')
        ->assertNoJavascriptErrors();

    // Step 5 — no roster, advance
    $page->pressAndWaitFor('Next')
        ->assertSee('Ready to Publish')
        ->assertSee('Spring Choral Concert')
        ->assertNoJavascriptErrors();

    // Step 6 — publish using wire:click selector to avoid matching the step label span
    $page->click('[wire\\:click="publish"]')
        ->assertUrlIs(route('digital-programs.index'))
        ->assertSee('Published')
        ->assertNoJavascriptErrors();

    // Verify the record was published in the DB
    $dp = DigitalProgram::where('program_id', $program->id)
        ->where('user_id', $user->id)
        ->first();

    expect($dp)->not->toBeNull();
    expect($dp->is_published)->toBeTrue();
    expect($dp->welcome_message)->toBe('Welcome to our spring concert!');
});
