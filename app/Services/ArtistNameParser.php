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

        // Split the name into parts
        $parts = explode(' ', $name);

        // First part is the first name, rest is the last name
        $firstName = array_shift($parts);
        $lastName = implode(' ', $parts);

        return [
            'artist_name' => $name,
            'artist_first_name' => $firstName,
            'artist_last_name' => $lastName,
        ];
    }
}
