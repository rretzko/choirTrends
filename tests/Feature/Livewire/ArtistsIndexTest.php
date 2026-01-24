<?php

use App\Livewire\Artists\Index;
use App\Models\Artist;
use App\Models\User;
use Livewire\Livewire;

test('artists index page can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('artists.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

test('artists index displays all artists', function () {
    $user = User::factory()->create();
    $artists = Artist::factory()->count(3)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee($artists[0]->artist_name)
        ->assertSee($artists[1]->artist_name)
        ->assertSee($artists[2]->artist_name)
        ->assertStatus(200);
});

test('guests cannot access artists index', function () {
    $this->get(route('artists.index'))
        ->assertRedirect(route('login'));
});
