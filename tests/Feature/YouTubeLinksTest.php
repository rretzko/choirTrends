<?php

declare(strict_types=1);

use App\Enums\VideoVisibility;
use App\Livewire\Catalog\Index as CatalogIndex;
use App\Livewire\Programs\Edit;
use App\Livewire\Programs\SongMediaManager;
use App\Livewire\SongTitles\Index as SongTitlesIndex;
use App\Models\DigitalProgram;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

const YT_LINK = 'https://youtu.be/dQw4w9WgXcQ?si=share';
const YT_NORMALIZED = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
const YT_EMBED = 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0';

beforeEach(function () {
    Storage::fake();
});

/**
 * @param  array<string, mixed>  $pivot
 * @return array{0: User, 1: Program, 2: SongTitle}
 */
function youTubeProgramWithSong(array $pivot = [], ?User $user = null): array
{
    $user ??= User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $ensemble = Ensemble::factory()->create(['school_id' => $school->id]);
    $song = SongTitle::factory()->create(['song_title' => 'Shenandoah']);

    $program->songTitles()->attach($song->id, array_merge([
        'ensemble_id' => $ensemble->id,
        'sort_order' => 1,
        'ensemble_sort_order' => 1,
    ], $pivot));

    return [$user, $program, $song];
}

function youTubeSongPivot(Program $program): object
{
    return $program->songTitles()->first()->pivot;
}

// ─── Song-level: SongMediaManager ─────────────────────────────────────────────

test('owner can add a song YouTube link, which is normalized and private by default', function () {
    [$user, $program, $song] = youTubeProgramWithSong();

    Livewire::actingAs($user)
        ->test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->set('youtubeUrlInput', YT_LINK)
        ->call('saveYouTubeUrl')
        ->assertHasNoErrors()
        ->assertSet('youtubeUrl', YT_NORMALIZED)
        ->assertSet('videoVisibility', 'Private')
        ->assertDispatched('song-media-updated', songTitleId: $song->id);

    $pivot = youTubeSongPivot($program);
    expect($pivot->youtube_url)->toBe(YT_NORMALIZED)
        ->and($pivot->video_visibility)->toBe('Private');
});

test('open loads an existing song YouTube link', function () {
    [$user, $program, $song] = youTubeProgramWithSong(['youtube_url' => YT_NORMALIZED, 'video_visibility' => 'Public']);

    Livewire::actingAs($user)
        ->test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->assertSet('youtubeUrl', YT_NORMALIZED)
        ->assertSet('youtubeUrlInput', YT_NORMALIZED)
        ->assertSet('videoVisibility', 'Public')
        ->assertSee('dQw4w9WgXcQ/mqdefault.jpg');
});

test('song YouTube link is validated', function (string $input) {
    [$user, $program, $song] = youTubeProgramWithSong();

    Livewire::actingAs($user)
        ->test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->set('youtubeUrlInput', $input)
        ->call('saveYouTubeUrl')
        ->assertHasErrors('youtubeUrlInput');

    expect(youTubeSongPivot($program)->youtube_url)->toBeNull();
})->with([
    'empty' => '',
    'not youtube' => 'https://vimeo.com/123456',
    'channel page' => 'https://www.youtube.com/@choir',
]);

test('removing a song YouTube link clears it and resets visibility when no file remains', function () {
    [$user, $program, $song] = youTubeProgramWithSong(['youtube_url' => YT_NORMALIZED, 'video_visibility' => 'Public']);

    Livewire::actingAs($user)
        ->test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->call('removeYouTubeUrl')
        ->assertSet('youtubeUrl', null)
        ->assertSet('videoVisibility', 'Private');

    $pivot = youTubeSongPivot($program);
    expect($pivot->youtube_url)->toBeNull()
        ->and($pivot->video_visibility)->toBeNull();
});

test('removing the uploaded file keeps the YouTube link and its visibility', function () {
    Storage::put('mp4s/songs/take.mp4', 'x');
    [$user, $program, $song] = youTubeProgramWithSong([
        'video_path' => 'mp4s/songs/take.mp4',
        'video_visibility' => 'Public',
        'youtube_url' => YT_NORMALIZED,
    ]);

    Livewire::actingAs($user)
        ->test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->call('removeAudioVideo')
        ->assertSet('videoVisibility', 'Public');

    $pivot = youTubeSongPivot($program);
    expect($pivot->video_path)->toBeNull()
        ->and($pivot->youtube_url)->toBe(YT_NORMALIZED)
        ->and($pivot->video_visibility)->toBe('Public');
});

