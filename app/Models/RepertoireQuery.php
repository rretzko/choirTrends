<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RepertoireQuerySource;
use Database\Factories\RepertoireQueryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $user_id
 * @property string|null $ip_address
 * @property RepertoireQuerySource $source
 * @property string $query_text
 * @property array<string, mixed>|null $response
 * @property string|null $error
 */
class RepertoireQuery extends Model
{
    /** @use HasFactory<RepertoireQueryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'source',
        'query_text',
        'response',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'source' => RepertoireQuerySource::class,
            'response' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<RepertoireQuery>  $query
     * @return Builder<RepertoireQuery>
     */
    public function scopeGuestQueriesFrom(Builder $query, string $ipAddress): Builder
    {
        return $query->whereNull('user_id')->where('ip_address', $ipAddress);
    }
}
