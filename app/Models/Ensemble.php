<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EnsembleType;
use Database\Factories\EnsembleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property EnsembleType $type
 * @property-read School|null $school
 */
class Ensemble extends Model
{
    /** @use HasFactory<EnsembleFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id',
        'ensemble_name',
        'type',
        'a_cappella',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EnsembleType::class,
            'a_cappella' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
