<?php

declare(strict_types=1);

use App\Livewire\SongTitles\Index;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use App\Models\UserSongLyrics;
use Carbon\Carbon;
use Livewire\Livewire;

test('non-compliant user cannot opt into lyrics search', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $user = User::factory()->create([
        'created_at' => Carbon::parse('2026-01-15'),
    ]);

    $this->actingAs($user);

    $songOwner = User::factory()->create();
    $song = SongTitle::factory()->create(['song_title' => 'Hidden Song']);
    $ownerSchool = School::factory()->create();
    $ownerProgram = Program::factory()->for($songOwner)->for($ownerSchool)->create();
    $ownerProgram->songTitles()->attach($song->id, ['sort_order' => 1]);

    UserSongLyrics::factory()->create([
        'user_id' => $songOwner->id,
        'song_title_id' => $song->id,
        'content' => 'Waltzing Matilda under the coolabah tree',
    ]);

    Livewire::test(Index::class)
        ->set('searchLyrics', true)
        ->set('search', 'coolabah')
        ->assertDontSee('Hidden Song');

    Carbon::setTestNow();
});

test('compliant user can find a song by its lyrics content', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $viewer = User::factory()->create([
        'created_at' => Carbon::parse('2026-01-01'),
    ]);
    $viewerSchool = School::factory()->create();
    Program::factory()->for($viewer)->for($viewerSchool)->create(['event_date' => '2026-01-15']);

    $this->actingAs($viewer);

    $songOwner = User::factory()->create();
    $ownerSchool = School::factory()->create();
    $ownerProgram = Program::factory()->for($songOwner)->for($ownerSchool)->create(['event_date' => '2026-01-20']);

    $song = SongTitle::factory()->create(['song_title' => 'Waltzing Matilda']);
    $ownerProgram->songTitles()->attach($song->id, ['sort_order' => 1]);

    UserSongLyrics::factory()->create([
        'user_id' => $songOwner->id,
        'song_title_id' => $song->id,
        'content' => 'Once a jolly swagman camped by a coolabah tree',
    ]);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->set('searchLyrics', true)
        ->set('search', 'swagman')
        ->assertSee('Waltzing Matilda');

    Carbon::setTestNow();
});

test('lyrics content is not rendered in search results', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $viewer = User::factory()->create([
        'created_at' => Carbon::parse('2026-01-01'),
    ]);
    $viewerSchool = School::factory()->create();
    Program::factory()->for($viewer)->for($viewerSchool)->create(['event_date' => '2026-01-15']);

    $this->actingAs($viewer);

    $songOwner = User::factory()->create();
    $ownerSchool = School::factory()->create();
    $ownerProgram = Program::factory()->for($songOwner)->for($ownerSchool)->create(['event_date' => '2026-01-20']);

    $song = SongTitle::factory()->create(['song_title' => 'Greensleeves']);
    $ownerProgram->songTitles()->attach($song->id, ['sort_order' => 1]);

    $secretLyrics = 'Alas my love you do me wrong to cast me off discourteously';

    UserSongLyrics::factory()->create([
        'user_id' => $songOwner->id,
        'song_title_id' => $song->id,
        'content' => $secretLyrics,
    ]);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->set('searchLyrics', true)
        ->set('search', 'discourteously')
        ->assertSee('Greensleeves')
        ->assertDontSee($secretLyrics);

    Carbon::setTestNow();
});

test('lyrics search is off by default', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $viewer = User::factory()->create([
        'created_at' => Carbon::parse('2026-01-01'),
    ]);
    $viewerSchool = School::factory()->create();
    Program::factory()->for($viewer)->for($viewerSchool)->create(['event_date' => '2026-01-15']);

    $this->actingAs($viewer);

    $songOwner = User::factory()->create();
    $ownerSchool = School::factory()->create();
    $ownerProgram = Program::factory()->for($songOwner)->for($ownerSchool)->create(['event_date' => '2026-01-20']);

    $song = SongTitle::factory()->create(['song_title' => 'Simple Gifts']);
    $ownerProgram->songTitles()->attach($song->id, ['sort_order' => 1]);

    UserSongLyrics::factory()->create([
        'user_id' => $songOwner->id,
        'song_title_id' => $song->id,
        'content' => 'Tis the gift to be simple tis the gift to be free',
    ]);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->set('search', 'free')
        ->assertDontSee('Simple Gifts');

    Carbon::setTestNow();
});
