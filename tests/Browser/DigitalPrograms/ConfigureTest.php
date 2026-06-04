<?php

use App\Models\DigitalProgram;
use App\Models\Program;
use App\Models\School;
use App\Models\User;

it('allows an owner to change the theme and save via the configure page', function () {
    $user = User::factory()->create();
    $school = School::factory()->create(['school_name' => 'East Valley High']);
    $program = Program::factory()->for($user)->for($school)->create([
        'event_name' => 'Graduation Concert',
    ]);

    $dp = DigitalProgram::factory()->for($user)->create([
        'program_id' => $program->id,
        'theme' => 'Formal',
    ]);

    $this->actingAs($user);

    $page = visit(route('digital-programs.configure', $dp));

    $page->assertSee('Graduation Concert')
        ->assertSee('Save Draft')
        ->assertSee('Style')
        ->assertNoJavascriptErrors();

    // Change theme to Graduation
    $page->click('Graduation')
        ->assertNoJavascriptErrors();

    // Save as draft
    $page->click('Save Draft')
        ->assertPathIs('/digital-programs')
        ->assertNoJavascriptErrors();

    expect($dp->fresh()->theme)->toBe('Graduation');
});

it('blocks a non-owner from accessing the configure page', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $dp = DigitalProgram::factory()->for($owner)->create();

    $this->actingAs($other);

    $page = visit(route('digital-programs.configure', $dp));

    $page->assertSee('403');
});

it('shows the edit button on the digital programs index', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->create();

    $this->actingAs($user);

    $page = visit(route('digital-programs.index'));

    $page->assertSee('Edit')
        ->assertNoJavascriptErrors();
});
