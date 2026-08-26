<?php

use App\Livewire\SongTitles\Index;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\SongTitleDescription;
use App\Models\SongTitleDifficultyObservation;
use App\Models\SongTitleTag;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Livewire;

/**
 * SongTitleFactory's default `song_title` draws from a fixed 8-title pool via fake()->unique(),
 * which exhausts immediately when a test needs more rows than that — bypass the factory entirely
 * for bulk creation and assign guaranteed-unique titles directly.
 *
 * @return Collection<int, SongTitle>
 */
function createNumberedSongTitles(int $count, string $prefix = 'Song'): Collection
{
    return collect(range(1, $count))->map(
        fn (int $i) => SongTitle::create(['song_title' => "{$prefix} ".str_pad((string) $i, 3, '0', STR_PAD_LEFT)])
    );
}

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

test('browse list paginates at 20 per page and a second page shows the remaining rows', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $songTitles = createNumberedSongTitles(25);
    $program->songTitles()->attach($songTitles->pluck('id'));

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertViewHas('songTitles', fn ($songTitles) => $songTitles->count() === 20)
        ->call('gotoPage', 2)
        ->assertViewHas('songTitles', fn ($songTitles) => $songTitles->count() === 5);
});

test('changing the search term resets to page 1', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $songTitles = createNumberedSongTitles(25);
    $program->songTitles()->attach($songTitles->pluck('id'));

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('gotoPage', 2)
        ->set('search', $songTitles[0]->song_title)
        ->assertViewHas('songTitles', fn ($songTitles) => $songTitles->currentPage() === 1);
});

test('changing the ensemble type filter resets to page 1', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $songTitles = createNumberedSongTitles(25);
    $program->songTitles()->attach($songTitles->pluck('id'));

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('gotoPage', 2)
        ->call('toggleEnsembleTypeFilter', 'Satb')
        ->assertViewHas('songTitles', fn ($songTitles) => $songTitles->currentPage() === 1);
});

test('sorting resets to page 1', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $songTitles = createNumberedSongTitles(25);
    $program->songTitles()->attach($songTitles->pluck('id'));

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('gotoPage', 2)
        ->call('sort', 'performed')
        ->assertViewHas('songTitles', fn ($songTitles) => $songTitles->currentPage() === 1);
});

test('not programmed filter shows songs with no programs', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $programmedSong = SongTitle::factory()->create(['song_title' => 'Programmed Song']);
    $unprogrammedSong = SongTitle::factory()->create(['song_title' => 'Unprogrammed Song']);

    $program->songTitles()->attach($programmedSong->id);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->set('programStatus', 'not_programmed')
        ->assertSee('Unprogrammed Song')
        ->assertDontSee('Programmed Song');
});

test('any program status filter shows both programmed and unprogrammed songs', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $programmedSong = SongTitle::factory()->create(['song_title' => 'Programmed Song']);
    $unprogrammedSong = SongTitle::factory()->create(['song_title' => 'Unprogrammed Song']);

    $program->songTitles()->attach($programmedSong->id);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->set('programStatus', 'all')
        ->assertSee('Programmed Song')
        ->assertSee('Unprogrammed Song');
});

test('not programmed filter is scoped to my programs when my filter is active', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create();

    $myProgram = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);
    $otherProgram = Program::factory()->create(['user_id' => $otherUser->id, 'school_id' => $school->id]);

    $mySong = SongTitle::factory()->create(['song_title' => 'My Song']);
    $otherSong = SongTitle::factory()->create(['song_title' => 'Other Song']);

    $myProgram->songTitles()->attach($mySong->id);
    $otherProgram->songTitles()->attach($otherSong->id);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'my')
        ->set('programStatus', 'not_programmed')
        ->assertSee('Other Song')
        ->assertDontSee('My Song');
});

test('non compliant users are forced back to the programmed filter', function () {
    $user = User::factory()->create(['created_at' => now()->subDays(30)]);
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('programStatus', 'all')
        ->assertSet('programStatus', 'programmed');
});

