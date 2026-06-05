<?php

use App\Livewire\DigitalPrograms\Configure;
use App\Livewire\DigitalPrograms\Index;
use App\Models\DigitalProgram;
use App\Models\DigitalProgramHonor;
use App\Models\DigitalProgramRoster;
use App\Models\DigitalProgramSongSetting;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to login', function () {
    $dp = DigitalProgram::factory()->create();

    $this->get(route('digital-programs.configure', $dp))
        ->assertRedirect(route('login'));
});

test('owner can access the configure page', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('digital-programs.configure', $dp))
        ->assertOk()
        ->assertSeeLivewire(Configure::class);
});

test('non-owner receives 403', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $dp = DigitalProgram::factory()->for($owner)->create();

    $this->actingAs($other)
        ->get(route('digital-programs.configure', $dp))
        ->assertForbidden();
});

test('mount pre-populates scalar fields from existing digital program', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->create([
        'theme' => 'WinterConcert',
        'print_orientation' => 'Landscape',
        'welcome_message' => 'Welcome everyone!',
        'acknowledgments' => 'Thanks to all.',
        'sponsor_text' => 'Sponsored by Acme.',
    ]);

    $this->actingAs($user);

    Livewire::test(Configure::class, ['digitalProgram' => $dp])
        ->assertSet('theme', 'WinterConcert')
        ->assertSet('printOrientation', 'Landscape')
        ->assertSet('welcomeMessage', 'Welcome everyone!')
        ->assertSet('acknowledgments', 'Thanks to all.')
        ->assertSet('sponsorText', 'Sponsored by Acme.');
});

test('mount pre-populates existing song settings', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $song = SongTitle::factory()->create(['song_title' => 'Gloria']);
    $program->songTitles()->attach($song->id, ['sort_order' => 1, 'ensemble_sort_order' => 1]);

    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);
    DigitalProgramSongSetting::create([
        'digital_program_id' => $dp->id,
        'song_title_id' => $song->id,
        'show_lyrics' => true,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Configure::class, ['digitalProgram' => $dp]);
    $settings = $component->get('songSettings');

    expect($settings)->toHaveCount(1);
    expect($settings[0]['title'])->toBe('Gloria');
    expect($settings[0]['showLyrics'])->toBeTrue();
});

test('mount pre-populates existing honors', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->create();

    DigitalProgramHonor::create([
        'digital_program_id' => $dp->id,
        'ensemble_id' => null,
        'label' => 'Section Leader',
        'sort_order' => 1,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Configure::class, ['digitalProgram' => $dp]);
    $honors = $component->get('honors');

    expect($honors['general'])->toHaveCount(1);
    expect($honors['general'][0]['label'])->toBe('Section Leader');
});

test('mount pre-populates existing roster with honor indexes', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->create();

    $honor = DigitalProgramHonor::create([
        'digital_program_id' => $dp->id,
        'ensemble_id' => null,
        'label' => 'All-State',
        'sort_order' => 1,
    ]);

    $roster = DigitalProgramRoster::create([
        'digital_program_id' => $dp->id,
        'ensemble_id' => null,
        'student_name' => 'Jane Smith',
        'sort_order' => 1,
    ]);
    $roster->honors()->attach($honor->id);

    $this->actingAs($user);

    $component = Livewire::test(Configure::class, ['digitalProgram' => $dp]);
    $rosters = $component->get('rosters');

    expect($rosters['general'])->toHaveCount(1);
    expect($rosters['general'][0]['student_name'])->toBe('Jane Smith');
    expect($rosters['general'][0]['honorIndexes'])->toBe([0]);
});

test('save updates scalar fields and redirects to index', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->create([
        'theme' => 'Formal',
        'welcome_message' => 'Old message',
    ]);

    $this->actingAs($user);

    Livewire::test(Configure::class, ['digitalProgram' => $dp])
        ->set('theme', 'Holiday')
        ->set('welcomeMessage', 'New welcome message')
        ->call('save', false)
        ->assertRedirect(route('digital-programs.index'));

    expect($dp->fresh()->theme)->toBe('Holiday');
    expect($dp->fresh()->welcome_message)->toBe('New welcome message');
});

test('save publish sets is_published to true', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->create(['is_published' => false]);

    $this->actingAs($user);

    Livewire::test(Configure::class, ['digitalProgram' => $dp])
        ->call('save', true);

    expect($dp->fresh()->is_published)->toBeTrue();
});

test('save requires lyrics acknowledgment when lyrics are enabled', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $song = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id, ['sort_order' => 1, 'ensemble_sort_order' => 1]);

    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);

    $this->actingAs($user);

    Livewire::test(Configure::class, ['digitalProgram' => $dp])
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
});

test('save updates song settings in database', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $ensemble = Ensemble::factory()->for($school)->create();
    $song = SongTitle::factory()->create(['song_title' => 'Ave Maria']);
    $program->songTitles()->attach($song->id, ['ensemble_id' => $ensemble->id, 'sort_order' => 1]);

    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);

    $this->actingAs($user);

    Livewire::test(Configure::class, ['digitalProgram' => $dp])
        ->set('wizardEnsembles', [['id' => $ensemble->id, 'name' => $ensemble->ensemble_name, 'type' => 'Satb']])
        ->set('ensembleSongs', [[
            ['songTitleId' => $song->id, 'title' => 'Ave Maria', 'composer' => '', 'arranger' => '', 'showLyrics' => true],
        ]])
        ->set('lyricsCopyrightAcknowledged', true)
        ->call('save', false);

    expect(DigitalProgramSongSetting::where('digital_program_id', $dp->id)
        ->where('song_title_id', $song->id)
        ->value('show_lyrics')
    )->toBeTrue();
});

test('save replaces honors and rosters', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->create();

    DigitalProgramHonor::create([
        'digital_program_id' => $dp->id,
        'ensemble_id' => null,
        'label' => 'Old Honor',
        'sort_order' => 1,
    ]);

    $this->actingAs($user);

    Livewire::test(Configure::class, ['digitalProgram' => $dp])
        ->set('honors', ['general' => [['label' => 'New Honor']]])
        ->set('rosters', ['general' => [
            ['student_name' => 'Alice', 'voice_part' => 'Alto', 'honorIndexes' => [0]],
        ]])
        ->set('studentNamesAcknowledged', true)
        ->call('save', false);

    expect(DigitalProgramHonor::where('digital_program_id', $dp->id)->count())->toBe(1);
    expect(DigitalProgramHonor::where('digital_program_id', $dp->id)->value('label'))->toBe('New Honor');
    expect(DigitalProgramRoster::where('digital_program_id', $dp->id)->count())->toBe(1);
    expect(DigitalProgramRoster::where('digital_program_id', $dp->id)->value('student_name'))->toBe('Alice');
});

test('index page shows edit button linking to configure route', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee(route('digital-programs.configure', $dp));
});
