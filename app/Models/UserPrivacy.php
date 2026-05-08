<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserPrivacyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPrivacy extends Model
{
    /** @use HasFactory<UserPrivacyFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'school',
        'ensemble_name',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'boolean',
            'school' => 'boolean',
            'ensemble_name' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
