<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StatSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatSnapshot extends Model
{
    /** @use HasFactory<StatSnapshotFactory> */
    use HasFactory;

    protected $fillable = [
        'verified_users_count',
        'schools_count',
        'programs_count',
        'song_titles_count',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_users_count' => 'integer',
            'schools_count' => 'integer',
            'programs_count' => 'integer',
            'song_titles_count' => 'integer',
            'captured_at' => 'datetime',
        ];
    }
}