test('a song with a description shows the info marker with the description on hover', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $describedSong = SongTitle::factory()->create(['song_title' => 'Described Song']);
    $plainSong = SongTitle::factory()->create(['song_title' => 'Plain Song']);
    $program->songTitles()->attach([$describedSong->id, $plainSong->id]);

    SongTitleDescription::factory()->create([
        'song_title_id' => $describedSong->id,
        'description' => 'A stirring setting of a classic text.',
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSeeHtml('data-flux-tooltip-content')
        ->assertSeeHtml('A stirring setting of a classic text.');
});

test('a song without a description does not show the info marker', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $plainSong = SongTitle::factory()->create(['song_title' => 'Plain Song']);
    $program->songTitles()->attach($plainSong->id);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertDontSeeHtml('data-flux-tooltip-content');
});

test('a song with difficulty observations shows a difficulty badge with a voice part breakdown on hover', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $ratedSong = SongTitle::factory()->create(['song_title' => 'Rated Song']);
    $unratedSong = SongTitle::factory()->create(['song_title' => 'Unrated Song']);
    $program->songTitles()->attach([$ratedSong->id, $unratedSong->id]);

    SongTitleDifficultyObservation::factory()->create([
        'song_title_id' => $ratedSong->id,
        'voice_part' => 'soprano',
        'difficulty_label' => 'challenging',
        'difficulty_value' => 3,
    ]);
    SongTitleDifficultyObservation::factory()->create([
        'song_title_id' => $ratedSong->id,
        'voice_part' => 'alto',
        'difficulty_label' => 'easy',
        'difficulty_value' => 1,
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSeeHtml('Moderate')
        ->assertSeeHtml('Soprano: Challenging · Alto: Easy');
});

test('a song without difficulty observations does not show a difficulty badge', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $unratedSong = SongTitle::factory()->create(['song_title' => 'Unrated Song']);
    $program->songTitles()->attach($unratedSong->id);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertDontSeeHtml('data-flux-tooltip-content');
});

test('the difficulty filter narrows results to songs with the selected overall level', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $easySong = SongTitle::factory()->create(['song_title' => 'Easy Song']);
    $challengingSong = SongTitle::factory()->create(['song_title' => 'Challenging Song']);
    $program->songTitles()->attach([$easySong->id, $challengingSong->id]);

    SongTitleDifficultyObservation::factory()->create([
        'song_title_id' => $easySong->id,
        'voice_part' => 'soprano',
        'difficulty_label' => 'easy',
        'difficulty_value' => 1,
    ]);
    SongTitleDifficultyObservation::factory()->create([
        'song_title_id' => $challengingSong->id,
        'voice_part' => 'soprano',
        'difficulty_label' => 'challenging',
        'difficulty_value' => 3,
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('toggleDifficultyFilter', 'easy')
        ->assertSee('Easy Song')
        ->assertDontSee('Challenging Song');
});

test('clearing the difficulty filter restores all songs', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $easySong = SongTitle::factory()->create(['song_title' => 'Easy Song']);
    $challengingSong = SongTitle::factory()->create(['song_title' => 'Challenging Song']);
    $program->songTitles()->attach([$easySong->id, $challengingSong->id]);

    SongTitleDifficultyObservation::factory()->create([
        'song_title_id' => $easySong->id,
        'voice_part' => 'soprano',
        'difficulty_label' => 'easy',
        'difficulty_value' => 1,
    ]);
    SongTitleDifficultyObservation::factory()->create([
        'song_title_id' => $challengingSong->id,
        'voice_part' => 'soprano',
        'difficulty_label' => 'challenging',
        'difficulty_value' => 3,
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('toggleDifficultyFilter', 'easy')
        ->set('difficultyFilter', [])
        ->assertSee('Easy Song')
        ->assertSee('Challenging Song');
});

test('changing the difficulty filter resets to page 1', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $songTitles = createNumberedSongTitles(25);
    $program->songTitles()->attach($songTitles->pluck('id'));

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('gotoPage', 2)
        ->call('toggleDifficultyFilter', 'easy')
        ->assertViewHas('songTitles', fn ($songTitles) => $songTitles->currentPage() === 1);
});

test('a song with tags shows a yes badge with the tags on hover', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $taggedSong = SongTitle::factory()->create(['song_title' => 'Tagged Song']);
    $untaggedSong = SongTitle::factory()->create(['song_title' => 'Untagged Song']);
    $program->songTitles()->attach([$taggedSong->id, $untaggedSong->id]);

    SongTitleTag::factory()->create(['song_title_id' => $taggedSong->id, 'tag' => 'sacred']);
    SongTitleTag::factory()->create(['song_title_id' => $taggedSong->id, 'tag' => 'holiday']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSeeHtml('holiday, sacred')
        ->assertSeeHtmlInOrder(['Yes', 'No']);
});

test('a song without tags shows a no badge', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $untaggedSong = SongTitle::factory()->create(['song_title' => 'Untagged Song']);
    $program->songTitles()->attach($untaggedSong->id);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSeeHtml('No')
        ->assertDontSeeHtml('data-flux-tooltip-content');
});

