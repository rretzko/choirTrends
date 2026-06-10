<?php

use App\Livewire\DigitalPrograms\GuidedWizard;
use App\Models\DigitalProgram;
use App\Models\Ensemble;
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

test('step 1 with existing program shows edit sub-step on first next', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->call('nextStep')
        ->assertSet('editingExistingProgram', true)
        ->assertSet('step', 1)
        ->assertSet('newEventName', $program->event_name);
});

test('step 1 with existing program creates a digital program', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->set('editingExistingProgram', true)
        ->set('newEventName', $program->event_name)
        ->set('newEventDate', $program->event_date->format('Y-m-d'))
        ->set('newDirectorName', $program->director_name ?? '')
        ->set('newSchoolName', $school->school_name)
        ->call('nextStep')
        ->assertSet('step', 2)
        ->assertSet('resolvedProgramId', $program->id);

    expect(DigitalProgram::where('program_id', $program->id)->where('user_id', $user->id)->exists())->toBeTrue();
});

test('step 1 with existing program reuses existing digital program and loads its content', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create([
        'program_id' => $program->id,
        'welcome_message' => '<p>Hello audience!</p>',
        'acknowledgments' => '<p>Thanks to all.</p>',
        'sponsor_text' => '<p>Sponsored by ACME.</p>',
        'theme' => 'Holiday',
        'print_orientation' => 'Landscape',
    ]);

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->set('editingExistingProgram', true)
        ->set('newEventName', $program->event_name)
        ->set('newEventDate', $program->event_date->format('Y-m-d'))
        ->set('newDirectorName', $program->director_name ?? '')
        ->set('newSchoolName', $school->school_name)
        ->call('nextStep')
        ->assertSet('step', 2)
        ->assertSet('digitalProgramId', $dp->id)
        ->assertSet('welcomeMessage', '<p>Hello audience!</p>')
        ->assertSet('acknowledgments', '<p>Thanks to all.</p>')
        ->assertSet('sponsorText', '<p>Sponsored by ACME.</p>')
        ->assertSet('theme', 'Holiday')
        ->assertSet('printOrientation', 'Landscape');

    // No duplicate digital program should be created
    expect(DigitalProgram::where('program_id', $program->id)->where('user_id', $user->id)->count())->toBe(1);
});

test('step 1 with existing program updates program details on confirm', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('startChoice', 'existing')
        ->set('selectedProgramId', $program->id)
        ->set('editingExistingProgram', true)
        ->set('newEventName', 'Updated Concert')
        ->set('newEventDate', '2026-08-01')
        ->set('newDirectorName', 'New Director')
        ->set('newSchoolName', $school->school_name)
        ->call('nextStep')
        ->assertSet('step', 2);

    expect($program->fresh()->event_name)->toBe('Updated Concert');
    expect($program->fresh()->director_name)->toBe('New Director');
});

test('previous step from edit sub-step returns to selection', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 1)
        ->set('startChoice', 'existing')
        ->set('editingExistingProgram', true)
        ->call('previousStep')
        ->assertSet('editingExistingProgram', false)
        ->assertSet('step', 1);
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

// ── Step 4: Ensembles ─────────────────────────────────────────────────────────

test('step 4 advances with no ensembles selected', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 4)
        ->set('digitalProgramId', $dp->id)
        ->set('resolvedProgramId', $program->id)
        ->set('wizardEnsembles', [])
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('step', 5);
});

test('step 4 can add an existing school ensemble', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);
    $ensemble = Ensemble::factory()->for($school)->create(['ensemble_name' => 'Concert Choir']);

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 4)
        ->set('digitalProgramId', $dp->id)
        ->set('resolvedProgramId', $program->id)
        ->call('addSelectedEnsemble', $ensemble->id)
        ->assertSet('wizardEnsembles.0.id', $ensemble->id)
        ->assertSet('wizardEnsembles.0.name', 'Concert Choir');
});

test('step 4 does not add the same ensemble twice', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);
    $ensemble = Ensemble::factory()->for($school)->create();

    $this->actingAs($user);

    $component = Livewire::test(GuidedWizard::class)
        ->set('step', 4)
        ->set('digitalProgramId', $dp->id)
        ->set('resolvedProgramId', $program->id)
        ->call('addSelectedEnsemble', $ensemble->id)
        ->call('addSelectedEnsemble', $ensemble->id);

    expect(count($component->get('wizardEnsembles')))->toBe(1);
});

