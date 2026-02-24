<?php

declare(strict_types=1);

use App\Livewire\Founder\SongTitleConflicts;
use App\Models\Artist;
use App\Models\SongTitle;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.founder' => 'founder@example.com']);
});

// --- Access Control ---

test('founder can access the song title conflicts page', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $this->actingAs($founder)
        ->get(route('founder.songTitleConflicts'))
        ->assertOk();
});

test('non-founder gets 403 on song title conflicts page', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('founder.songTitleConflicts'))
        ->assertForbidden();
});

test('guest is redirected from song title conflicts page', function () {
    $this->get(route('founder.songTitleConflicts'))
        ->assertRedirect(route('login'));
});

// --- Page Rendering ---

test('song title conflicts page renders with correct layout', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(SongTitleConflicts::class)
        ->assertSee('Song Title Conflicts')
        ->assertSee('conflicting composer or arranger attributions')
        ->assertOk();
});

// --- Conflict Detection ---

test('conflicts display when song titles have different composers', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $composerA = Artist::factory()->create(['artist_name' => 'Johann Bach']);
    $composerB = Artist::factory()->create(['artist_name' => 'George Handel']);

    SongTitle::factory()->create([
        'song_title' => 'Tres Cantos',
        'composer_id' => $composerA->id,
        'arranger_id' => null,
    ]);
    SongTitle::factory()->create([
        'song_title' => 'Tres Cantos',
        'composer_id' => $composerB->id,
        'arranger_id' => null,
    ]);

    Livewire::actingAs($founder)
        ->test(SongTitleConflicts::class)
        ->assertSee('Tres Cantos')
        ->assertSee('Johann Bach')
        ->assertSee('George Handel')
        ->assertOk();
});

test('conflicts display when song titles have different arrangers', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $composer = Artist::factory()->create(['artist_name' => 'Common Composer']);
    $arrangerA = Artist::factory()->create(['artist_name' => 'Arranger One']);
    $arrangerB = Artist::factory()->create(['artist_name' => 'Arranger Two']);

    SongTitle::factory()->create([
        'song_title' => 'Gloria',
        'composer_id' => $composer->id,
        'arranger_id' => $arrangerA->id,
    ]);
    SongTitle::factory()->create([
        'song_title' => 'Gloria',
        'composer_id' => $composer->id,
        'arranger_id' => $arrangerB->id,
    ]);

    Livewire::actingAs($founder)
        ->test(SongTitleConflicts::class)
        ->assertSee('Gloria')
        ->assertSee('Arranger One')
        ->assertSee('Arranger Two')
        ->assertOk();
});

test('conflicts display when one record has null composer and other has a composer', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $composer = Artist::factory()->create(['artist_name' => 'Solo Composer']);

    SongTitle::factory()->create([
        'song_title' => 'Sanctus',
        'composer_id' => $composer->id,
        'arranger_id' => null,
    ]);
    SongTitle::factory()->create([
        'song_title' => 'Sanctus',
        'composer_id' => null,
        'arranger_id' => null,
    ]);

    Livewire::actingAs($founder)
        ->test(SongTitleConflicts::class)
        ->assertSee('Sanctus')
        ->assertSee('Solo Composer')
        ->assertOk();
});

// --- Empty State ---

test('shows empty state when no conflicts exist', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $composer = Artist::factory()->create();

    SongTitle::factory()->create([
        'song_title' => 'Unique Song A',
        'composer_id' => $composer->id,
    ]);
    SongTitle::factory()->create([
        'song_title' => 'Unique Song B',
        'composer_id' => $composer->id,
    ]);

    Livewire::actingAs($founder)
        ->test(SongTitleConflicts::class)
        ->assertSee('No song title conflicts found.')
        ->assertOk();
});

test('no conflicts when identical song titles have same attributions', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $composer = Artist::factory()->create();
    $arranger = Artist::factory()->create();

    // These are identical attributions — not conflicts (unique constraint prevents same combo,
    // but the component should show no conflicts if the records are separate titles)
    SongTitle::factory()->create([
        'song_title' => 'Same Song',
        'composer_id' => $composer->id,
        'arranger_id' => $arranger->id,
    ]);

    Livewire::actingAs($founder)
        ->test(SongTitleConflicts::class)
        ->assertSee('No song title conflicts found.')
        ->assertOk();
});

// --- Search Links ---

test('google search links are generated for song title', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $composer = Artist::factory()->create(['artist_name' => 'Johann Bach']);

    SongTitle::factory()->create([
        'song_title' => 'Tres Cantos',
        'composer_id' => $composer->id,
        'arranger_id' => null,
    ]);
    SongTitle::factory()->create([
        'song_title' => 'Tres Cantos',
        'composer_id' => null,
        'arranger_id' => null,
    ]);

    $response = Livewire::actingAs($founder)
        ->test(SongTitleConflicts::class);

    $response->assertSeeHtml('https://google.com/search?q='.urlencode('"Tres Cantos" composer arranger choral'));
});

test('google search links are generated for composer verification', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $composerA = Artist::factory()->create(['artist_name' => 'Johann Bach']);
    $composerB = Artist::factory()->create(['artist_name' => 'George Handel']);

    SongTitle::factory()->create([
        'song_title' => 'Tres Cantos',
        'composer_id' => $composerA->id,
        'arranger_id' => null,
    ]);
    SongTitle::factory()->create([
        'song_title' => 'Tres Cantos',
        'composer_id' => $composerB->id,
        'arranger_id' => null,
    ]);

    $response = Livewire::actingAs($founder)
        ->test(SongTitleConflicts::class);

    $response->assertSeeHtml('https://google.com/search?q='.urlencode('"Tres Cantos" "Johann Bach"'));
    $response->assertSeeHtml('https://google.com/search?q='.urlencode('"Tres Cantos" "George Handel"'));
});

// --- Program Count ---

test('program count is displayed for each conflicting record', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $composerA = Artist::factory()->create();
    $composerB = Artist::factory()->create();

    $songA = SongTitle::factory()->create([
        'song_title' => 'Test Conflict',
        'composer_id' => $composerA->id,
    ]);
    $songB = SongTitle::factory()->create([
        'song_title' => 'Test Conflict',
        'composer_id' => $composerB->id,
    ]);

    Livewire::actingAs($founder)
        ->test(SongTitleConflicts::class)
        ->assertSee('Test Conflict')
        ->assertSee('0')
        ->assertOk();
});

// --- Duplicates Link ---

test('duplicates page link is present', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(SongTitleConflicts::class)
        ->assertSee('Go to Duplicates Page')
        ->assertOk();
});