test('sorting by difficulty orders songs by overall difficulty level', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $easySong = SongTitle::factory()->create(['song_title' => 'Easy Song']);
    $challengingSong = SongTitle::factory()->create(['song_title' => 'Challenging Song']);
    $program->songTitles()->attach([$easySong->id, $challengingSong->id]);

    SongTitleDifficultyObservation::factory()->create([
        'song_title_id' => $easySong->id,
        'voice_part' => 'soprano',
        'difficulty_label' => 'easy',
        'difficulty_value' => 1,
    ]);
    SongTitleDifficultyObservation::factory()->create([
        'song_title_id' => $challengingSong->id,
        'voice_part' => 'soprano',
        'difficulty_label' => 'challenging',
        'difficulty_value' => 3,
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('sort', 'difficulty')
        ->assertViewHas('songTitles', fn ($songTitles) => $songTitles->pluck('song_title')->all() === ['Easy Song', 'Challenging Song'])
        ->call('sort', 'difficulty')
        ->assertViewHas('songTitles', fn ($songTitles) => $songTitles->pluck('song_title')->all() === ['Challenging Song', 'Easy Song']);
});

test('sorting by tags orders songs by tag count', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $taggedSong = SongTitle::factory()->create(['song_title' => 'Tagged Song']);
    $untaggedSong = SongTitle::factory()->create(['song_title' => 'Untagged Song']);
    $program->songTitles()->attach([$taggedSong->id, $untaggedSong->id]);

    SongTitleTag::factory()->create(['song_title_id' => $taggedSong->id, 'tag' => 'sacred']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('sort', 'tags')
        ->assertViewHas('songTitles', fn ($songTitles) => $songTitles->pluck('song_title')->all() === ['Untagged Song', 'Tagged Song'])
        ->call('sort', 'tags')
        ->assertViewHas('songTitles', fn ($songTitles) => $songTitles->pluck('song_title')->all() === ['Tagged Song', 'Untagged Song']);
});

test('sorting by difficulty resets to page 1', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $songTitles = createNumberedSongTitles(25);
    $program->songTitles()->attach($songTitles->pluck('id'));

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('gotoPage', 2)
        ->call('sort', 'difficulty')
        ->assertViewHas('songTitles', fn ($songTitles) => $songTitles->currentPage() === 1);
});

test('sorting by tags resets to page 1', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $songTitles = createNumberedSongTitles(25);
    $program->songTitles()->attach($songTitles->pluck('id'));

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('gotoPage', 2)
        ->call('sort', 'tags')
        ->assertViewHas('songTitles', fn ($songTitles) => $songTitles->currentPage() === 1);
});

test('the tag filter narrows results to songs with the selected tag', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $sacredSong = SongTitle::factory()->create(['song_title' => 'Sacred Song']);
    $holidaySong = SongTitle::factory()->create(['song_title' => 'Holiday Song']);
    $program->songTitles()->attach([$sacredSong->id, $holidaySong->id]);

    SongTitleTag::factory()->create(['song_title_id' => $sacredSong->id, 'tag' => 'sacred']);
    SongTitleTag::factory()->create(['song_title_id' => $holidaySong->id, 'tag' => 'holiday']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('toggleTagFilter', 'sacred')
        ->assertSee('Sacred Song')
        ->assertDontSee('Holiday Song');
});

test('clearing the tag filter restores all songs', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $sacredSong = SongTitle::factory()->create(['song_title' => 'Sacred Song']);
    $holidaySong = SongTitle::factory()->create(['song_title' => 'Holiday Song']);
    $program->songTitles()->attach([$sacredSong->id, $holidaySong->id]);

    SongTitleTag::factory()->create(['song_title_id' => $sacredSong->id, 'tag' => 'sacred']);
    SongTitleTag::factory()->create(['song_title_id' => $holidaySong->id, 'tag' => 'holiday']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('toggleTagFilter', 'sacred')
        ->set('tagFilter', [])
        ->assertSee('Sacred Song')
        ->assertSee('Holiday Song');
});

