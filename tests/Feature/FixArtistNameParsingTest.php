<?php

declare(strict_types=1);

use App\Models\Artist;

it('fixes artists with middle initials in the last name', function () {
    $artist = Artist::factory()->create([
        'artist_name' => 'Glenda E. Franklin',
        'artist_first_name' => 'Glenda',
        'artist_last_name' => 'E. Franklin',
    ]);

    $this->artisan('app:fix-artist-name-parsing')
        ->assertSuccessful();

    $artist->refresh();
    expect($artist->artist_first_name)->toBe('Glenda E.');
    expect($artist->artist_last_name)->toBe('Franklin');
});

it('fixes artists with middle names in the last name', function () {
    $artist = Artist::factory()->create([
        'artist_name' => 'Johann Sebastian Bach',
        'artist_first_name' => 'Johann',
        'artist_last_name' => 'Sebastian Bach',
    ]);

    $this->artisan('app:fix-artist-name-parsing')
        ->assertSuccessful();

    $artist->refresh();
    expect($artist->artist_first_name)->toBe('Johann Sebastian');
    expect($artist->artist_last_name)->toBe('Bach');
});

it('does not modify correctly parsed two-part names', function () {
    $artist = Artist::factory()->create([
        'artist_name' => 'Sheena Phillips',
        'artist_first_name' => 'Sheena',
        'artist_last_name' => 'Phillips',
    ]);

    $this->artisan('app:fix-artist-name-parsing')
        ->assertSuccessful();

    $artist->refresh();
    expect($artist->artist_first_name)->toBe('Sheena');
    expect($artist->artist_last_name)->toBe('Phillips');
});

it('skips collaborative artists with null first name', function () {
    Artist::factory()->create([
        'artist_name' => 'Alice Parker & Robert Shaw',
        'artist_first_name' => null,
        'artist_last_name' => 'Alice Parker & Robert Shaw',
    ]);

    $this->artisan('app:fix-artist-name-parsing')
        ->expectsOutputToContain('No artists need fixing')
        ->assertSuccessful();
});
