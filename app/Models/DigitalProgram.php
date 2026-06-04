<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DigitalProgramFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property-read User $user
 * @property-read Program|null $program
 * @property-read Collection<int, DigitalProgramRoster> $rosters
 * @property-read Collection<int, DigitalProgramHonor> $honors
 * @property-read Collection<int, DigitalProgramSongSetting> $songSettings
 */
class DigitalProgram extends Model
{
    /** @use HasFactory<DigitalProgramFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'program_id',
        'slug',
        'theme',
        'is_published',
        'welcome_message',
        'acknowledgments',
        'sponsor_text',
        'intermission_after_ensemble',
        'print_orientation',
        'student_names_acknowledged',
        'lyrics_copyright_acknowledged',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $digitalProgram): void {
            if (empty($digitalProgram->slug)) {
                do {
                    $slug = Str::random(8);
                } while (self::query()->where('slug', $slug)->exists());

                $digitalProgram->slug = $slug;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'student_names_acknowledged' => 'boolean',
            'lyrics_copyright_acknowledged' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function rosters(): HasMany
    {
        return $this->hasMany(DigitalProgramRoster::class)->orderBy('sort_order');
    }

    public function honors(): HasMany
    {
        return $this->hasMany(DigitalProgramHonor::class)->orderBy('sort_order');
    }

    public function songSettings(): HasMany
    {
        return $this->hasMany(DigitalProgramSongSetting::class);
    }

    public function rosterForEnsemble(?int $ensembleId): HasMany
    {
        return $this->hasMany(DigitalProgramRoster::class)
            ->where('ensemble_id', $ensembleId)
            ->orderBy('sort_order');
    }

    public function honorsForEnsemble(?int $ensembleId): HasMany
    {
        return $this->hasMany(DigitalProgramHonor::class)
            ->where('ensemble_id', $ensembleId)
            ->orderBy('sort_order');
    }
}
