<?php

declare(strict_types=1);

namespace App\Services;

/**
 * One anonymized row of a public piece ranking. URLs are null when the linked
 * page is not public (e.g. a placeholder composer such as "Traditional").
 */
final readonly class TrendingPiece
{
    public function __construct(
        public int $id,
        public string $title,
        public string $url,
        public ?string $composer,
        public ?string $composerUrl,
        public ?string $arranger,
        public ?string $arrangerUrl,
        public int $schoolCount,
        public int $performanceCount,
    ) {}
}
