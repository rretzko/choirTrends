<?php

use App\Livewire\Catalog\Index;
use App\Models\Artist;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use Livewire\Livewire;

test('guest can view catalog when enabled', function () {
    $user = User::factory()->withCatalog()->create();
    $school = School::factory()->create();
    Program::factory()->for($user)->for($school)->create();

    $this->get(route('catalog.show', $user->catalog_token))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

test('guest gets 404 when catalog is disabled', function () {
    $user = User::factory()->create([
        'catalog_token' => \Illuminate\Support\Str::uuid()->toString(),
        'catalog_enabled_at' => null,
    ]);

    $this->get(route('catalog.show', $user->catalog_token))
        ->assertNotFound();
});

test('guest gets 404 with invalid token', function () {
    $this->get(route('catalog.show', 'invalid-token-that-does-not-exist'))
        ->assertNotFound();
});

test('programs are displayed correctly', function () {
    $user = User::factory()->withCatalog()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    Livewire::test(Index::class, ['token' => $user->catalog_token])
        ->assertSee($program->event_name)
        ->assertSee($school->school_name)
        ->assertSee($user->name);
});

test('school filter works', function () {
    $user = User::factory()->withCatalog()->create();
    $school1 = School::factory()->create(['school_name' => 'Alpha School']);
    $school2 = School::factory()->create(['school_name' => 'Beta School']);
    $program1 = Program::factory()->for($user)->for($school1)->create();
    $program2 = Program::factory()->for($user)->for($school2)->create();

    Livewire::test(Index::class, ['token' => $user->catalog_token])
        ->assertSee($program1->event_name)
        ->assertSee($program2->event_name)
        ->set('schoolFilter', (string) $school1->id)
        ->assertSee($program1->event_name)
        ->assertDontSee($program2->event_name);
});

test('program details modal loads correctly', function () {
    $user = User::factory()->withCatalog()->create();
    $school = School::factory()->create();
    $ensemble = Ensemble::factory()->for($school)->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $composer = Artist::factory()->create();
    $songTitle = SongTitle::factory()->create(['composer_id' => $composer->id]);
    $program->songTitles()->attach($songTitle->id, [
        'ensemble_id' => $ensemble->id,
        'sort_order' => 1,
    ]);

    Livewire::test(Index::class, ['token' => $user->catalog_token])
        ->call('showProgramDetails', $program->id)
        ->assertSet('selectedProgram.id', $program->id)
        ->assertSee($program->event_name)
        ->assertSee($songTitle->song_title)
        ->assertSee($composer->artist_name);
});

test('no edit or auth-only elements are visible', function () {
    $user = User::factory()->withCatalog()->create();
    $school = School::factory()->create();
    Program::factory()->for($user)->for($school)->create();

    Livewire::test(Index::class, ['token' => $user->catalog_token])
        ->assertDontSee('Add Program')
        ->assertDontSee('pencil-square');
});

test('only shows programs belonging to the catalog owner', function () {
    $user = User::factory()->withCatalog()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create();
    $myProgram = Program::factory()->for($user)->for($school)->create();
    $otherProgram = Program::factory()->for($otherUser)->for($school)->create();

    Livewire::test(Index::class, ['token' => $user->catalog_token])
        ->assertSee($myProgram->event_name)
        ->assertDontSee($otherProgram->event_name);
});

test('concert video button shows when program has video', function () {
    $user = User::factory()->withCatalog()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->withVideo()->create();

    Livewire::test(Index::class, ['token' => $user->catalog_token])
        ->call('showProgramDetails', $program->id)
        ->assertSee('Watch Concert Video');
});

test('concert video button hidden when program has no video', function () {
    $user = User::factory()->withCatalog()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    Livewire::test(Index::class, ['token' => $user->catalog_token])
        ->call('showProgramDetails', $program->id)
        ->assertDontSee('Watch Concert Video');
});

test('song video icon shows in program details when song has video', function () {
    $user = User::factory()->withCatalog()->create();
    $school = School::factory()->create();
    $ensemble = Ensemble::factory()->for($school)->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create(['song_title' => 'Song With Video']);
    $program->songTitles()->attach($song->id, [
        'ensemble_id' => $ensemble->id,
        'sort_order' => 1,
        'video_path' => 'mp4s/songs/catalog-test.mp4',
        'video_visibility' => 'Private',
        'video_uploaded_at' => now(),
    ]);

    Livewire::test(Index::class, ['token' => $user->catalog_token])
        ->call('showProgramDetails', $program->id)
        ->assertSeeHtml('Watch Video');
});

test('playConcertVideo sets modal properties correctly', function () {
    $user = User::factory()->withCatalog()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->withVideo()->create();

    Livewire::test(Index::class, ['token' => $user->catalog_token])
        ->call('showProgramDetails', $program->id)
        ->call('playConcertVideo')
        ->assertSet('videoProgramId', $program->id)
        ->assertSet('showConcertVideo', true);
});

test('playSongVideo sets modal properties correctly', function () {
    $user = User::factory()->withCatalog()->create();
    $school = School::factory()->create();
    $ensemble = Ensemble::factory()->for($school)->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create(['song_title' => 'Test Song']);
    $program->songTitles()->attach($song->id, [
        'ensemble_id' => $ensemble->id,
        'sort_order' => 1,
        'video_path' => 'mp4s/songs/test.mp4',
        'video_visibility' => 'Private',
        'video_uploaded_at' => now(),
    ]);

    Livewire::test(Index::class, ['token' => $user->catalog_token])
        ->call('showProgramDetails', $program->id)
        ->call('playSongVideo', $song->id, 'Test Song')
        ->assertSet('videoSongTitleId', $song->id)
        ->assertSet('videoProgramId', $program->id)
        ->assertSet('videoSongName', 'Test Song')
        ->assertSet('showConcertVideo', false);
});