test('step 4 can create a new ensemble', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 4)
        ->set('digitalProgramId', $dp->id)
        ->set('resolvedProgramId', $program->id)
        ->set('newEnsembleName', 'Treble Choir')
        ->set('newEnsembleType', 'SopranoAlto')
        ->call('createWizardEnsemble')
        ->assertSet('wizardEnsembles.0.id', null)
        ->assertSet('wizardEnsembles.0.name', 'Treble Choir')
        ->assertSet('wizardEnsembles.0.type', 'SopranoAlto')
        ->assertSet('newEnsembleName', '');
});

test('step 4 persists new ensembles to the database when advancing to step 5', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 4)
        ->set('digitalProgramId', $dp->id)
        ->set('resolvedProgramId', $program->id)
        ->set('wizardEnsembles', [['id' => null, 'name' => 'Treble Choir', 'type' => 'SopranoAlto']])
        ->call('nextStep')
        ->assertSet('step', 5)
        ->assertSet('wizardEnsembles.0.id', Ensemble::where('ensemble_name', 'Treble Choir')->value('id'));

    expect(Ensemble::where('ensemble_name', 'Treble Choir')->where('school_id', $school->id)->exists())->toBeTrue();
});

test('step 4 can reorder ensembles up and down', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);
    $a = Ensemble::factory()->for($school)->create(['ensemble_name' => 'Alpha Choir']);
    $b = Ensemble::factory()->for($school)->create(['ensemble_name' => 'Beta Choir']);
    $c = Ensemble::factory()->for($school)->create(['ensemble_name' => 'Gamma Choir']);

    $this->actingAs($user);

    $component = Livewire::test(GuidedWizard::class)
        ->set('step', 4)
        ->set('digitalProgramId', $dp->id)
        ->set('resolvedProgramId', $program->id)
        ->set('wizardEnsembles', [
            ['id' => $a->id, 'name' => 'A', 'type' => 'Satb'],
            ['id' => $b->id, 'name' => 'B', 'type' => 'Satb'],
            ['id' => $c->id, 'name' => 'C', 'type' => 'Satb'],
        ])
        ->call('moveWizardEnsembleDown', 0); // A moves to index 1

    expect($component->get('wizardEnsembles.0.name'))->toBe('B');
    expect($component->get('wizardEnsembles.1.name'))->toBe('A');

    $component->call('moveWizardEnsembleUp', 2); // C moves to index 1

    expect($component->get('wizardEnsembles.1.name'))->toBe('C');
    expect($component->get('wizardEnsembles.2.name'))->toBe('A');
});

test('step 4 can remove an ensemble', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);
    $ensemble = Ensemble::factory()->for($school)->create();

    $this->actingAs($user);

    $component = Livewire::test(GuidedWizard::class)
        ->set('step', 4)
        ->set('digitalProgramId', $dp->id)
        ->set('resolvedProgramId', $program->id)
        ->call('addSelectedEnsemble', $ensemble->id)
        ->call('removeWizardEnsemble', 0);

    expect(count($component->get('wizardEnsembles')))->toBe(0);
});

// ── Step 5: Songs ─────────────────────────────────────────────────────────────

test('step 5 advances with no songs', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 5)
        ->set('digitalProgramId', $dp->id)
        ->set('resolvedProgramId', $program->id)
        ->set('wizardEnsembles', [])
        ->set('ensembleSongs', [])
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('step', 6);
});

test('step 5 can add and remove song rows', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);

    $this->actingAs($user);

    $component = Livewire::test(GuidedWizard::class)
        ->set('step', 5)
        ->set('digitalProgramId', $dp->id)
        ->set('resolvedProgramId', $program->id)
        ->set('wizardEnsembles', [['id' => null, 'name' => 'Choir', 'type' => 'Satb']])
        ->set('ensembleSongs', [[]])
        ->call('addSongRow', 0)
        ->call('addSongRow', 0);

    expect(count($component->get('ensembleSongs.0')))->toBe(2);

    $component->call('removeSongRow', 0, 0);

    expect(count($component->get('ensembleSongs.0')))->toBe(1);
});

