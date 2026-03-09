<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuickTipStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property QuickTipStatus $status
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 */
class QuickTip extends Model
{
    /** @use HasFactory<\Database\Factories\QuickTipFactory> */
    use HasFactory;

    protected $fillable = [
        'sort_order',
        'header',
        'introduction',
        'tip',
        'footer',
        'call_to_action',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuickTipStatus::class,
            'sort_order' => 'integer',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('emailed_at', 'viewed_at')
            ->withCasts(['emailed_at' => 'datetime', 'viewed_at' => 'datetime']);
    }

    public function resolvedFooter(): string
    {
        return $this->footer ?? config('quicktip.default_footer');
    }

    public function resolvedCallToAction(): string
    {
        return $this->call_to_action ?? config('quicktip.default_call_to_action');
    }
}
