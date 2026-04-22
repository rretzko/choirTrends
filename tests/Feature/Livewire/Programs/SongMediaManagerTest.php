<?php

declare(strict_types=1);

use App\Livewire\Programs\SongMediaManager;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use App\Models\UserSongFile;
use App\Models\UserSongLyrics;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake();
});

test('open loads existing pivot media state', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create(['song_title' => 'Moon River']);
    $program->songTitles()->attach($song->id, [
        'sort_order' => 1,
        'video_path' => 'mp4s/songs/moon.mp4',
        'video_visibility' => 'Public',
        'video_uploaded_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->assertSet('songTitleId', $song->id)
        ->assertSet('songTitleLabel', 'Moon River')
        ->assertSet('videoPath', 'mp4s/songs/moon.mp4')
        ->assertSet('videoVisibility', 'Public');
});

test('open aborts for a non-owner', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($owner)->for($school)->create();

    $song = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id, ['sort_order' => 1]);

    $this->actingAs($stranger);

    Livewire::test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->assertForbidden();
});

test('uploading an audio file stores file and updates pivot', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id, ['sort_order' => 1]);

    $this->actingAs($user);

    $file = UploadedFile::fake()->create('take.mp3', 2000, 'audio/mpeg');

    Livewire::test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->set('audioVideoUpload', $file)
        ->assertDispatched('song-media-updated', songTitleId: $song->id);

    $program->refresh();
    $pivot = $program->songTitles()->first()?->pivot;
    expect($pivot->video_path)->not->toBeNull();
    expect($pivot->video_visibility)->toBe('Private');

    Storage::assertExists($pivot->video_path);
});

test('uploading a video file stores file and updates pivot', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id, ['sort_order' => 1]);

    $this->actingAs($user);

    $file = UploadedFile::fake()->create('take.mp4', 5000, 'video/mp4');

    Livewire::test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->set('audioVideoUpload', $file);

    $program->refresh();
    $pivot = $program->songTitles()->first()?->pivot;
    expect($pivot->video_path)->not->toBeNull();

    Storage::assertExists($pivot->video_path);
});

test('upload rejects invalid file types', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id, ['sort_order' => 1]);

    $this->actingAs($user);

    $file = UploadedFile::fake()->create('bad.pdf', 1000, 'application/pdf');

    Livewire::test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->set('audioVideoUpload', $file)
        ->assertHasErrors('audioVideoUpload');
});

test('removing clears pivot media and deletes file', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create();

    $videoPath = 'mp4s/songs/existing.mp4';
    Storage::put($videoPath, 'fake-content');

    $program->songTitles()->attach($song->id, [
        'sort_order' => 1,
        'video_path' => $videoPath,
        'video_visibility' => 'Private',
        'video_uploaded_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->call('removeAudioVideo')
        ->assertSet('videoPath', null)
        ->assertDispatched('song-media-updated');

    $program->refresh();
    $pivot = $program->songTitles()->first()?->pivot;
    expect($pivot->video_path)->toBeNull();

    Storage::assertMissing($videoPath);
});

test('open loads existing lyrics when present', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id, ['sort_order' => 1]);

    UserSongLyrics::factory()->create([
        'user_id' => $user->id,
        'song_title_id' => $song->id,
        'content' => 'Swing low, sweet chariot',
    ]);

    $this->actingAs($user);

    Livewire::test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->assertSet('lyrics', 'Swing low, sweet chariot')
        ->assertSet('hasLyrics', true);
});

test('saving lyrics creates a new record scoped to user and song', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id, ['sort_order' => 1]);

    $this->actingAs($user);

    Livewire::test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->set('lyrics', 'Row, row, row your boat')
        ->call('saveLyrics')
        ->assertHasNoErrors()
        ->assertSet('hasLyrics', true)
        ->assertDispatched('song-media-updated', songTitleId: $song->id);

    expect(UserSongLyrics::where('user_id', $user->id)->where('song_title_id', $song->id)->value('content'))
        ->toBe('Row, row, row your boat');
});

test('saving lyrics updates existing record', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id, ['sort_order' => 1]);

    UserSongLyrics::factory()->create([
        'user_id' => $user->id,
        'song_title_id' => $song->id,
        'content' => 'old content',
    ]);

    $this->actingAs($user);

    Livewire::test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->set('lyrics', 'new content')
        ->call('saveLyrics')
        ->assertHasNoErrors();

    expect(UserSongLyrics::where('user_id', $user->id)->where('song_title_id', $song->id)->count())->toBe(1);
    expect(UserSongLyrics::where('user_id', $user->id)->where('song_title_id', $song->id)->value('content'))->toBe('new content');
});

