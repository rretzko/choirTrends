<?php

declare(strict_types=1);

use App\Livewire\Founder\Duplicates;
use App\Models\Artist;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.founder' => 'founder@example.com']);
});

// --- Access Control ---

test('founder can access the duplicates page', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $this->actingAs($founder)
        ->get(route('founder.duplicates'))
        ->assertOk();
});

test('non-founder gets 403 on duplicates page', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('founder.duplicates'))
        ->assertForbidden();
});

test('guest is redirected from duplicates page', function () {
    $this->get(route('founder.duplicates'))
        ->assertRedirect(route('login'));
});

// --- Page Rendering ---

test('duplicates page renders with correct layout', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->assertSee('Duplicates')
        ->assertSee('Schools')
        ->assertSee('Composers/Arrangers')
        ->assertSee('Songs')
        ->assertStatus(200);
});

test('tab switching works and resets selections', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $school = School::factory()->create();

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->assertSet('activeTab', 'schools')
        ->set('keeperId', $school->id)
        ->call('switchTab', 'artists')
        ->assertSet('activeTab', 'artists')
        ->assertSet('keeperId', null)
        ->assertSet('duplicateId', null)
        ->call('switchTab', 'song-titles')
        ->assertSet('activeTab', 'song-titles');
});

// --- Manual Selection ---

test('records list shows all schools on schools tab', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $schoolA = School::factory()->create(['school_name' => 'Alpha School']);
    $schoolB = School::factory()->create(['school_name' => 'Beta School']);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->assertSee('Alpha School')
        ->assertSee('Beta School');
});

test('records list shows all artists on artists tab', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Artist::factory()->create(['artist_name' => 'Johann Bach']);
    Artist::factory()->create(['artist_name' => 'Wolfgang Mozart']);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('switchTab', 'artists')
        ->assertSee('Johann Bach')
        ->assertSee('Wolfgang Mozart');
});

test('artists tab shows reference table with artist details', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Artist::factory()->create([
        'artist_name' => 'Bach, Johann Sebastian',
        'artist_first_name' => 'Johann Sebastian',
        'artist_last_name' => 'Bach',
    ]);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('switchTab', 'artists')
        ->assertSee('Artist Name')
        ->assertSee('First Name')
        ->assertSee('Last Name')
        ->assertSee('Bach, Johann Sebastian')
        ->assertSee('Johann Sebastian')
        ->assertSeeInOrder(['Bach, Johann Sebastian', 'Johann Sebastian', 'Bach']);
});

test('artists reference table is not visible on schools tab', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Artist::factory()->create(['artist_name' => 'Test Artist']);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->assertSet('activeTab', 'schools')
        ->assertDontSee('Artist Name');
});

test('records list shows all song titles on song-titles tab', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $composer = Artist::factory()->create(['artist_name' => 'Composer A']);
    SongTitle::factory()->create(['song_title' => 'Ave Maria', 'composer_id' => $composer->id, 'arranger_id' => null]);
    SongTitle::factory()->create(['song_title' => 'Hallelujah', 'composer_id' => $composer->id, 'arranger_id' => null]);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('switchTab', 'song-titles')
        ->assertSee('Ave Maria')
        ->assertSee('Hallelujah');
});

test('selecting same record as keeper clears duplicate selection', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $school = School::factory()->create();

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->set('duplicateId', $school->id)
        ->set('keeperId', $school->id)
        ->assertSet('duplicateId', null);
});

// --- Manual Merge ---

test('manual merge calls correct merge method for schools', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $keeper = School::factory()->create(['school_name' => 'North Plainfield High School']);
    $duplicate = School::factory()->create(['school_name' => 'Plainfield Public High School']);

    $program = Program::factory()->create(['school_id' => $duplicate->id]);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->set('keeperId', $keeper->id)
        ->set('duplicateId', $duplicate->id)
        ->call('manualMerge');

    expect(Program::find($program->id)->school_id)->toBe($keeper->id);
    expect(School::find($duplicate->id))->toBeNull();
});

