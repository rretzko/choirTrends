<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read DigitalProgram $digitalProgram
 * @property-read SongTitle $songTitle
 */
class DigitalProgramSongSetting extends Model
{
    protected $fillable = [
        'digital_program_id',
        'song_title_id',
        'show_lyrics',
    ];

    protected function casts(): array
    {
        return [
            'show_lyrics' => 'boolean',
        ];
    }

    public function digitalProgram(): BelongsTo
    {
        return $this->belongsTo(DigitalProgram::class);
    }

    public function songTitle(): BelongsTo
    {
        return $this->belongsTo(SongTitle::class);
    }
}
