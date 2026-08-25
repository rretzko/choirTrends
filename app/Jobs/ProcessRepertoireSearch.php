<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\RepertoireQuery;
use App\Services\RepertoireSearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessRepertoireSearch implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        public string $requestId,
        public int $repertoireQueryId,
        public bool $restrictToOwnCatalog = false,
    ) {}

    public function handle(RepertoireSearchService $service): void
    {
        $repertoireQuery = RepertoireQuery::findOrFail($this->repertoireQueryId);

        $service->process($repertoireQuery, $this->restrictToOwnCatalog);

        Cache::put(
            "repertoire_search_{$this->requestId}",
            ['status' => 'completed', 'repertoire_query_id' => $repertoireQuery->id],
            now()->addMinutes(15)
        );
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('ProcessRepertoireSearch failed permanently', [
            'request_id' => $this->requestId,
            'repertoire_query_id' => $this->repertoireQueryId,
            'error' => $exception?->getMessage(),
        ]);

        RepertoireQuery::where('id', $this->repertoireQueryId)->update([
            'error' => $exception?->getMessage() ?? 'Search failed after all retry attempts.',
        ]);

        Cache::put(
            "repertoire_search_{$this->requestId}",
            ['status' => 'failed', 'error' => $exception?->getMessage() ?? 'Search failed after all retry attempts.'],
            now()->addMinutes(15)
        );
    }
}
