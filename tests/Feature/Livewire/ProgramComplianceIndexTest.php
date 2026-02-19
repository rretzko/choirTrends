<?php

use App\Livewire\Artists\Index as ArtistsIndex;
use App\Livewire\Ensembles\Index as EnsemblesIndex;
use App\Livewire\Programs\Index as ProgramsIndex;
use App\Livewire\Schools\Index as SchoolsIndex;
use App\Livewire\SongTitles\Index as SongTitlesIndex;
use App\Models\Program;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

dataset('index components', [
    'programs' => [ProgramsIndex::class],
    'artists' => [ArtistsIndex::class],
    'ensembles' => [EnsemblesIndex::class],
    'schools' => [SchoolsIndex::class],
    'song titles' => [SongTitlesIndex::class],
]);

test('non-compliant user filter is forced to my', function (string $componentClass) {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    // User created 30 days ago with no programs — past 14-day deadline
    $user = User::factory()->create([
        'created_at' => Carbon::parse('2026-01-15'),
    ]);

    $this->actingAs($user);

    Livewire::test($componentClass)
        ->set('filter', 'all')
        ->assertSet('filter', 'my');

    Carbon::setTestNow();
})->with('index components');

test('compliant user can use all filter', function (string $componentClass) {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $user = User::factory()->create([
        'created_at' => Carbon::parse('2026-01-01'),
    ]);
    $school = School::factory()->create();
    Program::factory()->for($user)->for($school)->create(['event_date' => '2026-01-15']);

    $this->actingAs($user);

    Livewire::test($componentClass)
        ->set('filter', 'all')
        ->assertSet('filter', 'all');

    Carbon::setTestNow();
})->with('index components');

test('founder can always use all filter', function (string $componentClass) {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));
    config(['app.founder' => 'founder@example.com']);

    $user = User::factory()->founder()->create([
        'created_at' => Carbon::parse('2024-01-01'),
    ]);

    $this->actingAs($user);

    Livewire::test($componentClass)
        ->set('filter', 'all')
        ->assertSet('filter', 'all');

    Carbon::setTestNow();
})->with('index components');

test('non-compliant user sees disabled All button', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $user = User::factory()->create([
        'created_at' => Carbon::parse('2026-01-15'),
    ]);

    $this->actingAs($user);

    Livewire::test(ProgramsIndex::class)
        ->assertSee('Upload programs to unlock community data');

    Carbon::setTestNow();
});

test('compliant user sees active All button', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $user = User::factory()->create([
        'created_at' => Carbon::parse('2026-01-01'),
    ]);
    $school = School::factory()->create();
    Program::factory()->for($user)->for($school)->create(['event_date' => '2026-01-15']);

    $this->actingAs($user);

    Livewire::test(ProgramsIndex::class)
        ->assertDontSee('Upload programs to unlock community data');

    Carbon::setTestNow();
});
