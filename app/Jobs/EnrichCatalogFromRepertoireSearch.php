<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\RepertoireQuery;
use App\Services\CatalogEnrichmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class EnrichCatalogFromRepertoireSearch implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 3;

    public function __construct(public int $repertoireQueryId) {}

    public function handle(CatalogEnrichmentService $service): void
    {
        $repertoireQuery = RepertoireQuery::findOrFail($this->repertoireQueryId);

        if ($repertoireQuery->error !== null || $repertoireQuery->response === null) {
            return;
        }

        $service->enrich($repertoireQuery);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('EnrichCatalogFromRepertoireSearch failed permanently', [
            'repertoire_query_id' => $this->repertoireQueryId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
