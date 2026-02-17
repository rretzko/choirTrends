<?php

declare(strict_types=1);

use App\Models\Artist;

it('fixes collaborative artists with ampersand separator', function () {
    $artist = Artist::factory()->create([
        'artist_name' => 'Alice Parker & Robert Shaw',
        'artist_first_name' => 'Alice',
        'artist_last_name' => 'Parker & Robert Shaw',
    ]);

    $this->artisan('app:fix-collaborative-artists')
        ->assertSuccessful();

    $artist->refresh();
    expect($artist->artist_first_name)->toBeNull();
    expect($artist->artist_last_name)->toBe('Alice Parker & Robert Shaw');
});

it('fixes collaborative artists with "and" separator', function () {
    $artist = Artist::factory()->create([
        'artist_name' => 'Joe Beal and Jim Boothe',
        'artist_first_name' => 'Joe',
        'artist_last_name' => 'Beal and Jim Boothe',
    ]);

    $this->artisan('app:fix-collaborative-artists')
        ->assertSuccessful();

    $artist->refresh();
    expect($artist->artist_first_name)->toBeNull();
    expect($artist->artist_last_name)->toBe('Joe Beal and Jim Boothe');
});

it('fixes collaborative artists with comma separator', function () {
    $artist = Artist::factory()->create([
        'artist_name' => 'George Gordy, Allen Story, and Anna Gordy Gaye',
        'artist_first_name' => 'George',
        'artist_last_name' => 'Gordy, Allen Story, and Anna Gordy Gaye',
    ]);

    $this->artisan('app:fix-collaborative-artists')
        ->assertSuccessful();

    $artist->refresh();
    expect($artist->artist_first_name)->toBeNull();
    expect($artist->artist_last_name)->toBe('George Gordy, Allen Story, and Anna Gordy Gaye');
});

it('does not modify single-person artists', function () {
    $artist = Artist::factory()->create([
        'artist_name' => 'Sheena Phillips',
        'artist_first_name' => 'Sheena',
        'artist_last_name' => 'Phillips',
    ]);

    $this->artisan('app:fix-collaborative-artists')
        ->assertSuccessful();

    $artist->refresh();
    expect($artist->artist_first_name)->toBe('Sheena');
    expect($artist->artist_last_name)->toBe('Phillips');
});

it('skips artists already correctly set to null first name', function () {
    $artist = Artist::factory()->create([
        'artist_name' => 'Rodgers & Hammerstein',
        'artist_first_name' => null,
        'artist_last_name' => 'Rodgers & Hammerstein',
    ]);

    $this->artisan('app:fix-collaborative-artists')
        ->expectsOutputToContain('No collaborative artists need fixing')
        ->assertSuccessful();

    $artist->refresh();
    expect($artist->artist_first_name)->toBeNull();
    expect($artist->artist_last_name)->toBe('Rodgers & Hammerstein');
});
