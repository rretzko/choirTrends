<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\RepertoireQuery;
use App\Services\RepertoireResultsExporter;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shows and downloads (TXT, CSV, or PDF) the current AI repertoire search results,
 * limited to the person who ran the search.
 *
 * Requires the using component to expose a public `?int $aiResultQueryId` property.
 */
trait ManagesRepertoireResults
{
    /**
     * The current search result, or null when there is none or it belongs to someone else.
     */
    public function ownedAiResult(): ?RepertoireQuery
    {
        if (! $this->aiResultQueryId) {
            return null;
        }

        $repertoireQuery = RepertoireQuery::find($this->aiResultQueryId);

        return $repertoireQuery && $this->ownsRepertoireQuery($repertoireQuery) ? $repertoireQuery : null;
    }

    public function downloadResults(string $format, RepertoireResultsExporter $exporter): ?StreamedResponse
    {
        if (! in_array($format, RepertoireResultsExporter::FORMATS, true)) {
            return null;
        }

        $repertoireQuery = $this->ownedAiResult();

        if (! $repertoireQuery) {
            return null;
        }

        $contents = $exporter->render($repertoireQuery, $format);

        return response()->streamDownload(
            function () use ($contents): void {
                echo $contents;
            },
            $exporter->filename($repertoireQuery, $format),
            ['Content-Type' => $exporter->contentType($format)],
        );
    }

    private function ownsRepertoireQuery(RepertoireQuery $repertoireQuery): bool
    {
        if (Auth::check()) {
            return $repertoireQuery->user_id === Auth::id();
        }

        return $repertoireQuery->user_id === null
            && $repertoireQuery->ip_address === request()->ip();
    }
}
