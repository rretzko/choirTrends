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

it('handles names with middle names or initials', function () {
    $parser = new ArtistNameParser;

    $result = $parser->parse('Johann Sebastian Bach');

    expect($result)->toBe([
        'artist_name' => 'Johann Sebastian Bach',
        'artist_first_name' => 'Johann Sebastian',
        'artist_last_name' => 'Bach',
    ]);
});

it('keeps middle initials with the first name', function () {
    $parser = new ArtistNameParser;

    $result = $parser->parse('Glenda E. Franklin');

    expect($result)->toBe([
        'artist_name' => 'Glenda E. Franklin',
        'artist_first_name' => 'Glenda E.',
        'artist_last_name' => 'Franklin',
    ]);
});

it('treats ampersand-separated names as a collaborative artist', function () {
    $parser = new ArtistNameParser;

    $result = $parser->parse('Alice Parker & Robert Shaw');

    expect($result)->toBe([
        'artist_name' => 'Alice Parker & Robert Shaw',
        'artist_first_name' => null,
        'artist_last_name' => 'Alice Parker & Robert Shaw',
    ]);
});

it('treats "and"-separated names as a collaborative artist', function () {
    $parser = new ArtistNameParser;

    $result = $parser->parse('Joe Beal and Jim Boothe');

    expect($result)->toBe([
        'artist_name' => 'Joe Beal and Jim Boothe',
        'artist_first_name' => null,
        'artist_last_name' => 'Joe Beal and Jim Boothe',
    ]);
});

it('treats comma-separated names as a collaborative artist', function () {
    $parser = new ArtistNameParser;

    $result = $parser->parse('George Gordy, Allen Story, and Anna Gordy Gaye');

    expect($result)->toBe([
        'artist_name' => 'George Gordy, Allen Story, and Anna Gordy Gaye',
        'artist_first_name' => null,
        'artist_last_name' => 'George Gordy, Allen Story, and Anna Gordy Gaye',
    ]);
});

it('treats last-name-only pairs as a collaborative artist', function () {
    $parser = new ArtistNameParser;

    $result = $parser->parse('Rodgers & Hammerstein');

    expect($result)->toBe([
        'artist_name' => 'Rodgers & Hammerstein',
        'artist_first_name' => null,
        'artist_last_name' => 'Rodgers & Hammerstein',
    ]);
});

it('treats initials with ampersand as a collaborative artist', function () {
    $parser = new ArtistNameParser;

    $result = $parser->parse('F. Slay, Jr & B. Crewe');

    expect($result)->toBe([
        'artist_name' => 'F. Slay, Jr & B. Crewe',
        'artist_first_name' => null,
        'artist_last_name' => 'F. Slay, Jr & B. Crewe',
    ]);
});
