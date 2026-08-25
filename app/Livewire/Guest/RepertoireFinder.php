<?php

declare(strict_types=1);

namespace App\Livewire\Guest;

use App\Enums\RepertoireQuerySource;
use App\Jobs\ProcessRepertoireSearch;
use App\Models\RepertoireQuery;
use App\Services\RepertoireSearchService;
use App\Services\TurnstileVerificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class RepertoireFinder extends Component
{
    public string $query = '';

    public ?string $turnstileToken = null;

    public ?string $aiRequestId = null;

    public bool $aiSearching = false;

    public ?int $aiResultQueryId = null;

    public ?string $aiError = null;

    public function remainingQueries(): int
    {
        $used = RepertoireQuery::guestQueriesFrom((string) request()->ip())->count();
        $limit = (int) config('services.repertoire_search.guest_query_limit');

        return max(0, $limit - $used);
    }

    #[On('turnstile-verified')]
    public function setTurnstileToken(string $token): void
    {
        $this->turnstileToken = $token;
    }

    #[On('turnstile-expired')]
    public function clearTurnstileToken(): void
    {
        $this->turnstileToken = null;
    }

    public function askAi(RepertoireSearchService $service, TurnstileVerificationService $turnstile): void
    {
        $this->validate([
            'query' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $this->aiError = null;

        if ($this->remainingQueries() <= 0) {
            $this->aiError = __("You've used your free searches. Create a free account to keep searching — it's unlimited for directors who share a program.");

            return;
        }

        if (! $turnstile->verify($this->turnstileToken, request()->ip())) {
            $this->addError('turnstileToken', __('Please complete the verification check and try again.'));
            $this->dispatch('reset-turnstile');
            $this->turnstileToken = null;

            return;
        }

        $repertoireQuery = $service->createPendingQuery(
            queryText: $this->query,
            source: RepertoireQuerySource::Welcome,
            ipAddress: request()->ip(),
        );

        $this->aiResultQueryId = null;
        $this->aiRequestId = (string) Str::uuid();
        $this->aiSearching = true;
        $this->turnstileToken = null;
        $this->dispatch('reset-turnstile');

        ProcessRepertoireSearch::dispatch(
            requestId: $this->aiRequestId,
            repertoireQueryId: $repertoireQuery->id,
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

        return view('livewire.guest.repertoire-finder', [
            'aiResult' => $aiResult,
            'remainingQueries' => $this->remainingQueries(),
        ]);
    }
}