test('changing the tag filter resets to page 1', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $songTitles = createNumberedSongTitles(25);
    $program->songTitles()->attach($songTitles->pluck('id'));

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('gotoPage', 2)
        ->call('toggleTagFilter', 'sacred')
        ->assertViewHas('songTitles', fn ($songTitles) => $songTitles->currentPage() === 1);
});

test('the tag filter row is hidden when no tags exist in the catalog', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $song = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertDontSee('toggleTagFilter');
});

test('the programmed status filter shows a clear button labeled Clear instead of Any', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);
    SongTitle::factory()->create()->programs()->attach($program->id);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertDontSee('Any')
        ->assertSee('Clear')
        ->set('programStatus', 'not_programmed')
        ->assertSee('Clear');
});

test('clearAllFilters resets the programmed status and multi-select filters but not scope', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);
    SongTitle::factory()->create()->programs()->attach($program->id);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->set('programStatus', 'not_programmed')
        ->call('toggleEnsembleTypeFilter', 'Satb')
        ->call('toggleDifficultyFilter', 'easy')
        ->call('toggleTagFilter', 'sacred')
        ->call('clearAllFilters')
        ->assertSet('programStatus', 'programmed')
        ->assertSet('ensembleTypeFilter', [])
        ->assertSet('difficultyFilter', [])
        ->assertSet('tagFilter', [])
        ->assertSet('filter', 'all');
});

test('the active filter count reflects non-default filters', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);
    SongTitle::factory()->create()->programs()->attach($program->id);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertDontSee('Clear all')
        ->call('toggleEnsembleTypeFilter', 'Satb')
        ->assertSee('1 active')
        ->assertSee('Clear all')
        ->call('toggleDifficultyFilter', 'easy')
        ->assertSee('2 active');
});

test('the advanced filters section groups status, ensemble type, difficulty, and tags', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);
    $song = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id);
    SongTitleTag::factory()->create(['song_title_id' => $song->id, 'tag' => 'sacred']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('Advanced Filters')
        ->assertSee('Status')
        ->assertSee('Ensemble Type')
        ->assertSee('Difficulty')
        ->assertSee('Tags');
});

test('the applied filters indicator shows the default scope and status when no filters are active', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);
    SongTitle::factory()->create()->programs()->attach($program->id);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('Applied')
        ->assertSee('Scope: All')
        ->assertSee('Status: Programmed');
});

test('the applied filters indicator shows a chip for each active filter', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);
    $song = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id);
    SongTitleTag::factory()->create(['song_title_id' => $song->id, 'tag' => 'sacred']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('programStatus', 'not_programmed')
        ->call('toggleEnsembleTypeFilter', 'Satb')
        ->call('toggleDifficultyFilter', 'easy')
        ->call('toggleTagFilter', 'sacred')
        ->assertSee('Applied')
        ->assertSee('Status: Not Programmed')
        ->assertSee('Satb')
        ->assertSee('Easy')
        ->assertSee('sacred');
});

test('clicking the status chip clears just the status filter and leaves others active', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);
    SongTitle::factory()->create()->programs()->attach($program->id);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('programStatus', 'not_programmed')
        ->call('toggleEnsembleTypeFilter', 'Satb')
        ->set('programStatus', 'programmed')
        ->assertSet('programStatus', 'programmed')
        ->assertSet('ensembleTypeFilter', ['Satb'])
        ->assertDontSee('Status: Not Programmed');
});

test('the video map only includes videos for songs on the current page', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    $firstPageSongs = createNumberedSongTitles(20, 'AAA Song');
    $secondPageSong = SongTitle::create(['song_title' => 'ZZZ Video Song']);

    $program->songTitles()->attach($firstPageSongs->pluck('id'));
    $program->songTitles()->attach($secondPageSong->id, [
        'sort_order' => 1,
        'video_path' => 'mp4s/songs/test.mp4',
        'video_visibility' => 'Private',
        'video_uploaded_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertDontSeeHtml('Watch Video')
        ->call('gotoPage', 2)
        ->assertSeeHtml('Watch Video');
});
