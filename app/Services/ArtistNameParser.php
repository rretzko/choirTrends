<?php

declare(strict_types=1);

namespace App\Services;

class ArtistNameParser
{
    /**
     * Parse an artist name into its constituent parts.
     *
     * @return array{artist_name: string, artist_first_name: string|null, artist_last_name: string|null}
     */
    public function parse(string $name): array
    {
        $name = trim($name);

        // Remove arranger abbreviation prefixes (case-insensitive)
        // Matches: "arr ", "arr. ", "arr." at the start of the string
        $name = preg_replace('/^arr(?:\.|\s)\s*/i', '', $name);
        $name = trim($name);

        // Check if the name contains a space
        if (! str_contains($name, ' ')) {
            // Single word name (e.g., "Traditional")
            return [
                'artist_name' => $name,
                'artist_first_name' => null,
                'artist_last_name' => $name,
            ];
        }

        // Detect multiple artists: & separator, "and" as a word, or comma-separated names
        if (preg_match('/\s&\s|\band\b|,\s/', $name)) {
            return [
                'artist_name' => $name,
                'artist_first_name' => null,
                'artist_last_name' => $name,
            ];
        }

        foreach (self::COMPOUND_SURNAMES as $compoundSurname) {
            if (preg_match('/^(.+)\s+('.preg_quote($compoundSurname, '/').')$/iu', $name, $matches)) {
                return [
                    'artist_name' => $name,
                    'artist_first_name' => trim($matches[1]),
                    'artist_last_name' => $matches[2],
                ];
            }
        }

        // Split the name into parts
        $parts = preg_split('/\s+/u', $name) ?: [$name];

        // Last part is the last name, everything before it is the first name
        $lastName = (string) array_pop($parts);

        // A capitalized prefix ("David Von Kampen", "Anthony De Rosa") belongs to the surname,
        // per English-language cataloging. Lowercase European particles ("Orlando di Lasso",
        // "Ludwig van Beethoven") stay with the given name so the name files under the main surname.
        while (count($parts) > 1 && in_array(end($parts), self::CAPITALIZED_SURNAME_PREFIXES, true)) {
            $lastName = array_pop($parts).' '.$lastName;
        }

        $firstName = implode(' ', $parts);

        return [
            'artist_name' => $name,
            'artist_first_name' => $firstName,
            'artist_last_name' => $lastName,
        ];
    }

    /**
     * Surname prefixes that are part of the surname when capitalized.
     *
     * @var list<string>
     */
    private const CAPITALIZED_SURNAME_PREFIXES = ['Von', 'Van', 'Vander', 'Der', 'Den', 'De', 'Di', 'Da', 'Del', 'Della', 'Du', 'La', 'Le', 'St.', 'Ten', 'Ter'];

    /**
     * Two-word surnames with no prefix, which cannot be detected from capitalization.
     *
     * @var list<string>
     */
    private const COMPOUND_SURNAMES = ['Vaughan Williams', 'Lloyd Webber'];
}
