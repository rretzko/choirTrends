<?php

use App\Enums\VideoVisibility;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake();
});

// --- Program Video (auth routes) ---

test('owner can view their program video', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->withVideo()->create();

    Storage::put($program->video_path, 'fake-video-content');

    $this->actingAs($user)
        ->get(route('videos.program', $program))
        ->assertOk();
});

test('authenticated user can view public program video', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($owner)->for($school)
        ->withVideo(VideoVisibility::Public)->create();

    Storage::put($program->video_path, 'fake-video-content');

    $this->actingAs($viewer)
        ->get(route('videos.program', $program))
        ->assertOk();
});

test('authenticated user cannot view private program video of another user', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($owner)->for($school)
        ->withVideo(VideoVisibility::Private)->create();

    Storage::put($program->video_path, 'fake-video-content');

    $this->actingAs($viewer)
        ->get(route('videos.program', $program))
        ->assertForbidden();
});

test('returns 404 when program has no video', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $this->actingAs($user)
        ->get(route('videos.program', $program))
        ->assertNotFound();
});

test('guest cannot access program video route', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->withVideo()->create();

    $this->get(route('videos.program', $program))
        ->assertRedirect(route('login'));
});

// --- Song Video (auth routes) ---

test('owner can view their song video', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $song = SongTitle::factory()->create();

    $videoPath = 'mp4s/songs/test-song.mp4';
    Storage::put($videoPath, 'fake-video-content');

    $program->songTitles()->attach($song->id, [
        'sort_order' => 1,
        'video_path' => $videoPath,
        'video_visibility' => 'Private',
        'video_uploaded_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('videos.song', [$program, $song->id]))
        ->assertOk();
});

test('authenticated user can view public song video', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($owner)->for($school)->create();
    $song = SongTitle::factory()->create();

    $videoPath = 'mp4s/songs/test-song.mp4';
    Storage::put($videoPath, 'fake-video-content');

    $program->songTitles()->attach($song->id, [
        'sort_order' => 1,
        'video_path' => $videoPath,
        'video_visibility' => 'Public',
        'video_uploaded_at' => now(),
    ]);

    $this->actingAs($viewer)
        ->get(route('videos.song', [$program, $song->id]))
        ->assertOk();
});

test('authenticated user cannot view private song video of another user', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($owner)->for($school)->create();
    $song = SongTitle::factory()->create();

    $videoPath = 'mp4s/songs/test-song.mp4';
    Storage::put($videoPath, 'fake-video-content');

    $program->songTitles()->attach($song->id, [
        'sort_order' => 1,
        'video_path' => $videoPath,
        'video_visibility' => 'Private',
        'video_uploaded_at' => now(),
    ]);

    $this->actingAs($viewer)
        ->get(route('videos.song', [$program, $song->id]))
        ->assertForbidden();
});

test('returns 404 for song without video', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $song = SongTitle::factory()->create();

    $program->songTitles()->attach($song->id, ['sort_order' => 1]);

    $this->actingAs($user)
        ->get(route('videos.song', [$program, $song->id]))
        ->assertNotFound();
});

// --- Catalog Program Video ---

test('catalog guest can view program video via valid token', function () {
    $user = User::factory()->withCatalog()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->withVideo()->create();

    Storage::put($program->video_path, 'fake-video-content');

    $this->get(route('catalog.videos.program', [$user->catalog_token, $program]))
        ->assertOk();
});

test('catalog returns 404 with invalid token for program video', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->withVideo()->create();

    $this->get(route('catalog.videos.program', ['invalid-token', $program]))
        ->assertNotFound();
});

test('catalog returns 404 for program not owned by catalog user', function () {
    $catalogUser = User::factory()->withCatalog()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($otherUser)->for($school)->withVideo()->create();

    $this->get(route('catalog.videos.program', [$catalogUser->catalog_token, $program]))
        ->assertNotFound();
});

// --- Catalog Song Video ---

test('catalog guest can view song video via valid token', function () {
    $user = User::factory()->withCatalog()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $song = SongTitle::factory()->create();

    $videoPath = 'mp4s/songs/test-song.mp4';
    Storage::put($videoPath, 'fake-video-content');

    $program->songTitles()->attach($song->id, [
        'sort_order' => 1,
        'video_path' => $videoPath,
        'video_visibility' => 'Private',
        'video_uploaded_at' => now(),
    ]);

    $this->get(route('catalog.videos.song', [$user->catalog_token, $program, $song->id]))
        ->assertOk();
});

test('catalog returns 404 with invalid token for song video', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $song = SongTitle::factory()->create();

    $program->songTitles()->attach($song->id, [
        'sort_order' => 1,
        'video_path' => 'mp4s/songs/test.mp4',
        'video_visibility' => 'Private',
        'video_uploaded_at' => now(),
    ]);

    $this->get(route('catalog.videos.song', ['invalid-token', $program, $song->id]))
        ->assertNotFound();
});
