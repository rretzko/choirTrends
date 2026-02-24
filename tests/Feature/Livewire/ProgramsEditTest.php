<?php

use App\Livewire\Programs\Edit;
use App\Livewire\Programs\Index;
use App\Models\Artist;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use Livewire\Livewire;

test('guests cannot access program edit page', function () {
    $program = Program::factory()->create();

    $this->get(route('programs.edit', $program))
        ->assertRedirect(route('login'));
});

test('owner can access their program edit page', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    $this->get(route('programs.edit', $program))
        ->assertOk()
        ->assertSeeLivewire(Edit::class);
});

test('non-owner gets 403 when accessing another users program', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($owner)->for($school)->create();

    $this->actingAs($otherUser);

    $this->get(route('programs.edit', $program))
        ->assertForbidden();
});

test('edit page loads program data correctly', function () {
    $user = User::factory()->create();
    $school = School::factory()->create(['school_name' => 'Test High School']);
    $program = Program::factory()->for($user)->for($school)->create([
        'event_name' => 'Spring Concert',
        'event_date' => '2025-05-15',
        'director_name' => 'Jane Doe',
    ]);

    $this->actingAs($user);

    Livewire::test(Edit::class, ['program' => $program])
        ->assertSet('eventName', 'Spring Concert')
        ->assertSet('eventDate', '2025-05-15')
        ->assertSet('directorName', 'Jane Doe')
        ->assertSet('schoolName', 'Test High School');
});

test('can update event name, date, and director name', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create([
        'event_name' => 'Old Concert',
        'event_date' => '2025-01-01',
        'director_name' => 'Old Director',
    ]);

    $this->actingAs($user);

    Livewire::test(Edit::class, ['program' => $program])
        ->set('eventName', 'New Concert')
        ->set('eventDate', '2025-06-01')
        ->set('directorName', 'New Director')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('programs.index'));

    $program->refresh();
    expect($program->event_name)->toBe('New Concert');
    expect($program->event_date->format('Y-m-d'))->toBe('2025-06-01');
    expect($program->director_name)->toBe('New Director');
});

test('validates required fields', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Edit::class, ['program' => $program])
        ->set('eventName', '')
        ->set('eventDate', '')
        ->set('directorName', '')
        ->set('schoolName', '')
        ->call('save')
        ->assertHasErrors(['eventName', 'eventDate', 'directorName', 'schoolName']);
});

test('prevents duplicate program for same user, event name, and date', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();

    Program::factory()->for($user)->for($school)->create([
        'event_name' => 'Existing Concert',
        'event_date' => '2025-03-01',
    ]);

    $program = Program::factory()->for($user)->for($school)->create([
        'event_name' => 'Other Concert',
        'event_date' => '2025-03-01',
    ]);

    $this->actingAs($user);

    Livewire::test(Edit::class, ['program' => $program])
        ->set('eventName', 'Existing Concert')
        ->call('save')
        ->assertHasErrors('eventName');
});

test('school is editable when only current user references it', function () {
    $user = User::factory()->create();
    $school = School::factory()->create(['school_name' => 'My School']);
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Edit::class, ['program' => $program])
        ->assertSet('schoolEditable', true);
});

test('school is locked when another user references it', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create(['school_name' => 'Shared School']);

    Program::factory()->for($user)->for($school)->create();
    Program::factory()->for($otherUser)->for($school)->create();

    $program = Program::query()->where('user_id', $user->id)->first();

    $this->actingAs($user);

    Livewire::test(Edit::class, ['program' => $program])
        ->assertSet('schoolEditable', false)
        ->assertSee('Shared');
});

test('can rename school when it is exclusive', function () {
    $user = User::factory()->create();
    $school = School::factory()->create(['school_name' => 'Old School Name']);
    $user->schools()->attach($school);
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Edit::class, ['program' => $program])
        ->set('schoolName', 'New School Name')
        ->call('save')
        ->assertHasNoErrors();

    $program->refresh();
    $newSchool = School::find($program->school_id);
    expect($newSchool->school_name)->toBe('New School Name');
});

