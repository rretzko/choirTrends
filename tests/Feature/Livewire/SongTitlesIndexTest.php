<?php

use App\Livewire\SongTitles\Index;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use Livewire\Livewire;

test('song titles index page can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('song-titles.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

test('song titles index displays all song titles that have programs', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $songTitles = SongTitle::factory()->count(3)->create();
    $program->songTitles()->attach($songTitles->pluck('id'));

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee($songTitles[0]->song_title)
        ->assertSee($songTitles[1]->song_title)
        ->assertSee($songTitles[2]->song_title)
        ->assertStatus(200);
});

test('guests cannot access song titles index', function () {
    $this->get(route('song-titles.index'))
        ->assertRedirect(route('login'));
});

test('song titles index displays only user songs with my filter', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create();

    // Create a program for the user
    $userProgram = Program::factory()->create([
        'user_id' => $user->id,
        'school_id' => $school->id,
    ]);

    // Create a program for another user
    $otherProgram = Program::factory()->create([
        'user_id' => $otherUser->id,
        'school_id' => $school->id,
    ]);

    // Create song titles and attach to programs
    $userSong = SongTitle::factory()->create(['song_title' => 'User Song']);
    $otherSong = SongTitle::factory()->create(['song_title' => 'Other Song']);

    $userProgram->songTitles()->attach($userSong->id);
    $otherProgram->songTitles()->attach($otherSong->id);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'my')
        ->assertSee('User Song')
        ->assertDontSee('Other Song');
});

test('song titles index displays correct counts', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();

    // Create a program for the user
    $userProgram = Program::factory()->create([
        'user_id' => $user->id,
        'school_id' => $school->id,
    ]);

    // Create song titles - 2 attached to user's program, 1 not attached
    $userSong1 = SongTitle::factory()->create();
    $userSong2 = SongTitle::factory()->create();
    $unattachedSong = SongTitle::factory()->create();

    $userProgram->songTitles()->attach([$userSong1->id, $userSong2->id]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSet('myCount', 2)
        ->assertSet('allCount', 2);
});

test('song titles without programs are excluded from the index', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $attachedSong = SongTitle::factory()->create(['song_title' => 'Attached Song']);
    $orphanedSong = SongTitle::factory()->create(['song_title' => 'Orphaned Song']);

    $program->songTitles()->attach($attachedSong->id);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('Attached Song')
        ->assertDontSee('Orphaned Song');
});