test('uploading a file next to a public YouTube link keeps the shared visibility', function () {
    [$user, $program, $song] = youTubeProgramWithSong(['youtube_url' => YT_NORMALIZED, 'video_visibility' => 'Public']);

    Livewire::actingAs($user)
        ->test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->set('audioVideoUpload', UploadedFile::fake()->create('take.mp3', 100, 'audio/mpeg'));

    $pivot = youTubeSongPivot($program);
    expect($pivot->video_path)->not->toBeNull()
        ->and($pivot->video_visibility)->toBe('Public');
});

test('visibility can be toggled when only a YouTube link exists', function () {
    [$user, $program, $song] = youTubeProgramWithSong(['youtube_url' => YT_NORMALIZED, 'video_visibility' => 'Private']);

    Livewire::actingAs($user)
        ->test(SongMediaManager::class, ['program' => $program])
        ->call('open', $song->id)
        ->call('toggleAudioVideoVisibility')
        ->assertSet('videoVisibility', 'Public');

    expect(youTubeSongPivot($program)->video_visibility)->toBe('Public');
});

// ─── Programs\Edit ─────────────────────────────────────────────────────────────

test('saving the program preserves song YouTube links and visibility', function () {
    [$user, $program] = youTubeProgramWithSong(['youtube_url' => YT_NORMALIZED, 'video_visibility' => 'Public']);

    Livewire::actingAs($user)
        ->test(Edit::class, ['program' => $program])
        ->call('save')
        ->assertHasNoErrors();

    $pivot = youTubeSongPivot($program);
    expect($pivot->youtube_url)->toBe(YT_NORMALIZED)
        ->and($pivot->video_visibility)->toBe('Public');
});

test('edit picks up a YouTube link added in the media manager before saving', function () {
    [$user, $program, $song] = youTubeProgramWithSong();

    $component = Livewire::actingAs($user)->test(Edit::class, ['program' => $program]);

    $program->songTitles()->updateExistingPivot($song->id, ['youtube_url' => YT_NORMALIZED, 'video_visibility' => 'Private']);

    $component->dispatch('song-media-updated', songTitleId: $song->id)
        ->assertSet('ensembles.0.songs.0.youtubeUrl', YT_NORMALIZED)
        ->call('save');

    expect(youTubeSongPivot($program)->youtube_url)->toBe(YT_NORMALIZED);
});

test('owner can add, toggle and remove a program-level YouTube link', function () {
    $user = User::factory()->create();
    $program = Program::factory()->for($user)->for(School::factory())->create();

    $component = Livewire::actingAs($user)
        ->test(Edit::class, ['program' => $program])
        ->set('programYoutubeUrlInput', 'https://www.youtube.com/live/dQw4w9WgXcQ?feature=share')
        ->call('saveProgramYouTubeUrl')
        ->assertHasNoErrors()
        ->assertSet('programYoutubeUrl', YT_NORMALIZED);

    $program->refresh();
    expect($program->youtube_url)->toBe(YT_NORMALIZED)
        ->and($program->video_visibility)->toBe(VideoVisibility::Private);

    $component->call('toggleProgramVideoVisibility')->assertSet('programVideoVisibility', 'Public');
    expect($program->refresh()->video_visibility)->toBe(VideoVisibility::Public);

    $component->call('removeProgramYouTubeUrl')
        ->assertSet('programYoutubeUrl', null)
        ->assertSet('programVideoVisibility', 'Private');

    $program->refresh();
    expect($program->youtube_url)->toBeNull()
        ->and($program->video_visibility)->toBeNull();
});

test('program-level YouTube link is validated', function () {
    $user = User::factory()->create();
    $program = Program::factory()->for($user)->for(School::factory())->create();

    Livewire::actingAs($user)
        ->test(Edit::class, ['program' => $program])
        ->set('programYoutubeUrlInput', 'https://example.com/video')
        ->call('saveProgramYouTubeUrl')
        ->assertHasErrors('programYoutubeUrlInput');

    expect($program->refresh()->youtube_url)->toBeNull();
});

