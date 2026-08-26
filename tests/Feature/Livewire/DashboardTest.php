<?php

use App\Enums\SongTitleOrigin;
use App\Livewire\Dashboard;
use App\Models\Artist;
use App\Models\DigitalProgram;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use Livewire\Livewire;

test('dashboard component displays summary counts', function () {
    $user = User::factory()->create();

    Artist::factory()->count(5)->create();
    $schools = School::factory()->count(3)->create();
    foreach ($schools as $school) {
        Ensemble::factory()->for($school)->create();
    }
    $programs = Program::factory()->count(2)->for($user)->for($schools->first())->create();
    SongTitle::factory()->count(4)->create();
    DigitalProgram::factory()->for($user)->for($programs->first())->create();

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSet('artistsCount', 5)
        ->assertSet('ensemblesCount', 3)
        ->assertSet('programsCount', 2)
        ->assertSet('schoolsCount', 3)
        ->assertSet('songTitlesCount', 4)
        ->assertSet('digitalProgramsCount', 1)
        ->assertSee('Composers/Arrangers')
        ->assertSee('Ensembles')
        ->assertSee('Programs')
        ->assertSee('Schools')
        ->assertSee('Song Titles')
        ->assertSee('Digital Programs')
        ->assertSee('Users')
        ->assertStatus(200);
});

test('dashboard users count only includes verified users', function () {
    $user = User::factory()->create(); // verified by default
    User::factory()->unverified()->create();

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSet('usersCount', 1);
});

test('dashboard users count excludes assistant accounts', function () {
    $director = User::factory()->create();
    User::factory()->assistant($director)->create();

    $this->actingAs($director);

    Livewire::test(Dashboard::class)
        ->assertSet('usersCount', 1);
});

test('songTitlesCount excludes ai_discovered song titles', function () {
    $user = User::factory()->create();

    SongTitle::factory()->count(2)->create(['origin' => SongTitleOrigin::Performed]);
    SongTitle::factory()->create(['origin' => SongTitleOrigin::AiDiscovered]);

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSet('songTitlesCount', 2);
});

test('dashboard page contains livewire component', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('/dashboard')
        ->assertSeeLivewire(Dashboard::class)
        ->assertOk();
});
