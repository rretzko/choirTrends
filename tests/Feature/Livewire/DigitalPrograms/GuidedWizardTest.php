<?php

use App\Livewire\DigitalPrograms\GuidedWizard;
use App\Models\DigitalProgram;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to login', function () {
    $this->get(route('digital-programs.create.guided'))
        ->assertRedirect(route('login'));
});

test('authenticated user can access the wizard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('digital-programs.create.guided'))
        ->assertOk()
        ->assertSeeLivewire(GuidedWizard::class);
});

test('wizard starts on step 1', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->assertSet('step', 1);
});

test('step 1 requires startChoice', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->call('nextStep')
        ->assertHasErrors('startChoice')
        ->assertSet('step', 1);
});

test('step 1 with existing program creates a digital program', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->call('nextStep')
        ->assertSet('step', 2)
        ->assertSet('resolvedProgramId', $program->id);

    expect(DigitalProgram::where('program_id', $program->id)->where('user_id', $user->id)->exists())->toBeTrue();
});

test('step 1 existing program requires a selected program id', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('startChoice', 'existing')
        ->call('nextStep')
        ->assertHasErrors('selectedProgramId')
        ->assertSet('step', 1);
});

test('step 1 new program creates both program and digital program', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('startChoice', 'new')
        ->set('newEventName', 'Spring Concert')
        ->set('newEventDate', '2026-05-10')
        ->set('newDirectorName', 'Jane Smith')
        ->set('newSchoolName', 'Lincoln High School')
        ->call('nextStep')
        ->assertSet('step', 2);

    expect(Program::where('event_name', 'Spring Concert')->where('user_id', $user->id)->exists())->toBeTrue();
    expect(DigitalProgram::where('user_id', $user->id)->exists())->toBeTrue();
});

test('step 1 new program validates required fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('startChoice', 'new')
        ->call('nextStep')
        ->assertHasErrors(['newEventName', 'newEventDate', 'newDirectorName', 'newSchoolName'])
        ->assertSet('step', 1);
});

test('step 2 saves theme and advances to step 3', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 2)
        ->set('digitalProgramId', $dp->id)
        ->set('resolvedProgramId', $program->id)
        ->set('theme', 'WinterConcert')
        ->set('printOrientation', 'Landscape')
        ->call('nextStep')
        ->assertSet('step', 3);

    expect($dp->fresh()->theme)->toBe('WinterConcert');
    expect($dp->fresh()->print_orientation)->toBe('Landscape');
});

test('step 4 lyrics gate blocks advance when lyrics enabled without acknowledgment', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);
    $song = SongTitle::factory()->create();

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 4)
        ->set('digitalProgramId', $dp->id)
        ->set('resolvedProgramId', $program->id)
        ->set('songSettings', [[
            'songTitleId' => $song->id,
            'title' => 'Ave Maria',
            'composer' => '',
            'hasLyrics' => true,
            'showLyrics' => true,
        ]])
        ->set('lyricsCopyrightAcknowledged', false)
        ->call('nextStep')
        ->assertHasErrors('lyricsCopyrightAcknowledged')
        ->assertSet('step', 4);
});

test('step 4 advances when lyrics enabled with acknowledgment', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);
    $song = SongTitle::factory()->create();

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 4)
        ->set('digitalProgramId', $dp->id)
        ->set('resolvedProgramId', $program->id)
        ->set('songSettings', [[
            'songTitleId' => $song->id,
            'title' => 'Ave Maria',
            'composer' => '',
            'hasLyrics' => true,
            'showLyrics' => true,
        ]])
        ->set('lyricsCopyrightAcknowledged', true)
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('step', 5);
});

test('step 5 student names gate blocks advance when students added without acknowledgment', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 5)
        ->set('digitalProgramId', $dp->id)
        ->set('resolvedProgramId', $program->id)
        ->set('rosters', ['general' => [
            ['student_name' => 'Jane Smith', 'voice_part' => 'Soprano I', 'honorIndexes' => []],
        ]])
        ->set('honors', ['general' => []])
        ->set('studentNamesAcknowledged', false)
        ->call('nextStep')
        ->assertHasErrors('studentNamesAcknowledged')
        ->assertSet('step', 5);
});

test('step 5 with no students advances without acknowledgment', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 5)
        ->set('digitalProgramId', $dp->id)
        ->set('resolvedProgramId', $program->id)
        ->set('rosters', [])
        ->set('honors', [])
        ->set('studentNamesAcknowledged', false)
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('step', 6);
});

test('publish on step 6 marks program as published and redirects to index', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->create(['is_published' => false]);

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 6)
        ->set('digitalProgramId', $dp->id)
        ->call('publish')
        ->assertRedirect(route('digital-programs.index'));

    expect($dp->fresh()->is_published)->toBeTrue();
});

test('save as draft on step 6 redirects to index without publishing', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->create(['is_published' => false]);

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 6)
        ->set('digitalProgramId', $dp->id)
        ->call('saveDraft')
        ->assertRedirect(route('digital-programs.index'));

    expect($dp->fresh()->is_published)->toBeFalse();
});

test('previous step goes back one step', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 3)
        ->call('previousStep')
        ->assertSet('step', 2);
});

test('previous step does not go below step 1', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 1)
        ->call('previousStep')
        ->assertSet('step', 1);
});