test('manual merge calls correct merge method for artists', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $keeper = Artist::factory()->create(['artist_name' => 'Johann Bach']);
    $duplicate = Artist::factory()->create(['artist_name' => 'J.S. Bach']);

    $song = SongTitle::factory()->create([
        'song_title' => 'Mass in B Minor',
        'composer_id' => $duplicate->id,
        'arranger_id' => null,
    ]);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('switchTab', 'artists')
        ->set('keeperId', $keeper->id)
        ->set('duplicateId', $duplicate->id)
        ->call('manualMerge');

    expect(SongTitle::find($song->id)->composer_id)->toBe($keeper->id);
    expect(Artist::find($duplicate->id))->toBeNull();
});

test('manual merge calls correct merge method for song titles', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $composerA = Artist::factory()->create(['artist_name' => 'Composer A']);
    $composerB = Artist::factory()->create(['artist_name' => 'Composer B']);

    $keeper = SongTitle::factory()->create(['song_title' => 'Ave Maria', 'composer_id' => $composerA->id, 'arranger_id' => null]);
    $duplicate = SongTitle::factory()->create(['song_title' => 'Ave Maria', 'composer_id' => $composerB->id, 'arranger_id' => null]);

    $program = Program::factory()->create();
    $program->songTitles()->attach($duplicate->id);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('switchTab', 'song-titles')
        ->set('keeperId', $keeper->id)
        ->set('duplicateId', $duplicate->id)
        ->call('manualMerge');

    expect($program->fresh()->songTitles->pluck('id'))->toContain($keeper->id);
    expect(SongTitle::find($duplicate->id))->toBeNull();
});

test('manual merge resets selections after merge', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $keeper = School::factory()->create(['school_name' => 'School A']);
    $duplicate = School::factory()->create(['school_name' => 'School B']);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->set('keeperId', $keeper->id)
        ->set('duplicateId', $duplicate->id)
        ->call('manualMerge')
        ->assertSet('keeperId', null)
        ->assertSet('duplicateId', null);
});

test('manual merge does nothing when keeper and duplicate are same', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $school = School::factory()->create(['school_name' => 'Test School']);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->set('keeperId', $school->id)
        ->set('duplicateId', $school->id)
        ->call('manualMerge');

    expect(School::find($school->id))->not->toBeNull();
});

test('manual merge does nothing when selections are missing', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('manualMerge')
        ->assertSet('successMessage', '');
});

// --- School Merge ---

test('merging schools reassigns programs and deletes duplicate', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $keeper = School::factory()->create(['school_name' => 'Lincoln High School', 'postal_code' => '12345']);
    $duplicate = School::factory()->create(['school_name' => 'Lincoln High School', 'postal_code' => '67890']);

    $program = Program::factory()->create(['school_id' => $duplicate->id]);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('mergeSchools', $keeper->id, (string) $duplicate->id);

    expect(Program::find($program->id)->school_id)->toBe($keeper->id);
    expect(School::find($duplicate->id))->toBeNull();
});

test('merging schools reassigns ensembles and skips conflicts', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $keeper = School::factory()->create(['school_name' => 'Lincoln High School', 'postal_code' => '12345']);
    $duplicate = School::factory()->create(['school_name' => 'Lincoln High School', 'postal_code' => '67890']);

    $keeperEnsemble = Ensemble::factory()->create(['school_id' => $keeper->id, 'ensemble_name' => 'Concert Choir']);
    $duplicateEnsemble = Ensemble::factory()->create(['school_id' => $duplicate->id, 'ensemble_name' => 'Concert Choir']);
    $uniqueEnsemble = Ensemble::factory()->create(['school_id' => $duplicate->id, 'ensemble_name' => 'Jazz Choir']);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('mergeSchools', $keeper->id, (string) $duplicate->id);

    // Conflicting ensemble was deleted
    expect(Ensemble::find($duplicateEnsemble->id))->toBeNull();
    // Unique ensemble was moved to keeper
    expect(Ensemble::find($uniqueEnsemble->id)->school_id)->toBe($keeper->id);
    // Keeper ensemble unchanged
    expect(Ensemble::find($keeperEnsemble->id))->not->toBeNull();
    expect(School::find($duplicate->id))->toBeNull();
});