test('ensemble is editable when only current user references it', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $ensemble = Ensemble::factory()->create(['school_id' => $school->id, 'ensemble_name' => 'Concert Choir']);
    $song = SongTitle::factory()->create(['song_title' => 'Test Song']);
    $program->songTitles()->attach($song->id, ['ensemble_id' => $ensemble->id]);

    $this->actingAs($user);

    $component = Livewire::test(Edit::class, ['program' => $program]);
    $ensembles = $component->get('ensembles');

    expect($ensembles[0]['editable'])->toBeTrue();
});

test('ensemble is locked when another user references it', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create();

    $program = Program::factory()->for($user)->for($school)->create();
    $otherProgram = Program::factory()->for($otherUser)->for($school)->create();

    $ensemble = Ensemble::factory()->create(['school_id' => $school->id, 'ensemble_name' => 'Shared Choir']);
    $song1 = SongTitle::factory()->create(['song_title' => 'Song A']);
    $song2 = SongTitle::factory()->create(['song_title' => 'Song B']);

    $program->songTitles()->attach($song1->id, ['ensemble_id' => $ensemble->id]);
    $otherProgram->songTitles()->attach($song2->id, ['ensemble_id' => $ensemble->id]);

    $this->actingAs($user);

    $component = Livewire::test(Edit::class, ['program' => $program]);
    $ensembles = $component->get('ensembles');

    expect($ensembles[0]['editable'])->toBeFalse();
});

test('song title is editable when only current user references it', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create(['song_title' => 'My Exclusive Song']);
    $program->songTitles()->attach($song->id);

    $this->actingAs($user);

    $component = Livewire::test(Edit::class, ['program' => $program]);
    $ensembles = $component->get('ensembles');

    expect($ensembles[0]['songs'][0]['titleEditable'])->toBeTrue();
});

test('song title is locked when another user references it', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create();

    $program = Program::factory()->for($user)->for($school)->create();
    $otherProgram = Program::factory()->for($otherUser)->for($school)->create();

    $song = SongTitle::factory()->create(['song_title' => 'Shared Song']);
    $program->songTitles()->attach($song->id);
    $otherProgram->songTitles()->attach($song->id);

    $this->actingAs($user);

    $component = Livewire::test(Edit::class, ['program' => $program]);
    $ensembles = $component->get('ensembles');

    expect($ensembles[0]['songs'][0]['titleEditable'])->toBeFalse();
});

test('composer is editable when only current user references the artist', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $composer = Artist::factory()->create(['artist_name' => 'John Bach']);
    $song = SongTitle::factory()->create([
        'song_title' => 'Test Piece',
        'composer_id' => $composer->id,
    ]);
    $program->songTitles()->attach($song->id);

    $this->actingAs($user);

    $component = Livewire::test(Edit::class, ['program' => $program]);
    $ensembles = $component->get('ensembles');

    expect($ensembles[0]['songs'][0]['composerEditable'])->toBeTrue();
});

test('composer is locked when another user references the artist', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create();

    $composer = Artist::factory()->create(['artist_name' => 'Shared Composer']);

    $song1 = SongTitle::factory()->create([
        'song_title' => 'Song X',
        'composer_id' => $composer->id,
    ]);
    $song2 = SongTitle::factory()->create([
        'song_title' => 'Song Y',
        'composer_id' => $composer->id,
    ]);

    $program = Program::factory()->for($user)->for($school)->create();
    $otherProgram = Program::factory()->for($otherUser)->for($school)->create();

    $program->songTitles()->attach($song1->id);
    $otherProgram->songTitles()->attach($song2->id);

    $this->actingAs($user);

    $component = Livewire::test(Edit::class, ['program' => $program]);
    $ensembles = $component->get('ensembles');

    expect($ensembles[0]['songs'][0]['composerEditable'])->toBeFalse();
});

test('arranger is locked when another user references the artist', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create();

    $arranger = Artist::factory()->create(['artist_name' => 'Shared Arranger']);

    $song1 = SongTitle::factory()->create([
        'song_title' => 'Song P',
        'arranger_id' => $arranger->id,
    ]);
    $song2 = SongTitle::factory()->create([
        'song_title' => 'Song Q',
        'arranger_id' => $arranger->id,
    ]);

    $program = Program::factory()->for($user)->for($school)->create();
    $otherProgram = Program::factory()->for($otherUser)->for($school)->create();

    $program->songTitles()->attach($song1->id);
    $otherProgram->songTitles()->attach($song2->id);

    $this->actingAs($user);

    $component = Livewire::test(Edit::class, ['program' => $program]);
    $ensembles = $component->get('ensembles');

    expect($ensembles[0]['songs'][0]['arrangerEditable'])->toBeFalse();
});

