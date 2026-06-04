<?php

use App\Livewire\DigitalPrograms\PowerUserForm;
use App\Models\DigitalProgram;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to login', function () {
    $this->get(route('digital-programs.create.pro'))
        ->assertRedirect(route('login'));
});

test('authenticated user can access the power user form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('digital-programs.create.pro'))
        ->assertOk()
        ->assertSeeLivewire(PowerUserForm::class);
});

test('form starts with program not loaded', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(PowerUserForm::class)
        ->assertSet('programLoaded', false);
});

test('load program requires start choice', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(PowerUserForm::class)
        ->call('loadProgram')
        ->assertHasErrors('startChoice')
        ->assertSet('programLoaded', false);
});

test('load program with existing selection sets program loaded', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(PowerUserForm::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->call('loadProgram')
        ->assertSet('programLoaded', true)
        ->assertSet('resolvedProgramId', $program->id)
        ->assertHasNoErrors();
});

test('load program with existing selection initialises song settings', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $song = SongTitle::factory()->create(['song_title' => 'Gloria']);
    $program->songTitles()->attach($song->id, [
        'sort_order' => 1,
        'ensemble_sort_order' => 1,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(PowerUserForm::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->call('loadProgram');

    $songSettings = $component->get('songSettings');
    expect($songSettings)->toHaveCount(1);
    expect($songSettings[0]['title'])->toBe('Gloria');
    expect($songSettings[0]['showLyrics'])->toBeFalse();
});

test('load program with new path sets program loaded without resolved id', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(PowerUserForm::class)
        ->set('startChoice', 'new')
        ->set('newEventName', 'Winter Concert')
        ->set('newEventDate', '2026-12-10')
        ->set('newDirectorName', 'John Doe')
        ->set('newSchoolName', 'Riverside High')
        ->call('loadProgram')
        ->assertSet('programLoaded', true)
        ->assertSet('resolvedProgramId', null)
        ->assertHasNoErrors();
});

test('save creates a draft digital program for existing program', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(PowerUserForm::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->set('programLoaded', true)
        ->set('resolvedProgramId', $program->id)
        ->set('theme', 'Formal')
        ->set('printOrientation', 'Portrait')
        ->call('save', false)
        ->assertRedirect(route('digital-programs.index'));

    $dp = DigitalProgram::where('program_id', $program->id)->where('user_id', $user->id)->first();
    expect($dp)->not->toBeNull();
    expect($dp->is_published)->toBeFalse();
    expect($dp->theme)->toBe('Formal');
});

test('save with publish true creates a published digital program', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(PowerUserForm::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->set('programLoaded', true)
        ->set('resolvedProgramId', $program->id)
        ->set('theme', 'Holiday')
        ->set('printOrientation', 'Landscape')
        ->call('save', true)
        ->assertRedirect(route('digital-programs.index'));

    $dp = DigitalProgram::where('program_id', $program->id)->first();
    expect($dp->is_published)->toBeTrue();
    expect($dp->print_orientation)->toBe('Landscape');
});

test('save creates a new program when start choice is new', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(PowerUserForm::class)
        ->set('startChoice', 'new')
        ->set('newEventName', 'Graduation Concert')
        ->set('newEventDate', '2026-06-01')
        ->set('newDirectorName', 'Jane Smith')
        ->set('newSchoolName', 'Roosevelt High')
        ->set('programLoaded', true)
        ->set('theme', 'Graduation')
        ->set('printOrientation', 'Portrait')
        ->call('save', false)
        ->assertRedirect(route('digital-programs.index'));

    expect(Program::where('event_name', 'Graduation Concert')->where('user_id', $user->id)->exists())->toBeTrue();
    expect(DigitalProgram::where('user_id', $user->id)->where('theme', 'Graduation')->exists())->toBeTrue();
});

test('save stores welcome message and content fields', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(PowerUserForm::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->set('programLoaded', true)
        ->set('resolvedProgramId', $program->id)
        ->set('theme', 'Formal')
        ->set('printOrientation', 'Portrait')
        ->set('welcomeMessage', 'Welcome to our concert!')
        ->set('acknowledgments', 'Thank you all.')
        ->set('sponsorText', 'Sponsored by XYZ.')
        ->call('save', false);

    $dp = DigitalProgram::where('user_id', $user->id)->first();
    expect($dp->welcome_message)->toBe('Welcome to our concert!');
    expect($dp->acknowledgments)->toBe('Thank you all.');
    expect($dp->sponsor_text)->toBe('Sponsored by XYZ.');
});

test('save requires lyrics acknowledgment when lyrics are enabled', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $song = SongTitle::factory()->create();

    $this->actingAs($user);

    Livewire::test(PowerUserForm::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->set('programLoaded', true)
        ->set('resolvedProgramId', $program->id)
        ->set('theme', 'Formal')
        ->set('printOrientation', 'Portrait')
        ->set('songSettings', [[
            'songTitleId' => $song->id,
            'title' => 'Ave Maria',
            'composer' => '',
            'hasLyrics' => true,
            'showLyrics' => true,
        ]])
        ->set('lyricsCopyrightAcknowledged', false)
        ->call('save', false)
        ->assertHasErrors('lyricsCopyrightAcknowledged');

    expect(DigitalProgram::where('user_id', $user->id)->exists())->toBeFalse();
});

test('save requires student names acknowledgment when students are added', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(PowerUserForm::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->set('programLoaded', true)
        ->set('resolvedProgramId', $program->id)
        ->set('theme', 'Formal')
        ->set('printOrientation', 'Portrait')
        ->set('rosters', ['general' => [
            ['student_name' => 'Jane Smith', 'voice_part' => 'Alto', 'honorIndexes' => []],
        ]])
        ->set('honors', ['general' => []])
        ->set('studentNamesAcknowledged', false)
        ->call('save', false)
        ->assertHasErrors('studentNamesAcknowledged');

    expect(DigitalProgram::where('user_id', $user->id)->exists())->toBeFalse();
});
