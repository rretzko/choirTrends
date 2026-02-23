<?php

use App\Livewire\Programs\Index;
use App\Models\Artist;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use App\Models\UserPrivacy;
use Livewire\Livewire;

test('programs index page can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('programs.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

test('programs index displays programs with my filter', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $myProgram = Program::factory()->for($user)->for($school)->create();

    $otherUser = User::factory()->create();
    $otherProgram = Program::factory()->for($otherUser)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'my')
        ->assertSet('filter', 'my')
        ->assertSee($myProgram->event_name)
        ->assertDontSee($otherProgram->event_name);
});

test('programs index displays all programs with all filter', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $myProgram = Program::factory()->for($user)->for($school)->create();

    $otherUser = User::factory()->create();
    $otherProgram = Program::factory()->for($otherUser)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertSee($myProgram->event_name)
        ->assertSee($otherProgram->event_name);
});

test('guests cannot access programs index', function () {
    $this->get(route('programs.index'))
        ->assertRedirect(route('login'));
});

test('school name is masked when program owner has school privacy enabled', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    UserPrivacy::factory()->create([
        'user_id' => $otherUser->id,
        'school' => true,
    ]);

    $school = School::factory()->create(['school_name' => 'Secret High School']);
    $otherProgram = Program::factory()->for($otherUser)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertSee('School'.$school->id)
        ->assertDontSee('Secret High School');
});

