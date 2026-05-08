<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SongTitle;
use App\Models\User;
use App\Models\UserSongFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserSongFile>
 */
class UserSongFileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory();
        $song = SongTitle::factory();

        return [
            'user_id' => $user,
            'song_title_id' => $song,
            'file_path' => 'sheet-music/1/1/'.$this->faker->uuid().'.pdf',
            'original_filename' => $this->faker->word().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => $this->faker->numberBetween(10000, 5000000),
            'type' => 'sheet_music',
        ];
    }
}
