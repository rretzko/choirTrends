<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $ip_address
 * @property string|null $os
 * @property string|null $browser
 * @property string|null $device
 * @property string|null $viewport
 * @property int $counter
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class UserLogin extends Model
{
    /** @use HasFactory<\Database\Factories\UserLoginFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ip_address',
        'os',
        'browser',
        'device',
        'viewport',
        'counter',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'counter' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