test('can add and remove ensembles', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    $component = Livewire::test(Edit::class, ['program' => $program])
        ->assertSet('ensembles', []);

    $component->call('addEnsemble');
    $ensembles = $component->get('ensembles');
    expect($ensembles)->toHaveCount(1);
    expect($ensembles[0]['editable'])->toBeTrue();
    expect($ensembles[0]['songs'])->toHaveCount(1);

    $component->call('removeEnsemble', 0);
    expect($component->get('ensembles'))->toHaveCount(0);
});

test('can add and remove songs within an ensemble', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $ensemble = Ensemble::factory()->create(['school_id' => $school->id]);
    $song = SongTitle::factory()->create(['song_title' => 'First Song']);
    $program->songTitles()->attach($song->id, ['ensemble_id' => $ensemble->id]);

    $this->actingAs($user);

    $component = Livewire::test(Edit::class, ['program' => $program]);
    expect($component->get('ensembles.0.songs'))->toHaveCount(1);

    $component->call('addSong', 0);
    expect($component->get('ensembles.0.songs'))->toHaveCount(2);

    $component->call('removeSong', 0, 1);
    expect($component->get('ensembles.0.songs'))->toHaveCount(1);
});

test('saving creates new song title record when editing to different values', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $composer = Artist::factory()->create(['artist_name' => 'Original Composer']);
    $originalSong = SongTitle::factory()->create([
        'song_title' => 'Original Title',
        'composer_id' => $composer->id,
    ]);
    $program->songTitles()->attach($originalSong->id, ['sort_order' => 1]);

    $originalSongCount = SongTitle::count();

    $this->actingAs($user);

    Livewire::test(Edit::class, ['program' => $program])
        ->set('ensembles.0.songs.0.title', 'New Title')
        ->call('save')
        ->assertHasNoErrors();

    // A new SongTitle record should have been created
    expect(SongTitle::count())->toBeGreaterThanOrEqual($originalSongCount);
    expect(SongTitle::where('song_title', 'New Title')->exists())->toBeTrue();

    // Original song title should still exist (not mutated)
    expect(SongTitle::where('song_title', 'Original Title')->exists())->toBeTrue();
});

test('saving reuses existing song title record when values match', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $composer = Artist::factory()->create(['artist_name' => 'Test Composer']);
    $existingSong = SongTitle::factory()->create([
        'song_title' => 'Existing Song',
        'composer_id' => $composer->id,
    ]);

    $anotherSong = SongTitle::factory()->create(['song_title' => 'My Song']);
    $program->songTitles()->attach($anotherSong->id, ['sort_order' => 1]);

    $this->actingAs($user);

    Livewire::test(Edit::class, ['program' => $program])
        ->set('ensembles.0.songs.0.title', 'Existing Song')
        ->set('ensembles.0.songs.0.composer', 'Test Composer')
        ->call('save')
        ->assertHasNoErrors();

    $program->refresh();
    $attachedSongIds = $program->songTitles->pluck('id')->all();
    expect($attachedSongIds)->toContain($existingSong->id);
});

test('save redirects to programs index with success flash', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Edit::class, ['program' => $program])
        ->call('save')
        ->assertRedirect(route('programs.index'));
});

test('edit button is visible on index for own programs only', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create();

    $myProgram = Program::factory()->for($user)->for($school)->create(['event_name' => 'My Concert']);
    $otherProgram = Program::factory()->for($otherUser)->for($school)->create(['event_name' => 'Other Concert']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertSeeHtml(route('programs.edit', $myProgram))
        ->assertDontSeeHtml(route('programs.edit', $otherProgram));
});

