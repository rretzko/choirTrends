<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SongTitle extends Model
{
    /** @use HasFactory<\Database\Factories\SongTitleFactory> */
    use HasFactory;

    protected $fillable = [
        'song_title',
    ];
}
