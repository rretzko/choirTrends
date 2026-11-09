<?php

declare(strict_types=1);

namespace App\Services;

/**
 * One anonymized row of a public composer or arranger ranking.
 */
final readonly class TrendingArtist
{
    public function __construct(
        public int $id,
        public string $name,
        public string $sortKey,
        public string $url,
        public int $schoolCount,
        public int $pieceCount,
        public int $performanceCount,
    ) {}
}
