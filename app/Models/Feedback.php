<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeedbackStatus;
use App\Enums\FeedbackType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property FeedbackType $type
 * @property FeedbackStatus $status
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, FeedbackEffort> $efforts
 */
class Feedback extends Model
{
    /** @use HasFactory<\Database\Factories\FeedbackFactory> */
    use HasFactory;

    protected $table = 'feedbacks';

    protected $fillable = [
        'user_id',
        'from_page',
        'type',
        'body',
        'file_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => FeedbackType::class,
            'status' => FeedbackStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(FeedbackComment::class);
    }

    public function efforts(): HasMany
    {
        return $this->hasMany(FeedbackEffort::class);
    }

    public function totalEffortMinutes(): int
    {
        return (int) $this->efforts()
            ->whereNotNull('stopped_at')
            ->get()
            ->sum(fn (FeedbackEffort $effort): int => (int) $effort->started_at->diffInMinutes($effort->stopped_at));
    }
}