test('saving empty lyrics fails validation', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id, ['sort_order' => 1]);

    $this->actingAs($user);

    Livewire::test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->set('lyrics', '')
        ->call('saveLyrics')
        ->assertHasErrors(['lyrics' => 'required']);
});

test('deleting lyrics removes the record', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id, ['sort_order' => 1]);

    UserSongLyrics::factory()->create([
        'user_id' => $user->id,
        'song_title_id' => $song->id,
        'content' => 'to be removed',
    ]);

    $this->actingAs($user);

    Livewire::test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->assertSet('hasLyrics', true)
        ->call('deleteLyrics')
        ->assertSet('hasLyrics', false)
        ->assertSet('lyrics', '')
        ->assertDispatched('song-media-updated', songTitleId: $song->id);

    expect(UserSongLyrics::where('user_id', $user->id)->where('song_title_id', $song->id)->exists())->toBeFalse();
});

test('uploading sheet music creates a UserSongFile and stores the file', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id, ['sort_order' => 1]);

    $this->actingAs($user);

    $file = UploadedFile::fake()->create('soprano.pdf', 500, 'application/pdf');

    Livewire::test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->set('sheetMusicUpload', $file)
        ->assertHasNoErrors()
        ->assertDispatched('song-media-updated', songTitleId: $song->id);

    $record = UserSongFile::where('user_id', $user->id)->where('song_title_id', $song->id)->first();
    expect($record)->not->toBeNull();
    expect($record->original_filename)->toBe('soprano.pdf');
    expect($record->type)->toBe('sheet_music');

    Storage::assertExists($record->file_path);
});

test('sheet music upload rejects non-allowed file types', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id, ['sort_order' => 1]);

    $this->actingAs($user);

    $file = UploadedFile::fake()->create('evil.exe', 10, 'application/octet-stream');

    Livewire::test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->set('sheetMusicUpload', $file)
        ->assertHasErrors('sheetMusicUpload');
});

test('deleting a sheet music file removes record and storage file', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id, ['sort_order' => 1]);

    $filePath = "sheet-music/{$user->id}/{$song->id}/existing.pdf";
    Storage::put($filePath, 'fake-pdf-content');

    $record = UserSongFile::factory()->create([
        'user_id' => $user->id,
        'song_title_id' => $song->id,
        'file_path' => $filePath,
        'original_filename' => 'existing.pdf',
    ]);

    $this->actingAs($user);

    Livewire::test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->call('deleteSheetMusic', $record->id)
        ->assertDispatched('song-media-updated', songTitleId: $song->id);

    expect(UserSongFile::find($record->id))->toBeNull();
    Storage::assertMissing($filePath);
});

test('deleting another users sheet music file is a no-op', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($stranger)->for($school)->create();

    $song = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id, ['sort_order' => 1]);

    $record = UserSongFile::factory()->create([
        'user_id' => $owner->id,
        'song_title_id' => $song->id,
    ]);

    $this->actingAs($stranger);

    Livewire::test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->call('deleteSheetMusic', $record->id);

    expect(UserSongFile::find($record->id))->not->toBeNull();
});

test('open lists only owner sheet music for this song', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create();
    $otherSong = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id, ['sort_order' => 1]);

    $mine = UserSongFile::factory()->create([
        'user_id' => $user->id,
        'song_title_id' => $song->id,
        'original_filename' => 'mine.pdf',
    ]);
    UserSongFile::factory()->create([
        'user_id' => $user->id,
        'song_title_id' => $otherSong->id,
        'original_filename' => 'other-song.pdf',
    ]);
    UserSongFile::factory()->create([
        'user_id' => User::factory()->create()->id,
        'song_title_id' => $song->id,
        'original_filename' => 'other-user.pdf',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id);

    $files = $component->instance()->sheetMusicFiles;
    expect($files->pluck('id')->all())->toBe([$mine->id]);
});

test('toggle visibility flips between private and public on pivot', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $song = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id, [
        'sort_order' => 1,
        'video_path' => 'mp4s/songs/x.mp4',
        'video_visibility' => 'Private',
        'video_uploaded_at' => now(),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->call('toggleAudioVideoVisibility')
        ->assertSet('videoVisibility', 'Public');

    $program->refresh();
    expect($program->songTitles()->first()?->pivot->video_visibility)->toBe('Public');

    $component->call('toggleAudioVideoVisibility')
        ->assertSet('videoVisibility', 'Private');
});
