<?php

use App\Models\School;

it('generates abbreviation from school name', function (string $name, string $expected) {
    expect(School::guessAbbreviation($name))->toBe($expected);
})->with([
    'simple two words' => ['Lincoln High', 'LH'],
    'three words' => ['Central Valley High', 'CVH'],
    'skips of' => ['University of Michigan', 'UM'],
    'skips the' => ['The Ohio State University', 'OSU'],
    'skips and' => ['Arts and Sciences Academy', 'ASA'],
    'skips at' => ['School at the Crossroads', 'SC'],
    'skips in' => ['Academy in the Hills', 'AH'],
    'skips for' => ['Center for the Arts', 'CA'],
    'single word' => ['Harvard', 'H'],
    'multiple skip words' => ['School of the Arts and Sciences', 'SAS'],
    'empty string' => ['', ''],
]);
