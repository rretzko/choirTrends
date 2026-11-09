<?php

declare(strict_types=1);

namespace App\Services;

/**
 * One anonymized row of the public "most-performed pieces" ranking.
 */
final readonly class TrendingPiece
{
    public function __construct(
        public string $title,
        public ?string $composer,
        public ?string $arranger,
        public int $schoolCount,
        public int $performanceCount,
    ) {}
}
