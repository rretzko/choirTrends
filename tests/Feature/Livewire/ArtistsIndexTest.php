<?php

use App\Livewire\Artists\Index;
use App\Models\Artist;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use Livewire\Livewire;

test('artists index page can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('artists.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

test('artists index displays all artists', function () {
    $user = User::factory()->create();
    $artists = Artist::factory()->count(3)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee($artists[0]->artist_name)
        ->assertSee($artists[1]->artist_name)
        ->assertSee($artists[2]->artist_name)
        ->assertStatus(200);
});

test('artists index displays repertoire count', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $composer = Artist::factory()->create(['artist_name' => 'Bach']);
    $arranger = Artist::factory()->create(['artist_name' => 'Rutter']);

    $song1 = SongTitle::factory()->create(['composer_id' => $composer->id, 'arranger_id' => $arranger->id]);
    $song2 = SongTitle::factory()->create(['composer_id' => $composer->id, 'arranger_id' => null]);

    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);
    $program->songTitles()->attach([$song1->id, $song2->id]);

    $this->actingAs($user);

    // Bach composed both songs (rep count 2), Rutter arranged one (rep count 1)
    Livewire::test(Index::class)
        ->assertSeeInOrder(['Bach', '2'])
        ->assertSee('Rutter');
});

test('artists index can sort by repertoire count', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $prolific = Artist::factory()->create(['artist_name' => 'Prolific Composer']);
    $single = Artist::factory()->create(['artist_name' => 'Single Composer']);

    $songs = SongTitle::factory()->count(3)->create(['composer_id' => $prolific->id]);
    $singleSong = SongTitle::factory()->create(['composer_id' => $single->id]);

    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);
    $program->songTitles()->attach($songs->pluck('id')->merge([$singleSong->id]));

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('sort', 'repertoire_count')
        ->assertStatus(200);
});

test('showRepertoire loads song titles for the artist', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $composer = Artist::factory()->create(['artist_name' => 'Mozart']);
    $arranger = Artist::factory()->create(['artist_name' => 'Lauridsen']);

    $composedSong = SongTitle::factory()->create([
        'song_title' => 'Ave Verum Corpus',
        'composer_id' => $composer->id,
        'arranger_id' => $arranger->id,
    ]);
    $arrangedSong = SongTitle::factory()->create([
        'song_title' => 'O Magnum Mysterium',
        'composer_id' => $arranger->id,
        'arranger_id' => null,
    ]);
    $unrelatedSong = SongTitle::factory()->create([
        'song_title' => 'Unrelated Piece',
        'composer_id' => Artist::factory()->create()->id,
    ]);

    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);
    $program->songTitles()->attach([$composedSong->id, $arrangedSong->id, $unrelatedSong->id]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('showRepertoire', $composer->id)
        ->assertSet('selectedArtist.id', $composer->id)
        ->assertCount('repertoire', 1)
        ->assertSee('Ave Verum Corpus')
        ->assertSee('Composer')
        ->assertDontSee('Unrelated Piece');
});

test('showRepertoire shows both roles when artist is composer and arranger', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $artist = Artist::factory()->create(['artist_name' => 'Whitacre']);

    $song = SongTitle::factory()->create([
        'song_title' => 'Sleep',
        'composer_id' => $artist->id,
        'arranger_id' => $artist->id,
    ]);

    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);
    $program->songTitles()->attach([$song->id]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('showRepertoire', $artist->id)
        ->assertSee('Sleep')
        ->assertSee('Composer & Arranger');
});

test('artists index can search by artist name', function () {
    $user = User::factory()->create();
    Artist::factory()->create(['artist_name' => 'Johann Sebastian Bach']);
    Artist::factory()->create(['artist_name' => 'Wolfgang Amadeus Mozart']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('search', 'Bach')
        ->assertSee('Johann Sebastian Bach')
        ->assertDontSee('Wolfgang Amadeus Mozart');
});

test('artists index search is case insensitive', function () {
    $user = User::factory()->create();
    Artist::factory()->create(['artist_name' => 'Johann Sebastian Bach']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('search', 'bach')
        ->assertSee('Johann Sebastian Bach');
});

test('artists index clears search results when search is emptied', function () {
    $user = User::factory()->create();
    Artist::factory()->create(['artist_name' => 'Johann Sebastian Bach']);
    Artist::factory()->create(['artist_name' => 'Wolfgang Amadeus Mozart']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('search', 'Bach')
        ->assertDontSee('Wolfgang Amadeus Mozart')
        ->set('search', '')
        ->assertSee('Johann Sebastian Bach')
        ->assertSee('Wolfgang Amadeus Mozart');
});

test('guests cannot access artists index', function () {
    $this->get(route('artists.index'))
        ->assertRedirect(route('login'));
});
