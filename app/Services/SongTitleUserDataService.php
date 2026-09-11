<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuthorshipType;
use App\Enums\DifficultyLevel;
use App\Enums\VoicePart;
use App\Models\SongTitle;
use App\Models\SongTitleDifficultyObservation;
use App\Models\SongTitleTag;

class SongTitleUserDataService
{
    /**
     * Set (or clear) the given user's own difficulty rating for a song title.
     *
     * Writes one observation per voice part so the rating rolls up into the
     * community average alongside AI/publisher observations, without ever
     * touching another author's rows.
     */
    public function setDifficulty(SongTitle $songTitle, ?DifficultyLevel $level, int $userId): void
    {
        if (! $level) {
            SongTitleDifficultyObservation::query()
                ->where('song_title_id', $songTitle->id)
                ->where('authorship_type', AuthorshipType::User)
                ->where('authorship_id', $userId)
                ->delete();

            return;
        }

        foreach (VoicePart::cases() as $voicePart) {
            SongTitleDifficultyObservation::updateOrCreate(
                [
                    'song_title_id' => $songTitle->id,
                    'voice_part' => $voicePart,
                    'authorship_type' => AuthorshipType::User,
                    'authorship_id' => $userId,
                ],
                [
                    'difficulty_label' => $level,
                    'difficulty_value' => $level->numericValue(),
                ]
            );
        }
    }

    /**
     * Add a user-authored tag to a song title, skipping creation when a tag
     * with the same text (from any author) already exists.
     */
    public function addTag(SongTitle $songTitle, string $tag, int $userId): void
    {
        $tag = trim($tag);

        if ($tag === '') {
            return;
        }

        $alreadyExists = SongTitleTag::query()
            ->where('song_title_id', $songTitle->id)
            ->whereRaw('LOWER(tag) = ?', [mb_strtolower($tag)])
            ->exists();

        if ($alreadyExists) {
            return;
        }

        SongTitleTag::create([
            'song_title_id' => $songTitle->id,
            'tag' => $tag,
            'authorship_type' => AuthorshipType::User,
            'authorship_id' => $userId,
        ]);
    }
}