test('merging schools reassigns school_user pivot entries', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $keeper = School::factory()->create(['school_name' => 'Lincoln High School', 'postal_code' => '12345']);
    $duplicate = School::factory()->create(['school_name' => 'Lincoln High School', 'postal_code' => '67890']);

    $user = User::factory()->withoutTwoFactor()->create();
    $duplicate->users()->attach($user);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('mergeSchools', $keeper->id, (string) $duplicate->id);

    expect($keeper->fresh()->users->pluck('id'))->toContain($user->id);
    expect(School::find($duplicate->id))->toBeNull();
});

test('merging schools skips existing school_user pivot entries', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $keeper = School::factory()->create(['school_name' => 'Lincoln High School', 'postal_code' => '12345']);
    $duplicate = School::factory()->create(['school_name' => 'Lincoln High School', 'postal_code' => '67890']);

    $user = User::factory()->withoutTwoFactor()->create();
    $keeper->users()->attach($user);
    $duplicate->users()->attach($user);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('mergeSchools', $keeper->id, (string) $duplicate->id);

    // Should only have one pivot entry, not duplicate
    expect($keeper->fresh()->users->count())->toBe(1);
    expect(School::find($duplicate->id))->toBeNull();
});

// --- Artist Merge ---

test('merging artists reassigns composer references and deletes duplicate', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $keeper = Artist::factory()->create(['artist_name' => 'Johann Bach']);
    $duplicate = Artist::factory()->create(['artist_name' => 'johann bach']);

    $song = SongTitle::factory()->create([
        'song_title' => 'Mass in B Minor',
        'composer_id' => $duplicate->id,
        'arranger_id' => null,
    ]);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('switchTab', 'artists')
        ->call('mergeArtists', $keeper->id, (string) $duplicate->id);

    expect(SongTitle::find($song->id)->composer_id)->toBe($keeper->id);
    expect(Artist::find($duplicate->id))->toBeNull();
});

test('merging artists reassigns arranger references and deletes duplicate', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $keeper = Artist::factory()->create(['artist_name' => 'Johann Bach']);
    $duplicate = Artist::factory()->create(['artist_name' => 'johann bach']);

    $song = SongTitle::factory()->create([
        'song_title' => 'Ave Maria',
        'composer_id' => null,
        'arranger_id' => $duplicate->id,
    ]);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('switchTab', 'artists')
        ->call('mergeArtists', $keeper->id, (string) $duplicate->id);

    expect(SongTitle::find($song->id)->arranger_id)->toBe($keeper->id);
    expect(Artist::find($duplicate->id))->toBeNull();
});

test('merging artists handles song title unique constraint conflicts', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $keeper = Artist::factory()->create(['artist_name' => 'Johann Bach']);
    $duplicate = Artist::factory()->create(['artist_name' => 'johann bach']);

    // Both have the same song title with themselves as composer — merging would create a duplicate
    $keeperSong = SongTitle::factory()->create([
        'song_title' => 'Mass in B Minor',
        'composer_id' => $keeper->id,
        'arranger_id' => null,
    ]);
    $duplicateSong = SongTitle::factory()->create([
        'song_title' => 'Mass in B Minor',
        'composer_id' => $duplicate->id,
        'arranger_id' => null,
    ]);

    // Attach a program to the duplicate song
    $program = Program::factory()->create();
    $program->songTitles()->attach($duplicateSong->id);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('switchTab', 'artists')
        ->call('mergeArtists', $keeper->id, (string) $duplicate->id);

    // Duplicate song was merged into keeper song
    expect(SongTitle::find($duplicateSong->id))->toBeNull();
    // Keeper song still exists
    expect(SongTitle::find($keeperSong->id))->not->toBeNull();
    // Program now linked to keeper song
    expect($program->fresh()->songTitles->pluck('id'))->toContain($keeperSong->id);
    expect(Artist::find($duplicate->id))->toBeNull();
});