test('removing the program video file keeps the program YouTube link visibility', function () {
    $user = User::factory()->create();
    $program = Program::factory()->for($user)->for(School::factory())->withVideo(VideoVisibility::Public)->create([
        'youtube_url' => YT_NORMALIZED,
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['program' => $program])
        ->call('removeProgramVideo');

    $program->refresh();
    expect($program->video_path)->toBeNull()
        ->and($program->youtube_url)->toBe(YT_NORMALIZED)
        ->and($program->video_visibility)->toBe(VideoVisibility::Public);
});

// ─── Catalog ───────────────────────────────────────────────────────────────────

test('catalog plays song and concert YouTube links', function () {
    $user = User::factory()->withCatalog()->create();
    [, $program, $song] = youTubeProgramWithSong(['youtube_url' => YT_NORMALIZED], $user);
    $program->update(['youtube_url' => 'https://www.youtube.com/watch?v=abcdefghijk']);

    Livewire::test(CatalogIndex::class, ['token' => $user->catalog_token])
        ->call('showProgramDetails', $program->id)
        ->assertSee('Watch on YouTube')
        ->call('playSongYouTube', $song->id, 'Shenandoah')
        ->assertSet('youtubeEmbedUrl', YT_EMBED)
        ->assertSeeHtml(YT_EMBED)
        ->call('playConcertYouTube')
        ->assertSet('youtubeEmbedUrl', 'https://www.youtube-nocookie.com/embed/abcdefghijk?rel=0')
        ->call('playSongVideo', $song->id, 'Shenandoah')
        ->assertSet('youtubeEmbedUrl', null);
});

test('catalog ignores song YouTube requests for songs without a link', function () {
    $user = User::factory()->withCatalog()->create();
    [, $program, $song] = youTubeProgramWithSong([], $user);

    Livewire::test(CatalogIndex::class, ['token' => $user->catalog_token])
        ->call('showProgramDetails', $program->id)
        ->call('playSongYouTube', $song->id, 'Shenandoah')
        ->assertSet('youtubeEmbedUrl', null);
});

// ─── Song Titles index ─────────────────────────────────────────────────────────

test('song titles shows the YouTube button for own private link', function () {
    [$user, $program, $song] = youTubeProgramWithSong(['youtube_url' => YT_NORMALIZED, 'video_visibility' => 'Private']);

    Livewire::actingAs($user)
        ->test(SongTitlesIndex::class)
        ->assertSeeHtml('Watch performance on YouTube')
        ->assertDontSeeHtml('Watch Video')
        ->call('playYouTube', $song->id, $program->id, 'Shenandoah')
        ->assertSet('youtubeEmbedUrl', YT_EMBED);
});

test('song titles shows public YouTube links from other directors but hides private ones', function (string $visibility, bool $visible) {
    $viewer = User::factory()->create();
    Program::factory()->for($viewer)->for(School::factory())->create();
    [, $program, $song] = youTubeProgramWithSong(['youtube_url' => YT_NORMALIZED, 'video_visibility' => $visibility]);

    $component = Livewire::actingAs($viewer)
        ->test(SongTitlesIndex::class)
        ->set('filter', 'all')
        ->call('playYouTube', $song->id, $program->id, 'Shenandoah');

    if ($visible) {
        $component->assertSeeHtml('Watch performance on YouTube')->assertSet('youtubeEmbedUrl', YT_EMBED);
    } else {
        $component->assertDontSeeHtml('Watch performance on YouTube')->assertSet('youtubeEmbedUrl', null);
    }
})->with([
    'public' => ['Public', true],
    'private' => ['Private', false],
]);

// ─── Public digital program ───────────────────────────────────────────────────

test('published digital program embeds public YouTube links only', function () {
    $user = User::factory()->create();
    [, $program, $song] = youTubeProgramWithSong(['youtube_url' => YT_NORMALIZED, 'video_visibility' => 'Public'], $user);

    $privateSong = SongTitle::factory()->create(['song_title' => 'Private Piece']);
    $program->songTitles()->attach($privateSong->id, [
        'ensemble_id' => youTubeSongPivot($program)->ensemble_id,
        'sort_order' => 2,
        'ensemble_sort_order' => 1,
        'youtube_url' => 'https://www.youtube.com/watch?v=privatevid1',
        'video_visibility' => 'Private',
    ]);

    $program->update(['youtube_url' => 'https://www.youtube.com/watch?v=concertvid1', 'video_visibility' => VideoVisibility::Public]);

    $dp = DigitalProgram::factory()->for($user)->published()->create(['program_id' => $program->id]);

    $this->get(route('program.public', $dp->slug))
        ->assertOk()
        ->assertSee('Watch the Concert')
        ->assertSee('youtube-nocookie.com/embed/concertvid1', false)
        ->assertSee('Watch performance')
        ->assertSee(YT_EMBED, false)
        ->assertSee('Private Piece')
        ->assertDontSee('privatevid1', false);
});

test('published digital program hides a private concert YouTube link', function () {
    $user = User::factory()->create();
    $program = Program::factory()->for($user)->for(School::factory())->create([
        'youtube_url' => YT_NORMALIZED,
        'video_visibility' => VideoVisibility::Private,
    ]);
    $dp = DigitalProgram::factory()->for($user)->published()->create(['program_id' => $program->id]);

    $this->get(route('program.public', $dp->slug))
        ->assertOk()
        ->assertDontSee('Watch the Concert')
        ->assertDontSee('dQw4w9WgXcQ', false);
});
