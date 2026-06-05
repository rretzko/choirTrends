<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SongTitleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read ProgramSongTitlePivot|null $pivot
 * @property-read Artist|null $composer
 * @property-read Artist|null $arranger
 */
class SongTitle extends Model
{
    /** @use HasFactory<SongTitleFactory> */
    use HasFactory;

    protected $fillable = [
        'song_title',
        'composer_id',
        'arranger_id',
    ];

    public function composer(): BelongsTo
    {
        return $this->belongsTo(Artist::class, 'composer_id');
    }

    public function arranger(): BelongsTo
    {
        return $this->belongsTo(Artist::class, 'arranger_id');
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class);
    }

    public function lyrics(): HasMany
    {
        return $this->hasMany(UserSongLyrics::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(UserSongFile::class);
    }
}