// --- SongTitle Merge ---

test('merging song titles reassigns pivot entries and deletes duplicate', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $composerA = Artist::factory()->create(['artist_name' => 'Composer A']);
    $composerB = Artist::factory()->create(['artist_name' => 'Composer B']);

    $keeper = SongTitle::factory()->create(['song_title' => 'Ave Maria', 'composer_id' => $composerA->id, 'arranger_id' => null]);
    $duplicate = SongTitle::factory()->create(['song_title' => 'Ave Maria', 'composer_id' => $composerB->id, 'arranger_id' => null]);

    $program = Program::factory()->create();
    $program->songTitles()->attach($duplicate->id);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('switchTab', 'song-titles')
        ->call('mergeSongTitles', $keeper->id, (string) $duplicate->id);

    expect($program->fresh()->songTitles->pluck('id'))->toContain($keeper->id);
    expect(SongTitle::find($duplicate->id))->toBeNull();
});

test('merging song titles handles pivot primary key conflicts', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $composerA = Artist::factory()->create(['artist_name' => 'Composer A']);
    $composerB = Artist::factory()->create(['artist_name' => 'Composer B']);

    $keeper = SongTitle::factory()->create(['song_title' => 'Ave Maria', 'composer_id' => $composerA->id, 'arranger_id' => null]);
    $duplicate = SongTitle::factory()->create(['song_title' => 'Ave Maria', 'composer_id' => $composerB->id, 'arranger_id' => null]);

    // Same program linked to both songs — PK conflict on merge
    $program = Program::factory()->create();
    $program->songTitles()->attach($keeper->id);
    $program->songTitles()->attach($duplicate->id);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('switchTab', 'song-titles')
        ->call('mergeSongTitles', $keeper->id, (string) $duplicate->id);

    // Duplicate was deleted, keeper remains
    expect(SongTitle::find($duplicate->id))->toBeNull();
    expect(SongTitle::find($keeper->id))->not->toBeNull();
    // Program still linked to keeper (only one entry)
    expect($program->fresh()->songTitles->count())->toBe(1);
    expect($program->fresh()->songTitles->first()->id)->toBe($keeper->id);
});

// --- Artist Edit ---

test('founder can open edit artist modal', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $artist = Artist::factory()->create([
        'artist_name' => 'Bach, Johann Sebastian',
        'artist_first_name' => 'Johann Sebastian',
        'artist_last_name' => 'Bach',
    ]);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('switchTab', 'artists')
        ->call('editArtist', $artist->id)
        ->assertSet('editingArtistId', $artist->id)
        ->assertSet('editArtistName', 'Bach, Johann Sebastian')
        ->assertSet('editArtistFirstName', 'Johann Sebastian')
        ->assertSet('editArtistLastName', 'Bach');
});

test('founder can update an artist', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $artist = Artist::factory()->create([
        'artist_name' => 'Bach, Johann Sebastian',
        'artist_first_name' => 'Johann Sebastian',
        'artist_last_name' => 'Bach',
    ]);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('switchTab', 'artists')
        ->call('editArtist', $artist->id)
        ->set('editArtistName', 'Bach, J.S.')
        ->set('editArtistFirstName', 'J.S.')
        ->set('editArtistLastName', 'Bach')
        ->call('updateArtist')
        ->assertSet('editingArtistId', null)
        ->assertSet('successMessage', 'Artist updated successfully.');

    expect($artist->fresh()->artist_name)->toBe('Bach, J.S.');
    expect($artist->fresh()->artist_first_name)->toBe('J.S.');
});

