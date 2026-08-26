<?php

use App\Enums\AuthorshipType;
use App\Enums\DifficultyLevel;
use App\Enums\VoicePart;
use App\Models\SongTitleDifficultyObservation;

it('casts voice_part, difficulty_label, and authorship_type to their enums', function () {
    $observation = SongTitleDifficultyObservation::factory()->create([
        'voice_part' => 'alto',
        'difficulty_label' => 'moderate',
        'authorship_type' => 'publisher',
    ]);

    expect($observation->voice_part)->toBe(VoicePart::Alto)
        ->and($observation->difficulty_label)->toBe(DifficultyLevel::Moderate)
        ->and($observation->authorship_type)->toBe(AuthorshipType::Publisher);
});
