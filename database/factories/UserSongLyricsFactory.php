<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SongTitle;
use App\Models\User;
use App\Models\UserSongLyrics;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserSongLyrics>
 */
class UserSongLyricsFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'song_title_id' => SongTitle::factory(),
            'content' => $this->faker->paragraphs(3, true),
            'source' => 'manual',
        ];
    }
}