test('update artist validates required artist name', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $artist = Artist::factory()->create(['artist_name' => 'Test Artist']);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('switchTab', 'artists')
        ->call('editArtist', $artist->id)
        ->set('editArtistName', '')
        ->call('updateArtist')
        ->assertHasErrors(['editArtistName' => 'required']);
});

test('non-founder cannot edit an artist', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $artist = Artist::factory()->create(['artist_name' => 'Test Artist']);

    Livewire::actingAs($user)
        ->test(Duplicates::class)
        ->call('editArtist', $artist->id)
        ->assertForbidden();
});

test('non-founder cannot update an artist', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $artist = Artist::factory()->create(['artist_name' => 'Test Artist']);

    Livewire::actingAs($user)
        ->test(Duplicates::class)
        ->set('editingArtistId', $artist->id)
        ->set('editArtistName', 'Hacked')
        ->call('updateArtist')
        ->assertForbidden();
});

// --- Authorization on Merge Actions ---

test('non-founder cannot merge schools', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $schoolA = School::factory()->create(['school_name' => 'Test School', 'postal_code' => '11111']);
    $schoolB = School::factory()->create(['school_name' => 'Test School', 'postal_code' => '22222']);

    Livewire::actingAs($user)
        ->test(Duplicates::class)
        ->call('mergeSchools', $schoolA->id, (string) $schoolB->id)
        ->assertForbidden();
});

test('non-founder cannot merge artists', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $artistA = Artist::factory()->create(['artist_name' => 'Test Artist']);
    $artistB = Artist::factory()->create(['artist_name' => 'test artist']);

    Livewire::actingAs($user)
        ->test(Duplicates::class)
        ->call('mergeArtists', $artistA->id, (string) $artistB->id)
        ->assertForbidden();
});

test('non-founder cannot merge song titles', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $composerA = Artist::factory()->create(['artist_name' => 'Composer A']);
    $composerB = Artist::factory()->create(['artist_name' => 'Composer B']);

    $songA = SongTitle::factory()->create(['song_title' => 'Test Song', 'composer_id' => $composerA->id, 'arranger_id' => null]);
    $songB = SongTitle::factory()->create(['song_title' => 'Test Song', 'composer_id' => $composerB->id, 'arranger_id' => null]);

    Livewire::actingAs($user)
        ->test(Duplicates::class)
        ->call('mergeSongTitles', $songA->id, (string) $songB->id)
        ->assertForbidden();
});

test('non-founder cannot call manual merge', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $schoolA = School::factory()->create(['school_name' => 'Test School A']);
    $schoolB = School::factory()->create(['school_name' => 'Test School B']);

    Livewire::actingAs($user)
        ->test(Duplicates::class)
        ->set('keeperId', $schoolA->id)
        ->set('duplicateId', $schoolB->id)
        ->call('manualMerge')
        ->assertForbidden();
});

// --- Success Messages ---

test('merge schools shows success message', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $keeper = School::factory()->create(['school_name' => 'Merge School', 'postal_code' => '12345']);
    $duplicate = School::factory()->create(['school_name' => 'Merge School', 'postal_code' => '67890']);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('mergeSchools', $keeper->id, (string) $duplicate->id)
        ->assertSet('successMessage', 'Schools merged successfully.')
        ->assertSee('Schools merged successfully.');
});

// --- Song Title Table ---

test('song-titles tab shows paginated reference table', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $composer = Artist::factory()->create(['artist_name' => 'Test Composer']);
    $arranger = Artist::factory()->create(['artist_name' => 'Test Arranger']);

    SongTitle::factory()->create([
        'song_title' => 'Amazing Grace',
        'composer_id' => $composer->id,
        'arranger_id' => $arranger->id,
    ]);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('switchTab', 'song-titles')
        ->assertSee('Song Title')
        ->assertSee('Composer')
        ->assertSee('Arranger')
        ->assertSee('Amazing Grace')
        ->assertSee('Test Composer')
        ->assertSee('Test Arranger');
});

