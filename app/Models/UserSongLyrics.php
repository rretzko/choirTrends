<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $song_title_id
 * @property string $content
 * @property string $source
 */
class UserSongLyrics extends Model
{
    /** @use HasFactory<\Database\Factories\UserSongLyricsFactory> */
    use HasFactory;

    protected $table = 'user_song_lyrics';

    protected $fillable = [
        'user_id',
        'song_title_id',
        'content',
        'source',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function songTitle(): BelongsTo
    {
        return $this->belongsTo(SongTitle::class);
    }
}
