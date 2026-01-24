<?php

use App\Livewire\Dashboard;
use App\Models\Artist;
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
    Program::factory()->count(2)->for($user)->for($schools->first())->create();
    SongTitle::factory()->count(4)->create();

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSet('artistsCount', 5)
        ->assertSet('ensemblesCount', 3)
        ->assertSet('programsCount', 2)
        ->assertSet('schoolsCount', 3)
        ->assertSet('songTitlesCount', 4)
        ->assertSee('Artists')
        ->assertSee('Ensembles')
        ->assertSee('Programs')
        ->assertSee('Schools')
        ->assertSee('Song Titles')
        ->assertSee('Users')
        ->assertStatus(200);
});

test('dashboard page contains livewire component', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('/dashboard')
        ->assertSeeLivewire(Dashboard::class)
        ->assertOk();
});