test('director name is masked when program owner has name privacy enabled', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    UserPrivacy::factory()->create([
        'user_id' => $otherUser->id,
        'name' => true,
    ]);

    $school = School::factory()->create();
    $otherProgram = Program::factory()->for($otherUser)->for($school)->create([
        'director_name' => 'John Secret',
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertSee('Director'.$otherUser->id)
        ->assertDontSee('John Secret');
});

test('school and director are shown when viewing own program with privacy enabled', function () {
    $user = User::factory()->create();

    UserPrivacy::factory()->create([
        'user_id' => $user->id,
        'school' => true,
        'name' => true,
    ]);

    $school = School::factory()->create(['school_name' => 'My Private School']);
    $program = Program::factory()->for($user)->for($school)->create([
        'director_name' => 'My Real Name',
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('My Private School')
        ->assertSee('My Real Name');
});

test('school and director are shown when owner has no privacy settings', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // No privacy settings for other user
    $school = School::factory()->create(['school_name' => 'Public School']);
    $otherProgram = Program::factory()->for($otherUser)->for($school)->create([
        'director_name' => 'Public Director',
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertSee('Public School')
        ->assertSee('Public Director');
});

test('clicking program name loads program details', function () {
    $user = User::factory()->create();
    $school = School::factory()->create(['school_name' => 'Test School']);
    $program = Program::factory()->for($user)->for($school)->create([
        'event_name' => 'Spring Concert',
        'director_name' => 'Jane Doe',
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('showProgramDetails', $program->id)
        ->assertSet('selectedProgram.id', $program->id);
});

test('program details modal shows song titles with composer and arranger', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create([
        'event_name' => 'Winter Concert',
    ]);

    $composer = Artist::factory()->create(['artist_name' => 'Johann Bach']);
    $arranger = Artist::factory()->create(['artist_name' => 'John Rutter']);
    $songTitle = SongTitle::factory()->create([
        'song_title' => 'Ave Maria',
        'composer_id' => $composer->id,
        'arranger_id' => $arranger->id,
    ]);
    $program->songTitles()->attach($songTitle);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('showProgramDetails', $program->id)
        ->assertSee('Ave Maria')
        ->assertSee('Johann Bach')
        ->assertSee('arr. John Rutter');
});

test('program details modal groups songs by ensemble', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $concertChoir = Ensemble::factory()->create([
        'school_id' => $school->id,
        'ensemble_name' => 'Concert Choir',
    ]);
    $chamberSingers = Ensemble::factory()->create([
        'school_id' => $school->id,
        'ensemble_name' => 'Chamber Singers',
    ]);

    $song1 = SongTitle::factory()->create(['song_title' => 'Gloria']);
    $song2 = SongTitle::factory()->create(['song_title' => 'Ave Maria']);
    $song3 = SongTitle::factory()->create(['song_title' => 'Lux Aurumque']);

    // Attach songs with ensemble associations
    $program->songTitles()->attach([
        $song1->id => ['ensemble_id' => $chamberSingers->id],
        $song2->id => ['ensemble_id' => $concertChoir->id],
        $song3->id => ['ensemble_id' => $concertChoir->id],
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Index::class)
        ->call('showProgramDetails', $program->id);

    // Check that songs are grouped by ensemble
    $songsByEnsemble = $component->get('songsByEnsemble');
    expect($songsByEnsemble)->toHaveCount(2);

    // Ensembles should be sorted alphabetically
    expect($songsByEnsemble[0]['ensemble']->ensemble_name)->toBe('Chamber Singers');
    expect($songsByEnsemble[1]['ensemble']->ensemble_name)->toBe('Concert Choir');

    // Verify songs are in the correct groups
    expect($songsByEnsemble[0]['songs'])->toHaveCount(1);
    expect($songsByEnsemble[1]['songs'])->toHaveCount(2);

    // Check the view displays ensemble names
    $component->assertSee('Chamber Singers')
        ->assertSee('Concert Choir')
        ->assertSee('Gloria')
        ->assertSee('Ave Maria')
        ->assertSee('Lux Aurumque');
});

test('programs are sorted by school name by default', function () {
    $user = User::factory()->create();

    $schoolB = School::factory()->create(['school_name' => 'Beta School']);
    $schoolA = School::factory()->create(['school_name' => 'Alpha School']);

    Program::factory()->for($user)->for($schoolB)->create(['event_name' => 'Beta Concert']);
    Program::factory()->for($user)->for($schoolA)->create(['event_name' => 'Alpha Concert']);

    $this->actingAs($user);

    $component = Livewire::test(Index::class)
        ->assertSet('sortBy', 'school')
        ->assertSet('sortDirection', 'asc');

    $programs = $component->viewData('programs');
    $names = $programs->pluck('event_name')->all();

    expect($names)->toBe(['Alpha Concert', 'Beta Concert']);
});

test('programs can be sorted by director last name', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();

    Program::factory()->for($user)->for($school)->create([
        'event_name' => 'Concert B',
        'director_name' => 'John Smith',
    ]);
    Program::factory()->for($user)->for($school)->create([
        'event_name' => 'Concert A',
        'director_name' => 'Mrs. Amy Adams',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Index::class)
        ->call('sort', 'director');

    $programs = $component->viewData('programs');
    $names = $programs->pluck('event_name')->all();

    expect($names)->toBe(['Concert A', 'Concert B']);
});

test('program details modal shows songs without ensemble under Other', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create(['song_title' => 'Orphan Song']);

    // Attach song without ensemble
    $program->songTitles()->attach($song->id);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('showProgramDetails', $program->id)
        ->assertSee('Other')
        ->assertSee('Orphan Song');
});

test('school filter filters programs by school', function () {
    $user = User::factory()->create();
    $schoolA = School::factory()->create(['school_name' => 'Alpha School']);
    $schoolB = School::factory()->create(['school_name' => 'Beta School']);

    Program::factory()->for($user)->for($schoolA)->create(['event_name' => 'Alpha Concert']);
    Program::factory()->for($user)->for($schoolB)->create(['event_name' => 'Beta Concert']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('Alpha Concert')
        ->assertSee('Beta Concert')
        ->set('schoolFilter', [(string) $schoolA->id])
        ->assertSee('Alpha Concert')
        ->assertDontSee('Beta Concert')
        ->set('schoolFilter', [])
        ->assertSee('Alpha Concert')
        ->assertSee('Beta Concert');
});

test('school filter supports multiple schools', function () {
    $user = User::factory()->create();
    $schoolA = School::factory()->create(['school_name' => 'Alpha School']);
    $schoolB = School::factory()->create(['school_name' => 'Beta School']);
    $schoolC = School::factory()->create(['school_name' => 'Gamma School']);

    Program::factory()->for($user)->for($schoolA)->create(['event_name' => 'Alpha Concert']);
    Program::factory()->for($user)->for($schoolB)->create(['event_name' => 'Beta Concert']);
    Program::factory()->for($user)->for($schoolC)->create(['event_name' => 'Gamma Concert']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('schoolFilter', [(string) $schoolA->id, (string) $schoolB->id])
        ->assertSee('Alpha Concert')
        ->assertSee('Beta Concert')
        ->assertDontSee('Gamma Concert');
});

test('school filter defaults to user schools', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    $component = Livewire::test(Index::class);

    expect($component->get('schoolFilter'))->toBe([(string) $school->id]);
});

test('school filter resets to user schools when my/all filter changes', function () {
    $user = User::factory()->create();
    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();
    Program::factory()->for($user)->for($schoolA)->create();

    $otherUser = User::factory()->create();
    Program::factory()->for($otherUser)->for($schoolB)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('schoolFilter', [(string) $schoolA->id, (string) $schoolB->id])
        ->set('filter', 'my')
        ->assertSet('schoolFilter', [(string) $schoolA->id]);
});

test('school dropdown only shows schools with visible programs', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    UserPrivacy::factory()->create([
        'user_id' => $otherUser->id,
        'school' => true,
    ]);

    $visibleSchool = School::factory()->create(['school_name' => 'Public School']);
    $hiddenSchool = School::factory()->create(['school_name' => 'Private School']);

    Program::factory()->for($user)->for($visibleSchool)->create();
    Program::factory()->for($otherUser)->for($hiddenSchool)->create();

    $this->actingAs($user);

    $component = Livewire::test(Index::class)
        ->set('filter', 'all');

    $schools = $component->viewData('schools');
    $schoolNames = $schools->pluck('school_name')->all();

    expect($schoolNames)->toContain('Public School');
    expect($schoolNames)->not->toContain('Private School');
});

test('school dropdown shows all user schools in my filter', function () {
    $user = User::factory()->create();
    $schoolA = School::factory()->create(['school_name' => 'First School']);
    $schoolB = School::factory()->create(['school_name' => 'Second School']);

    Program::factory()->for($user)->for($schoolA)->create();
    Program::factory()->for($user)->for($schoolB)->create();

    $this->actingAs($user);

    $component = Livewire::test(Index::class)
        ->set('filter', 'my');

    $schools = $component->viewData('schools');
    $schoolNames = $schools->pluck('school_name')->all();

    expect($schoolNames)->toContain('First School');
    expect($schoolNames)->toContain('Second School');
});
