<?php

use App\Livewire\DigitalPrograms\PowerUserForm;
use App\Models\DigitalProgram;
use App\Models\DigitalProgramSongSetting;
use App\Models\Ensemble;
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

test('mount with digital program pre-loads program and marks as loaded', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create([
        'program_id' => $program->id,
        'theme' => 'Holiday',
        'welcome_message' => 'Welcome!',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(PowerUserForm::class, ['digitalProgram' => $dp]);

    $component->assertSet('programLoaded', true)
        ->assertSet('startChoice', 'existing')
        ->assertSet('selectedProgramId', $program->id)
        ->assertSet('theme', 'Holiday')
        ->assertSet('welcomeMessage', 'Welcome!');
});

test('mount with another users digital program returns 403', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($owner)->for($school)->create();
    $dp = DigitalProgram::factory()->for($owner)->create(['program_id' => $program->id]);

    $this->actingAs($other)
        ->get(route('digital-programs.create.pro', $dp))
        ->assertForbidden();
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

test('load program with existing selection initialises ensemble songs', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $ensemble = Ensemble::factory()->for($school)->create(['ensemble_name' => 'Concert Choir']);
    $song = SongTitle::factory()->create(['song_title' => 'Gloria']);
    $program->songTitles()->attach($song->id, [
        'sort_order' => 1,
        'ensemble_id' => $ensemble->id,
        'ensemble_sort_order' => 1,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(PowerUserForm::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->call('loadProgram');

    $wizardEnsembles = $component->get('wizardEnsembles');
    $ensembleSongs = $component->get('ensembleSongs');
    expect($wizardEnsembles)->toHaveCount(1);
    expect($wizardEnsembles[0]['name'])->toBe('Concert Choir');
    expect($ensembleSongs[0])->toHaveCount(1);
    expect($ensembleSongs[0][0]['title'])->toBe('Gloria');
    expect($ensembleSongs[0][0]['showLyrics'])->toBeFalse();
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

test('assistant can save a draft for the directors program', function () {
    $director = User::factory()->create();
    $assistant = User::factory()->assistant($director)->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($director)->for($school)->create();

    $this->actingAs($assistant);

    Livewire::test(PowerUserForm::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->set('programLoaded', true)
        ->set('resolvedProgramId', $program->id)
        ->set('theme', 'Formal')
        ->set('printOrientation', 'Portrait')
        ->call('save', false)
        ->assertRedirect(route('digital-programs.index'));

    $dp = DigitalProgram::where('program_id', $program->id)->where('user_id', $director->id)->first();
    expect($dp)->not->toBeNull();
    expect($dp->is_published)->toBeFalse();
});

test('assistant cannot publish via save', function () {
    $director = User::factory()->create();
    $assistant = User::factory()->assistant($director)->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($director)->for($school)->create();

    $this->actingAs($assistant);

    Livewire::test(PowerUserForm::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->set('programLoaded', true)
        ->set('resolvedProgramId', $program->id)
        ->set('theme', 'Formal')
        ->set('printOrientation', 'Portrait')
        ->call('save', true)
        ->assertForbidden();

    expect(DigitalProgram::where('program_id', $program->id)->exists())->toBeFalse();
});

test('assistant saving a draft does not unpublish an already published program', function () {
    $director = User::factory()->create();
    $assistant = User::factory()->assistant($director)->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($director)->for($school)->create();
    $dp = DigitalProgram::factory()->for($director)->published()->create([
        'program_id' => $program->id,
    ]);

    $this->actingAs($assistant);

    Livewire::test(PowerUserForm::class, ['digitalProgram' => $dp])
        ->call('save', false);

    expect($dp->fresh()->is_published)->toBeTrue();
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

test('load program pre-populates content from existing digital program', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create([
        'program_id' => $program->id,
        'theme' => 'Holiday',
        'print_orientation' => 'Landscape',
        'welcome_message' => 'Welcome everyone!',
        'acknowledgments' => 'Thanks to all.',
        'sponsor_text' => 'Proud sponsor.',
    ]);

    $this->actingAs($user);

    Livewire::test(PowerUserForm::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->call('loadProgram')
        ->assertSet('digitalProgramId', $dp->id)
        ->assertSet('theme', 'Holiday')
        ->assertSet('printOrientation', 'Landscape')
        ->assertSet('welcomeMessage', 'Welcome everyone!')
        ->assertSet('acknowledgments', 'Thanks to all.')
        ->assertSet('sponsorText', 'Proud sponsor.');
});

test('load program syncs show lyrics from existing digital program song settings', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $ensemble = Ensemble::factory()->for($school)->create(['ensemble_name' => 'Concert Choir']);
    $song = SongTitle::factory()->create(['song_title' => 'Hallelujah']);
    $program->songTitles()->attach($song->id, [
        'sort_order' => 1,
        'ensemble_id' => $ensemble->id,
        'ensemble_sort_order' => 1,
    ]);

    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);
    DigitalProgramSongSetting::create([
        'digital_program_id' => $dp->id,
        'song_title_id' => $song->id,
        'show_lyrics' => true,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(PowerUserForm::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->call('loadProgram');

    $ensembleSongs = $component->get('ensembleSongs');
    expect($ensembleSongs[0][0]['showLyrics'])->toBeTrue();
});

test('save updates existing digital program when one is found on load', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create([
        'program_id' => $program->id,
        'theme' => 'Formal',
        'welcome_message' => 'Old message',
    ]);

    $this->actingAs($user);

    Livewire::test(PowerUserForm::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->call('loadProgram')
        ->set('welcomeMessage', 'Updated message')
        ->set('theme', 'Holiday')
        ->call('save', false);

    expect(DigitalProgram::where('user_id', $user->id)->count())->toBe(1);
    $dp->refresh();
    expect($dp->welcome_message)->toBe('Updated message');
    expect($dp->theme)->toBe('Holiday');
});

test('save persists songs from wizardEnsembles', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $ensemble = Ensemble::factory()->for($school)->create(['ensemble_name' => 'Concert Choir']);

    $this->actingAs($user);

    Livewire::test(PowerUserForm::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->set('programLoaded', true)
        ->set('resolvedProgramId', $program->id)
        ->set('theme', 'Formal')
        ->set('printOrientation', 'Portrait')
        ->set('wizardEnsembles', [['id' => $ensemble->id, 'name' => 'Concert Choir', 'type' => 'Satb']])
        ->set('ensembleSongs', [[
            ['songTitleId' => null, 'title' => 'Ave Maria', 'composer' => 'Schubert', 'arranger' => '', 'showLyrics' => false, 'programNotes' => ''],
        ]])
        ->call('save', false);

    expect(SongTitle::where('song_title', 'Ave Maria')->exists())->toBeTrue();
    expect(DigitalProgram::where('user_id', $user->id)->exists())->toBeTrue();
});

test('processSongsCsv parses valid CSV and replaces ensembles and songs', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $csv = implode("\n", [
        'Ensemble,Song Title,Composer,Arranger,Show Lyrics,Program Notes',
        'Concert Choir,Ave Maria,Franz Schubert,,0,',
        'Concert Choir,Gloria,Vivaldi,,0,Soloist: Jane',
        'Chamber Choir,Lux Aeterna,,,0,',
    ]);

    $component = Livewire::test(PowerUserForm::class)
        ->call('processSongsCsv', $csv);

    $wizardEnsembles = $component->get('wizardEnsembles');
    $ensembleSongs = $component->get('ensembleSongs');
    $result = $component->get('songsCsvResult');

    expect($wizardEnsembles)->toHaveCount(2);
    expect($wizardEnsembles[0]['name'])->toBe('Concert Choir');
    expect($wizardEnsembles[1]['name'])->toBe('Chamber Choir');
    expect($ensembleSongs[0])->toHaveCount(2);
    expect($ensembleSongs[1])->toHaveCount(1);
    expect($ensembleSongs[0][1]['programNotes'])->toBe('Soloist: Jane');
    expect($result['type'])->toBe('success');
});

test('processSongsCsv replaces previously uploaded songs', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $csv1 = implode("\n", [
        'Ensemble,Song Title,Composer,Arranger,Show Lyrics,Program Notes',
        'Choir A,Old Song,Composer A,,0,',
    ]);

    $csv2 = implode("\n", [
        'Ensemble,Song Title,Composer,Arranger,Show Lyrics,Program Notes',
        'Choir B,New Song,Composer B,,0,',
    ]);

    $component = Livewire::test(PowerUserForm::class)
        ->call('processSongsCsv', $csv1)
        ->call('processSongsCsv', $csv2);

    $wizardEnsembles = $component->get('wizardEnsembles');
    expect($wizardEnsembles)->toHaveCount(1);
    expect($wizardEnsembles[0]['name'])->toBe('Choir B');
});

test('processSongsCsv sets error when Song Title column is missing', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $csv = implode("\n", [
        'Ensemble,Composer',
        'Concert Choir,Schubert',
    ]);

    $component = Livewire::test(PowerUserForm::class)
        ->call('processSongsCsv', $csv);

    expect($component->get('songsCsvResult')['type'])->toBe('error');
});

test('processSongsCsv sets error for header-only file', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test(PowerUserForm::class)
        ->call('processSongsCsv', "Ensemble,Song Title,Composer\n");

    expect($component->get('songsCsvResult')['type'])->toBe('error');
});

test('processSongsCsv matches existing school ensemble by name', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $ensemble = Ensemble::factory()->for($school)->create(['ensemble_name' => 'Concert Choir']);

    $this->actingAs($user);

    $csv = implode("\n", [
        'Ensemble,Song Title,Composer,Arranger,Show Lyrics,Program Notes',
        'Concert Choir,Ave Maria,Schubert,,0,',
    ]);

    $component = Livewire::test(PowerUserForm::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->call('loadProgram')
        ->call('processSongsCsv', $csv);

    $wizardEnsembles = $component->get('wizardEnsembles');
    expect($wizardEnsembles[0]['id'])->toBe($ensemble->id);
});
