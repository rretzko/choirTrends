<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FeedbackEffortFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $started_at
 * @property Carbon|null $stopped_at
 * @property-read Feedback $feedback
 */
class FeedbackEffort extends Model
{
    /** @use HasFactory<FeedbackEffortFactory> */
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