test('step 5 lyrics gate blocks advance when lyrics enabled without acknowledgment', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 5)
        ->set('digitalProgramId', $dp->id)
        ->set('resolvedProgramId', $program->id)
        ->set('wizardEnsembles', [['id' => null, 'name' => 'Choir', 'type' => 'Satb']])
        ->set('ensembleSongs', [[
            ['title' => 'Ave Maria', 'composer' => '', 'showLyrics' => true],
        ]])
        ->set('lyricsCopyrightAcknowledged', false)
        ->call('nextStep')
        ->assertHasErrors('lyricsCopyrightAcknowledged')
        ->assertSet('step', 5);
});

test('step 5 advances when lyrics enabled with acknowledgment', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 5)
        ->set('digitalProgramId', $dp->id)
        ->set('resolvedProgramId', $program->id)
        ->set('wizardEnsembles', [['id' => null, 'name' => 'Choir', 'type' => 'Satb']])
        ->set('ensembleSongs', [[
            ['title' => 'Ave Maria', 'composer' => 'Schubert', 'showLyrics' => true],
        ]])
        ->set('lyricsCopyrightAcknowledged', true)
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('step', 6);
});

test('step 5 saves songs to an already-persisted ensemble when advancing', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);
    // Ensemble already persisted — as happens after step 4 completes
    $ensemble = Ensemble::factory()->for($school)->create(['ensemble_name' => 'Concert Choir']);

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 5)
        ->set('digitalProgramId', $dp->id)
        ->set('resolvedProgramId', $program->id)
        ->set('wizardEnsembles', [['id' => $ensemble->id, 'name' => 'Concert Choir', 'type' => 'Satb']])
        ->set('ensembleSongs', [[
            ['title' => 'Lux Aeterna', 'composer' => 'Elgar', 'showLyrics' => false],
        ]])
        ->call('nextStep');

    expect(SongTitle::where('song_title', 'Lux Aeterna')->exists())->toBeTrue();
    expect($program->fresh()->songTitles()->count())->toBe(1);
});

// ── Step 6: Roster & Honours ──────────────────────────────────────────────────

test('step 6 student names gate blocks advance when students added without acknowledgment', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 6)
        ->set('digitalProgramId', $dp->id)
        ->set('resolvedProgramId', $program->id)
        ->set('rosters', ['general' => [
            ['student_name' => 'Jane Smith', 'voice_part' => 'Soprano I', 'honorIndexes' => []],
        ]])
        ->set('honors', ['general' => []])
        ->set('studentNamesAcknowledged', false)
        ->call('nextStep')
        ->assertHasErrors('studentNamesAcknowledged')
        ->assertSet('step', 6);
});

test('step 6 with no students advances without acknowledgment', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 6)
        ->set('digitalProgramId', $dp->id)
        ->set('resolvedProgramId', $program->id)
        ->set('rosters', [])
        ->set('honors', [])
        ->set('studentNamesAcknowledged', false)
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('step', 7);
});

// ── Step 7: Publish ───────────────────────────────────────────────────────────

test('publish on step 7 marks program as published and redirects to index', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->create(['is_published' => false]);

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 7)
        ->set('digitalProgramId', $dp->id)
        ->call('publish')
        ->assertRedirect(route('digital-programs.index'));

    expect($dp->fresh()->is_published)->toBeTrue();
});

test('save as draft on step 7 redirects to index without publishing', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->create(['is_published' => false]);

    $this->actingAs($user);

    Livewire::test(GuidedWizard::class)
        ->set('step', 7)
        ->set('digitalProgramId', $dp->id)
        ->call('saveDraft')
        ->assertRedirect(route('digital-programs.index'));

    expect($dp->fresh()->is_published)->toBeFalse();
});

test('assistant cannot publish on step 7', function () {
    $director = User::factory()->create();
    $assistant = User::factory()->assistant($director)->create();
    $dp = DigitalProgram::factory()->for($director)->create(['is_published' => false]);

    $this->actingAs($assistant);

    Livewire::test(GuidedWizard::class)
        ->set('step', 7)
        ->set('digitalProgramId', $dp->id)
        ->call('publish')
        ->assertForbidden();

    expect($dp->fresh()->is_published)->toBeFalse();
});

test('assistant can save as draft on step 7', function () {
    $director = User::factory()->create();
    $assistant = User::factory()->assistant($director)->create();
    $dp = DigitalProgram::factory()->for($director)->create(['is_published' => false]);

    $this->actingAs($assistant);

    Livewire::test(GuidedWizard::class)
        ->set('step', 7)
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
