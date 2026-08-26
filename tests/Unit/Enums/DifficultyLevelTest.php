<?php

use App\Enums\DifficultyLevel;

it('numericValue maps difficulty labels to the 1-3 scale', function (DifficultyLevel $level, int $expected) {
    expect($level->numericValue())->toBe($expected);
})->with([
    'easy' => [DifficultyLevel::Easy, 1],
    'moderate' => [DifficultyLevel::Moderate, 2],
    'challenging' => [DifficultyLevel::Challenging, 3],
]);
