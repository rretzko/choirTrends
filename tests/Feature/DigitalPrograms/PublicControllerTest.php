<?php

use App\Models\DigitalProgram;
use App\Models\DigitalProgramHonor;
use App\Models\DigitalProgramRoster;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;

test('published program is accessible without authentication', function () {
    $dp = DigitalProgram::factory()->published()->create();

    $this->get(route('program.public', $dp->slug))
        ->assertOk();
});

test('guest can access a published program', function () {
    $dp = DigitalProgram::factory()->published()->create();

    $this->get(route('program.public', $dp->slug))
        ->assertOk();
});

test('authenticated user can access a published program', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->published()->create();

    $this->actingAs($user)
        ->get(route('program.public', $dp->slug))
        ->assertOk();
});

test('unpublished program returns 404', function () {
    $dp = DigitalProgram::factory()->create(['is_published' => false]);

    $this->get(route('program.public', $dp->slug))
        ->assertNotFound();
});

test('non-existent slug returns 404', function () {
    $this->get(route('program.public', 'nosuchxx'))
        ->assertNotFound();
});

test('published program displays event name', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create([
        'event_name' => 'Spring Choral Concert',
        'director_name' => 'Jane Smith',
    ]);

    $dp = DigitalProgram::factory()->for($user)->published()->create([
        'program_id' => $program->id,
    ]);

    $this->get(route('program.public', $dp->slug))
        ->assertOk()
        ->assertSee('Spring Choral Concert')
        ->assertSee('Jane Smith');
});

test('published program displays school name', function () {
    $user = User::factory()->create();
    $school = School::factory()->create(['school_name' => 'Lincoln High School']);
    $program = Program::factory()->for($user)->for($school)->create();

    $dp = DigitalProgram::factory()->for($user)->published()->create([
        'program_id' => $program->id,
    ]);

    $this->get(route('program.public', $dp->slug))
        ->assertOk()
        ->assertSee('Lincoln High School');
});

test('published program displays welcome message', function () {
    $dp = DigitalProgram::factory()->published()->create([
        'welcome_message' => 'Welcome to our spring concert!',
    ]);

    $this->get(route('program.public', $dp->slug))
        ->assertOk()
        ->assertSee('Welcome to our spring concert!');
});

test('published program displays acknowledgments', function () {
    $dp = DigitalProgram::factory()->published()->create([
        'acknowledgments' => 'Thank you to all our supporters.',
    ]);

    $this->get(route('program.public', $dp->slug))
        ->assertOk()
        ->assertSee('Thank you to all our supporters.');
});

test('published program shows songs from linked program', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $song = SongTitle::factory()->create(['song_title' => 'Lux Aurumque']);
    $program->songTitles()->attach($song->id, [
        'sort_order' => 1,
        'ensemble_sort_order' => 1,
    ]);

    $dp = DigitalProgram::factory()->for($user)->published()->create([
        'program_id' => $program->id,
    ]);

    $this->get(route('program.public', $dp->slug))
        ->assertOk()
        ->assertSee('Lux Aurumque');
});

test('roster students appear with superscript honors', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->published()->create();

    $honor = DigitalProgramHonor::create([
        'digital_program_id' => $dp->id,
        'ensemble_id' => null,
        'label' => 'Section Leader',
        'sort_order' => 1,
    ]);

    $roster = DigitalProgramRoster::create([
        'digital_program_id' => $dp->id,
        'ensemble_id' => null,
        'student_name' => 'Mary Jones',
        'sort_order' => 1,
    ]);

    $roster->honors()->attach($honor->id);

    $this->get(route('program.public', $dp->slug))
        ->assertOk()
        ->assertSee('Mary Jones')
        ->assertSee('Section Leader');
});

test('public page contains qr code svg', function () {
    $dp = DigitalProgram::factory()->published()->create();

    $this->get(route('program.public', $dp->slug))
        ->assertOk()
        ->assertSee('<svg', false);
});

test('public page contains the program slug url', function () {
    $dp = DigitalProgram::factory()->published()->create();

    $this->get(route('program.public', $dp->slug))
        ->assertOk()
        ->assertSee('/p/'.$dp->slug);
});