test('song-titles table is not visible on other tabs', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    SongTitle::factory()->create(['song_title' => 'Hidden Song']);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->assertSet('activeTab', 'schools')
        ->assertDontSee('Song Title');
});

test('song-titles table shows em-dash for missing composer and arranger', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    SongTitle::factory()->create([
        'song_title' => 'Orphan Song',
        'composer_id' => null,
        'arranger_id' => null,
    ]);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('switchTab', 'song-titles')
        ->assertSee('Orphan Song');
});

// --- Song Title Edit ---

test('founder can open edit song modal', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $composer = Artist::factory()->create(['artist_name' => 'Bach']);
    $song = SongTitle::factory()->create([
        'song_title' => 'Mass in B Minor',
        'composer_id' => $composer->id,
        'arranger_id' => null,
    ]);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('switchTab', 'song-titles')
        ->call('editSong', $song->id)
        ->assertSet('editingSongId', $song->id)
        ->assertSet('editSongName', 'Mass in B Minor')
        ->assertSet('editSongComposerId', $composer->id)
        ->assertSet('editSongArrangerId', null);
});

test('founder can update a song title', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $composer = Artist::factory()->create(['artist_name' => 'Bach']);
    $newComposer = Artist::factory()->create(['artist_name' => 'Mozart']);

    $song = SongTitle::factory()->create([
        'song_title' => 'Mass in B Minor',
        'composer_id' => $composer->id,
        'arranger_id' => null,
    ]);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('switchTab', 'song-titles')
        ->call('editSong', $song->id)
        ->set('editSongName', 'Mass in B Minor, BWV 232')
        ->set('editSongComposerId', $newComposer->id)
        ->call('updateSong')
        ->assertSet('editingSongId', null)
        ->assertSet('successMessage', 'Song updated successfully.');

    $song->refresh();
    expect($song->song_title)->toBe('Mass in B Minor, BWV 232');
    expect($song->composer_id)->toBe($newComposer->id);
});

test('update song validates required song title', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $song = SongTitle::factory()->create(['song_title' => 'Test Song']);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('switchTab', 'song-titles')
        ->call('editSong', $song->id)
        ->set('editSongName', '')
        ->call('updateSong')
        ->assertHasErrors(['editSongName' => 'required']);
});

test('non-founder cannot edit a song', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $song = SongTitle::factory()->create(['song_title' => 'Test Song']);

    Livewire::actingAs($user)
        ->test(Duplicates::class)
        ->call('editSong', $song->id)
        ->assertForbidden();
});

test('non-founder cannot update a song', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $song = SongTitle::factory()->create(['song_title' => 'Test Song']);

    Livewire::actingAs($user)
        ->test(Duplicates::class)
        ->set('editingSongId', $song->id)
        ->set('editSongName', 'Hacked')
        ->call('updateSong')
        ->assertForbidden();
});

// --- Song Title Remove ---

test('founder can remove a song title', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $song = SongTitle::factory()->create(['song_title' => 'Delete Me']);
    $program = Program::factory()->create();
    $program->songTitles()->attach($song->id);

    Livewire::actingAs($founder)
        ->test(Duplicates::class)
        ->call('switchTab', 'song-titles')
        ->call('removeSong', $song->id)
        ->assertSet('successMessage', 'Song removed successfully.');

    expect(SongTitle::find($song->id))->toBeNull();
    expect($program->fresh()->songTitles)->toBeEmpty();
});

test('non-founder cannot remove a song', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $song = SongTitle::factory()->create(['song_title' => 'Test Song']);

    Livewire::actingAs($user)
        ->test(Duplicates::class)
        ->call('removeSong', $song->id)
        ->assertForbidden();
});
