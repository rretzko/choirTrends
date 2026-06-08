<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property-read DigitalProgram $digitalProgram
 * @property-read Ensemble|null $ensemble
 * @property-read Collection<int, DigitalProgramHonor> $honors
 */
class DigitalProgramRoster extends Model
{
    /** @var list<string> */
    public const VOICE_PARTS = [
        'Soprano',
        'Soprano I',
        'Soprano II',
        'Alto',
        'Alto I',
        'Alto II',
        'Tenor',
        'Tenor I',
        'Tenor II',
        'Baritone',
        'Bass',
        'Bass I',
        'Bass II',
        'Treble',
        'Cambiata',
        'Unison',
        'Other',
    ];

    protected $fillable = [
        'digital_program_id',
        'ensemble_id',
        'voice_part',
        'student_name',
        'sort_order',
    ];

    public function digitalProgram(): BelongsTo
    {
        return $this->belongsTo(DigitalProgram::class);
    }

    public function ensemble(): BelongsTo
    {
        return $this->belongsTo(Ensemble::class);
    }

    public function honors(): BelongsToMany
    {
        return $this->belongsToMany(
            DigitalProgramHonor::class,
            'digital_program_roster_honor',
            'digital_program_roster_id',
            'digital_program_honor_id'
        );
    }
}
