<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ensemble extends Model
{
    /** @use HasFactory<\Database\Factories\EnsembleFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id',
        'ensemble_name',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
