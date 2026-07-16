<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RepresentationStatus;
use Database\Factories\ArtistFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $artist_name
 */
class Artist extends Model
{
    /** @use HasFactory<ArtistFactory> */
    use HasFactory;

    protected $fillable = [
        'artist_name',
        'artist_first_name',
        'artist_last_name',
    ];

    /**
     * @return Attribute<string, string>
     */
    protected function artistName(): Attribute
    {
        return Attribute::make(
            set: function (string $value): string {
                // Remove arranger abbreviation prefixes (case-insensitive)
                // Matches: "arr ", "arr. ", "arr." at the start of the string
                $cleaned = preg_replace('/^arr(?:\.|\s)\s*/i', '', $value);

                return trim($cleaned);
            },
        );
    }

    public function representations(): HasMany
    {
        return $this->hasMany(ArtistRepresentation::class);
    }

    public function approvedRepresentations(): HasMany
    {
        return $this->representations()->where('status', RepresentationStatus::Approved);
    }
}
