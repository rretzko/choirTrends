<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\RepertoireQuery;
use App\Services\RepertoireResultsExporter;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Lets the searcher download the current AI repertoire search results as TXT, CSV, or PDF.
 *
 * Requires the using component to expose a public `?int $aiResultQueryId` property.
 */
trait DownloadsRepertoireResults
{
    public function downloadResults(string $format, RepertoireResultsExporter $exporter): ?StreamedResponse
    {
        if (! in_array($format, RepertoireResultsExporter::FORMATS, true) || ! $this->aiResultQueryId) {
            return null;
        }

        $repertoireQuery = RepertoireQuery::find($this->aiResultQueryId);

        if (! $repertoireQuery || ! $this->ownsRepertoireQuery($repertoireQuery)) {
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
