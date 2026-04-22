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
 * @property string $file_path
 * @property string $original_filename
 * @property string $mime_type
 * @property int $file_size
 * @property string $type
 */
class UserSongFile extends Model
{
    /** @use HasFactory<\Database\Factories\UserSongFileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'song_title_id',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'type',
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
