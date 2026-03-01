<?php

use App\Enums\VideoVisibility;
use App\Livewire\Programs\Edit;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake();
});

test('can upload a program video', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    $file = UploadedFile::fake()->create('concert.mp4', 5000, 'video/mp4');

    Livewire::test(Edit::class, ['program' => $program])
        ->set('programVideo', $file)
        ->assertSet('hasProgramVideo', true);

    $program->refresh();
    expect($program->video_path)->not->toBeNull();
    expect($program->video_visibility)->toBe(VideoVisibility::Private);
    expect($program->video_uploaded_at)->not->toBeNull();

    Storage::assertExists($program->video_path);
});

test('can remove a program video', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->withVideo()->create();

    Storage::put($program->video_path, 'fake-video-content');

    $this->actingAs($user);

    Livewire::test(Edit::class, ['program' => $program])
        ->assertSet('hasProgramVideo', true)
        ->call('removeProgramVideo')
        ->assertSet('hasProgramVideo', false);

    $program->refresh();
    expect($program->video_path)->toBeNull();
});

test('can toggle program video visibility', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->withVideo(VideoVisibility::Private)->create();

    $this->actingAs($user);

    Livewire::test(Edit::class, ['program' => $program])
        ->assertSet('programVideoVisibility', 'Private')
        ->call('toggleProgramVideoVisibility')
        ->assertSet('programVideoVisibility', 'Public');

    $program->refresh();
    expect($program->video_visibility)->toBe(VideoVisibility::Public);
});

test('program video rejects invalid file types', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user);

    $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

    Livewire::test(Edit::class, ['program' => $program])
        ->set('programVideo', $file)
        ->assertHasErrors('programVideo');
});

test('mount loads existing program video state', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->withVideo(VideoVisibility::Public)->create();

    $this->actingAs($user);

    Livewire::test(Edit::class, ['program' => $program])
        ->assertSet('hasProgramVideo', true)
        ->assertSet('programVideoVisibility', 'Public');
});

test('song video data is preserved during save', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $ensemble = Ensemble::factory()->create(['school_id' => $school->id]);
    $song = SongTitle::factory()->create(['song_title' => 'Test Song']);

    $videoPath = 'mp4s/songs/existing-video.mp4';
    Storage::put($videoPath, 'fake-video-content');

    $program->songTitles()->attach($song->id, [
        'ensemble_id' => $ensemble->id,
        'sort_order' => 1,
        'video_path' => $videoPath,
        'video_visibility' => 'Private',
        'video_uploaded_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(Edit::class, ['program' => $program])
        ->call('save')
        ->assertHasNoErrors();

    $program->refresh();
    $pivot = $program->songTitles()->first()?->pivot;
    expect($pivot->video_path)->toBe($videoPath);
    expect($pivot->video_visibility)->toBe('Private');
});

test('can upload a song video for existing song', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create(['song_title' => 'Test Song']);
    $program->songTitles()->attach($song->id, ['sort_order' => 1]);

    $this->actingAs($user);

    $file = UploadedFile::fake()->create('song-video.mp4', 3000, 'video/mp4');

    Livewire::test(Edit::class, ['program' => $program])
        ->set('songVideos.0-0', $file)
        ->call('uploadSongVideo', 0, 0);

    $program->refresh();
    $pivot = $program->songTitles()->first()?->pivot;
    expect($pivot->video_path)->not->toBeNull();

    Storage::assertExists($pivot->video_path);
});

test('can remove a song video', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create(['song_title' => 'Test Song']);

    $videoPath = 'mp4s/songs/to-remove.mp4';
    Storage::put($videoPath, 'fake-video-content');

    $program->songTitles()->attach($song->id, [
        'sort_order' => 1,
        'video_path' => $videoPath,
        'video_visibility' => 'Private',
        'video_uploaded_at' => now(),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Edit::class, ['program' => $program]);
    $ensembles = $component->get('ensembles');
    expect($ensembles[0]['songs'][0]['videoPath'])->toBe($videoPath);

    $component->call('removeSongVideo', 0, 0);

    $ensembles = $component->get('ensembles');
    expect($ensembles[0]['songs'][0]['videoPath'])->toBeNull();

    $program->refresh();
    $pivot = $program->songTitles()->first()?->pivot;
    expect($pivot->video_path)->toBeNull();
});

test('can toggle song video visibility', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create(['song_title' => 'Test Song']);

    $program->songTitles()->attach($song->id, [
        'sort_order' => 1,
        'video_path' => 'mp4s/songs/test.mp4',
        'video_visibility' => 'Private',
        'video_uploaded_at' => now(),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Edit::class, ['program' => $program])
        ->call('toggleSongVideoVisibility', 0, 0);

    $ensembles = $component->get('ensembles');
    expect($ensembles[0]['songs'][0]['videoVisibility'])->toBe('Public');

    $program->refresh();
    $pivot = $program->songTitles()->first()?->pivot;
    expect($pivot->video_visibility)->toBe('Public');
});

test('can upload an audio file for existing song', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create(['song_title' => 'Audio Song']);
    $program->songTitles()->attach($song->id, ['sort_order' => 1]);

    $this->actingAs($user);

    $file = UploadedFile::fake()->create('recording.mp3', 2000, 'audio/mpeg');

    Livewire::test(Edit::class, ['program' => $program])
        ->set('songVideos.0-0', $file)
        ->call('uploadSongVideo', 0, 0);

    $program->refresh();
    $pivot = $program->songTitles()->first()?->pivot;
    expect($pivot->video_path)->not->toBeNull();

    Storage::assertExists($pivot->video_path);
});

test('non-owner cannot access program edit with video features', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($owner)->for($school)->withVideo()->create();

    $this->actingAs($otherUser);

    $this->get(route('programs.edit', $program))
        ->assertForbidden();
});
