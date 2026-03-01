<?php

use App\Livewire\SongTitles\Index;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use Livewire\Livewire;

test('video icon shows for own song with video', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $song = SongTitle::factory()->create(['song_title' => 'My Video Song']);

    $program->songTitles()->attach($song->id, [
        'sort_order' => 1,
        'video_path' => 'mp4s/songs/test.mp4',
        'video_visibility' => 'Private',
        'video_uploaded_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSeeHtml('Watch Video');
});

test('video icon shows for public video from another user', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $school = School::factory()->create();

    // Viewer needs a program to see "all" data
    Program::factory()->for($viewer)->for($school)->create();

    $program = Program::factory()->for($owner)->for($school)->create();
    $song = SongTitle::factory()->create(['song_title' => 'Public Video Song']);

    $program->songTitles()->attach($song->id, [
        'sort_order' => 1,
        'video_path' => 'mp4s/songs/public.mp4',
        'video_visibility' => 'Public',
        'video_uploaded_at' => now(),
    ]);

    $this->actingAs($viewer);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertSeeHtml('Watch Video');
});

test('video icon hidden for private video from another user', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $school = School::factory()->create();

    // Viewer needs a program to see "all" data
    Program::factory()->for($viewer)->for($school)->create();

    $program = Program::factory()->for($owner)->for($school)->create();
    $song = SongTitle::factory()->create(['song_title' => 'Private Video Song']);

    $program->songTitles()->attach($song->id, [
        'sort_order' => 1,
        'video_path' => 'mp4s/songs/private.mp4',
        'video_visibility' => 'Private',
        'video_uploaded_at' => now(),
    ]);

    $this->actingAs($viewer);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertDontSeeHtml('Watch Video');
});

test('playVideo sets modal properties correctly', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $song = SongTitle::factory()->create(['song_title' => 'Modal Test Song']);

    $program->songTitles()->attach($song->id, [
        'sort_order' => 1,
        'video_path' => 'mp4s/songs/modal-test.mp4',
        'video_visibility' => 'Private',
        'video_uploaded_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('playVideo', $song->id, $program->id, 'Modal Test Song')
        ->assertSet('videoSongTitleId', $song->id)
        ->assertSet('videoProgramId', $program->id)
        ->assertSet('videoSongName', 'Modal Test Song');
});

test('audio file shows Listen title instead of Watch Video', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $song = SongTitle::factory()->create(['song_title' => 'Audio Song']);

    $program->songTitles()->attach($song->id, [
        'sort_order' => 1,
        'video_path' => 'mp4s/songs/recording.mp3',
        'video_visibility' => 'Private',
        'video_uploaded_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSeeHtml('Listen')
        ->assertDontSeeHtml('Watch Video');
});

test('playVideo sets isAudio for audio files', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('playVideo', 1, 1, 'Audio Song', true)
        ->assertSet('isAudio', true);
});

test('no video icon when song has no video', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $song = SongTitle::factory()->create(['song_title' => 'No Video Song']);

    $program->songTitles()->attach($song->id, ['sort_order' => 1]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertDontSeeHtml('Watch Video');
});
