<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VideoVisibility;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property-read User $user
 * @property-read School|null $school
 */
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'school_id',
        'event_name',
        'event_date',
        'director_name',
        'video_path',
        'video_visibility',
        'video_uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'video_visibility' => VideoVisibility::class,
            'video_uploaded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return BelongsToMany<SongTitle, $this, ProgramSongTitlePivot, 'pivot'> */
    public function songTitles(): BelongsToMany
    {
        return $this->belongsToMany(SongTitle::class)
            ->using(ProgramSongTitlePivot::class)
            ->withPivot('ensemble_id', 'sort_order', 'ensemble_sort_order', 'video_path', 'video_visibility', 'video_uploaded_at', 'notes', 'ensemble_director')
            ->orderByPivot('ensemble_sort_order')
            ->orderByPivot('sort_order');
    }

    public function hasVideo(): bool
    {
        return $this->video_path !== null;
    }

    /** @return BelongsToMany<Ensemble, $this, Pivot, 'pivot'> */
    public function ensembles(): BelongsToMany
    {
        return $this->belongsToMany(Ensemble::class, 'program_song_title', 'program_id', 'ensemble_id')
            ->distinct();
    }
}
