<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property-read Collection<int, Ensemble> $ensembles
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Program> $programs
 */
class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory;

    protected $fillable = [
        'school_name',
        'abbreviation',
        'postal_code',
        'geo_state',
        'country',
    ];

    /** @var list<string> */
    private const SKIP_WORDS = ['of', 'the', 'and', 'at', 'in', 'for'];

    /**
     * Generate an abbreviation from a school name by taking the first letter
     * of each significant word (skipping common prepositions/articles).
     */
    public static function guessAbbreviation(string $name): string
    {
        $words = Str::of($name)->explode(' ')->filter();

        $letters = $words
            ->reject(fn (string $word) => in_array(Str::lower($word), self::SKIP_WORDS, true))
            ->map(fn (string $word) => Str::upper(Str::substr($word, 0, 1)));

        return $letters->implode('');
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    public function ensembles(): HasMany
    {
        return $this->hasMany(Ensemble::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
