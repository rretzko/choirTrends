<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $stopped_at
 * @property-read Feedback $feedback
 */
class FeedbackEffort extends Model
{
    /** @use HasFactory<\Database\Factories\FeedbackEffortFactory> */
    use HasFactory;

    protected $fillable = [
        'feedback_id',
        'started_at',
        'stopped_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'stopped_at' => 'datetime',
        ];
    }

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }
}
