<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuthorshipType;
use App\Enums\SongTitleOrigin;
use Database\Factories\SongTitleFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * @property-read ProgramSongTitlePivot|null $pivot
 * @property-read Artist|null $composer
 * @property-read Artist|null $arranger
 * @property-read int $performed_count
 * @property-read Collection<int, SongTitleDescription> $descriptions
 * @property-read Collection<int, SongTitleTag> $tags
 * @property-read Collection<int, SongTitleDifficultyObservation> $difficultyObservations
 * @property SongTitleOrigin $origin
 */
class SongTitle extends Model
{
    /** @use HasFactory<SongTitleFactory> */
    use HasFactory;

    protected $fillable = [
        'song_title',
        'composer_id',
        'arranger_id',
        'origin',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'origin' => SongTitleOrigin::class,
        ];
    }

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

    public function difficultyObservations(): HasMany
    {
        return $this->hasMany(SongTitleDifficultyObservation::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(SongTitleTag::class);
    }

    public function descriptions(): HasMany
    {
        return $this->hasMany(SongTitleDescription::class);
    }

    /**
     * The one description to surface in the UI when multiple authorship types exist,
     * preferring publisher-cited copy over user-written over the AI fallback.
     */
    public function preferredDescription(): ?SongTitleDescription
    {
        foreach ([AuthorshipType::Publisher, AuthorshipType::User, AuthorshipType::Ai] as $type) {
            $description = $this->descriptions->firstWhere('authorship_type', $type);

            if ($description) {
                return $description;
            }
        }

        return null;
    }

    /**
     * Average of the per-voice-part average difficulty (1-3), across parts that have
     * at least one observation. Not a flat average of every raw row, so a part with
     * few observations isn't drowned out by a part that gets asked about more often.
     */
    public function overallDifficulty(): ?float
    {
        $partAverages = DB::table('song_title_difficulty_observations')
            ->where('song_title_id', $this->id)
            ->selectRaw('voice_part, AVG(difficulty_value) as part_average')
            ->groupBy('voice_part')
            ->pluck('part_average');

        if ($partAverages->isEmpty()) {
            return null;
        }

        return round((float) $partAverages->average(), 2);
    }
}