test('can add a new ensemble with songs and save', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Edit::class, ['program' => $program])
        ->call('addEnsemble')
        ->set('ensembles.0.name', 'New Choir')
        ->set('ensembles.0.songs.0.title', 'Amazing Grace')
        ->set('ensembles.0.songs.0.composer', 'John Newton')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('programs.index'));

    $program->refresh();
    $program->load('songTitles.composer');

    expect($program->songTitles)->toHaveCount(1);
    expect($program->songTitles->first()->song_title)->toBe('Amazing Grace');
    expect($program->songTitles->first()->composer->artist_name)->toBe('John Newton');

    $ensemble = Ensemble::where('ensemble_name', 'New Choir')->first();
    expect($ensemble)->not->toBeNull();
});

test('can rename an editable ensemble', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $ensemble = Ensemble::factory()->create(['school_id' => $school->id, 'ensemble_name' => 'Old Name']);
    $song = SongTitle::factory()->create(['song_title' => 'Test Song']);
    $program->songTitles()->attach($song->id, ['ensemble_id' => $ensemble->id, 'sort_order' => 1]);

    $this->actingAs($user);

    Livewire::test(Edit::class, ['program' => $program])
        ->set('ensembles.0.name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    $ensemble->refresh();
    expect($ensemble->ensemble_name)->toBe('New Name');
});

test('renaming ensemble to existing name reuses existing ensemble', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $chorus = Ensemble::factory()->create(['school_id' => $school->id, 'ensemble_name' => 'Chorus']);
    $chorus2 = Ensemble::factory()->create(['school_id' => $school->id, 'ensemble_name' => 'Chorus 2']);

    $song = SongTitle::factory()->create(['song_title' => 'Test Song']);
    $program->songTitles()->attach($song->id, ['ensemble_id' => $chorus2->id, 'sort_order' => 1]);

    $this->actingAs($user);

    Livewire::test(Edit::class, ['program' => $program])
        ->set('ensembles.0.name', 'Chorus')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('programs.index'));

    $program->refresh();
    $program->load('songTitles');

    // Song should now be linked to the existing "Chorus" ensemble
    expect($program->songTitles->first()->pivot->ensemble_id)->toBe($chorus->id);
});

test('sort_order is assigned sequentially per ensemble when saving', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Edit::class, ['program' => $program])
        ->call('addEnsemble')
        ->set('ensembles.0.name', 'Concert Choir')
        ->set('ensembles.0.songs.0.title', 'First Song')
        ->call('addSong', 0)
        ->set('ensembles.0.songs.1.title', 'Second Song')
        ->call('addSong', 0)
        ->set('ensembles.0.songs.2.title', 'Third Song')
        ->call('save')
        ->assertHasNoErrors();

    $program->refresh();
    $songs = $program->songTitles;

    expect($songs)->toHaveCount(3);
    expect($songs[0]->song_title)->toBe('First Song');
    expect($songs[0]->pivot->sort_order)->toBe(1);
    expect($songs[1]->song_title)->toBe('Second Song');
    expect($songs[1]->pivot->sort_order)->toBe(2);
    expect($songs[2]->song_title)->toBe('Third Song');
    expect($songs[2]->pivot->sort_order)->toBe(3);
});

test('sort_order resets per ensemble within a program', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Edit::class, ['program' => $program])
        ->call('addEnsemble')
        ->set('ensembles.0.name', 'Concert Choir')
        ->set('ensembles.0.songs.0.title', 'Choir Song A')
        ->call('addSong', 0)
        ->set('ensembles.0.songs.1.title', 'Choir Song B')
        ->call('addEnsemble')
        ->set('ensembles.1.name', 'Jazz Choir')
        ->set('ensembles.1.songs.0.title', 'Jazz Song A')
        ->call('addSong', 1)
        ->set('ensembles.1.songs.1.title', 'Jazz Song B')
        ->call('save')
        ->assertHasNoErrors();

    $program->refresh();
    $songs = $program->songTitles;

    // Concert Choir songs: sort_order 1, 2
    $choirSongs = $songs->filter(fn ($s) => str_starts_with($s->song_title, 'Choir'));
    expect($choirSongs->pluck('pivot.sort_order')->sort()->values()->all())->toBe([1, 2]);

    // Jazz Choir songs: sort_order 1, 2 (resets per ensemble)
    $jazzSongs = $songs->filter(fn ($s) => str_starts_with($s->song_title, 'Jazz'));
    expect($jazzSongs->pluck('pivot.sort_order')->sort()->values()->all())->toBe([1, 2]);
});
