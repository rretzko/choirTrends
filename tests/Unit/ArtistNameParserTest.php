<?php

declare(strict_types=1);

use App\Services\ArtistNameParser;

it('parses a two-part name correctly', function () {
    $parser = new ArtistNameParser;

    $result = $parser->parse('Sheena Phillips');

    expect($result)->toBe([
        'artist_name' => 'Sheena Phillips',
        'artist_first_name' => 'Sheena',
        'artist_last_name' => 'Phillips',
    ]);
});

it('parses a single-word name correctly', function () {
    $parser = new ArtistNameParser;

    $result = $parser->parse('Traditional');

    expect($result)->toBe([
        'artist_name' => 'Traditional',
        'artist_first_name' => null,
        'artist_last_name' => 'Traditional',
    ]);
});

it('strips arr. prefix before parsing', function () {
    $parser = new ArtistNameParser;

    $result = $parser->parse('arr. Sheena Phillips');

    expect($result)->toBe([
        'artist_name' => 'Sheena Phillips',
        'artist_first_name' => 'Sheena',
        'artist_last_name' => 'Phillips',
    ]);
});

it('strips arr prefix with space before parsing', function () {
    $parser = new ArtistNameParser;

    $result = $parser->parse('arr Sheena Phillips');

    expect($result)->toBe([
        'artist_name' => 'Sheena Phillips',
        'artist_first_name' => 'Sheena',
        'artist_last_name' => 'Phillips',
    ]);
});

it('strips ARR prefix case-insensitively', function () {
    $parser = new ArtistNameParser;

    $result = $parser->parse('ARR. Sheena Phillips');

    expect($result)->toBe([
        'artist_name' => 'Sheena Phillips',
        'artist_first_name' => 'Sheena',
        'artist_last_name' => 'Phillips',
    ]);
});

it('handles multi-part last names', function () {
    $parser = new ArtistNameParser;

    $result = $parser->parse('Johann Sebastian Bach');

    expect($result)->toBe([
        'artist_name' => 'Johann Sebastian Bach',
        'artist_first_name' => 'Johann',
        'artist_last_name' => 'Sebastian Bach',
    ]);
});
