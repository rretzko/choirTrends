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
 * @property-read Collection<int, DigitalProgramRoster> $rosters
 */
class DigitalProgramHonor extends Model
{
    protected $fillable = [
        'digital_program_id',
        'ensemble_id',
        'label',
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

    public function rosters(): BelongsToMany
    {
        return $this->belongsToMany(
            DigitalProgramRoster::class,
            'digital_program_roster_honor',
            'digital_program_honor_id',
            'digital_program_roster_id'
        );
    }
}
