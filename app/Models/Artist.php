<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RepresentationStatus;
use Database\Factories\ArtistFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property string $artist_name
 * @property string|null $artist_first_name
 * @property string|null $artist_last_name
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

    /**
     * URL slug for public pages; the id in the URL is authoritative, so collisions are harmless.
     */
    public static function slugFor(string $artistName): string
    {
        return Str::slug($artistName) ?: 'artist';
    }

    public function publicSlug(): string
    {
        return self::slugFor($this->artist_name);
    }

    /**
     * Library-style "Last, First" key for alphabetizing. Collaborations stored as a
     * single name ("Adam and Matt Podd", "Alice Parker and Robert Shaw") file under the
     * first person's surname, or the shared family name when the first person is a
     * first name only; mononyms ("Laufey") sort as written.
     */
    public static function sortKeyFor(string $artistName, ?string $firstName, ?string $lastName): string
    {
        $firstName = trim((string) $firstName);
        $lastName = trim((string) $lastName);

        if ($firstName !== '' && $lastName !== '') {
            return self::normalizeSortKey($lastName.' '.$firstName);
        }

        $people = preg_split('/\s*(?:&|\/|,|\band\b)\s*/iu', trim($artistName), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($people) > 1) {
            $firstPersonWords = preg_split('/\s+/u', $people[0]) ?: [];
            $lastPersonWords = preg_split('/\s+/u', (string) end($people)) ?: [];
            $sharesFamilyName = count($firstPersonWords) === 1 && count($lastPersonWords) > 1;
            $surname = $sharesFamilyName ? end($lastPersonWords) : end($firstPersonWords);

            return self::normalizeSortKey($surname.' '.$artistName);
        }

        return self::normalizeSortKey($artistName);
    }

    public function sortKey(): string
    {
        return self::sortKeyFor($this->artist_name, $this->artist_first_name, $this->artist_last_name);
    }

    private static function normalizeSortKey(string $value): string
    {
        return mb_strtolower(Str::ascii($value));
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
