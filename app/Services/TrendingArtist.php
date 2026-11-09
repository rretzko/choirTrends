<?php

declare(strict_types=1);

namespace App\Services;

/**
 * One anonymized row of the public composer or arranger ranking.
 */
final readonly class TrendingArtist
{
    public function __construct(
        public string $name,
        public int $schoolCount,
        public int $pieceCount,
        public int $performanceCount,
    ) {}
}
