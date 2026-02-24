<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property-read User $user
 * @property-read School|null $school
 */
class Program extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'school_id',
        'event_name',
        'event_date',
        'director_name',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
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

    public function songTitles(): BelongsToMany
    {
        return $this->belongsToMany(SongTitle::class)
            ->withPivot('ensemble_id', 'sort_order')
            ->orderByPivot('sort_order');
    }

    public function ensembles(): BelongsToMany
    {
        return $this->belongsToMany(Ensemble::class, 'program_song_title', 'program_id', 'ensemble_id')
            ->distinct();
    }
}
