<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\RepertoireQuerySource;
use App\Jobs\ProcessRepertoireSearch;
use App\Livewire\Concerns\ChecksProgramCompliance;
use App\Models\RepertoireQuery;
use App\Services\ProgramComplianceService;
use App\Services\RepertoireSearchService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

class RepertoireFinder extends Component
{
    use ChecksProgramCompliance;

    public string $query = '';

    public ?string $aiRequestId = null;

    public bool $aiSearching = false;

    public ?int $aiResultQueryId = null;

    public ?string $aiError = null;

    /**
     * Null means unlimited (uploaded a program within the last 6 months).
     */
    public function remainingQueries(): ?int
    {
        $recentUpload = app(ProgramComplianceService::class)->checkRecentUpload(Auth::user());

        if ($recentUpload['isRecent']) {
            return null;
        }

        $used = RepertoireQuery::query()
            ->where('user_id', Auth::id())
            ->where('source', RepertoireQuerySource::Dashboard)
            ->when($recentUpload['lastUploadDate'], fn ($q) => $q->where('created_at', '>=', $recentUpload['lastUploadDate']))
            ->count();

        $limit = (int) config('services.repertoire_search.dashboard_stale_query_limit');

        return max(0, $limit - $used);
    }

    public function askAi(RepertoireSearchService $service): void
    {
        $this->validate([
            'query' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $this->aiError = null;

        $remaining = $this->remainingQueries();

        if ($remaining !== null && $remaining <= 0) {
            $this->aiError = __('You have used your queries for now. Upload a program to restore unlimited access.');

            return;
        }

        $repertoireQuery = $service->createPendingQuery(
            queryText: $this->query,
            source: RepertoireQuerySource::Dashboard,
            user: Auth::user(),
        );

        $this->aiResultQueryId = null;
        $this->aiRequestId = (string) Str::uuid();
        $this->aiSearching = true;

        ProcessRepertoireSearch::dispatch(
            requestId: $this->aiRequestId,
            repertoireQueryId: $repertoireQuery->id,
            restrictToOwnCatalog: ! $this->canViewAll(),
        );
    }

    public function checkAiSearchStatus(): void
    {
        if (! $this->aiSearching || ! $this->aiRequestId) {
            return;
        }

        $status = Cache::get("repertoire_search_{$this->aiRequestId}");

        if (! $status) {
            return;
        }

        $this->aiSearching = false;

        if ($status['status'] === 'completed') {
            $this->aiResultQueryId = $status['repertoire_query_id'];
        } else {
            $this->aiError = $status['error'] ?? __('The search failed. Please try again.');
        }
    }

    public function resetAiSearch(): void
    {
        $this->reset(['query', 'aiRequestId', 'aiSearching', 'aiResultQueryId', 'aiError']);
    }

    public function render(): View
    {
        $aiResult = $this->aiResultQueryId ? RepertoireQuery::find($this->aiResultQueryId) : null;
        $recentUpload = app(ProgramComplianceService::class)->checkRecentUpload(Auth::user());

        return view('livewire.dashboard.repertoire-finder', [
            'aiResult' => $aiResult,
            'remainingQueries' => $this->remainingQueries(),
            'isRecentUpload' => $recentUpload['isRecent'],
            'lastUploadDate' => $recentUpload['lastUploadDate'],
        ]);
    }
}
