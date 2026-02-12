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

test('guests cannot access artists index', function () {
    $this->get(route('artists.index'))
        ->assertRedirect(route('login'));
});
