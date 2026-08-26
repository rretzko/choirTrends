<?php

declare(strict_types=1);

namespace App\Livewire\SongTitles;

use App\Enums\DifficultyLevel;
use App\Enums\EnsembleType;
use App\Enums\RepertoireQuerySource;
use App\Enums\VideoVisibility;
use App\Enums\VoicePart;
use App\Jobs\ProcessRepertoireSearch;
use App\Livewire\Concerns\ChecksProgramCompliance;
use App\Models\RepertoireQuery;
use App\Models\SongTitle;
use App\Services\RepertoireSearchService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use ChecksProgramCompliance;
    use WithPagination;

    public string $mode = 'browse';

    public string $aiQuery = '';

    public ?string $aiRequestId = null;

    public bool $aiSearching = false;

    public ?int $aiResultQueryId = null;

    public ?string $aiError = null;

    public string $filter = 'all';

    public string $programStatus = 'programmed';

    public string $search = '';

    public bool $searchLyrics = false;

    /** @var list<string> */
    public array $ensembleTypeFilter = [];

    /** @var list<string> */
    public array $difficultyFilter = [];

    /** @var list<string> */
    public array $tagFilter = [];

    public string $sortBy = 'song_title';

    public string $sortDirection = 'asc';

    public int $myCount = 0;

    public int $allCount = 0;

    public ?int $videoSongTitleId = null;

    public ?int $videoProgramId = null;

    public string $videoSongName = '';

    public bool $isAudio = false;

    public function sort(string $column): void
    {
        $this->resetPage();

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function toggleEnsembleTypeFilter(string $type): void
    {
        $this->resetPage();

        if (in_array($type, $this->ensembleTypeFilter, true)) {
            $this->ensembleTypeFilter = array_values(array_diff($this->ensembleTypeFilter, [$type]));
        } else {
            $this->ensembleTypeFilter[] = $type;
        }
    }

    public function toggleDifficultyFilter(string $level): void
    {
        $this->resetPage();

        if (in_array($level, $this->difficultyFilter, true)) {
            $this->difficultyFilter = array_values(array_diff($this->difficultyFilter, [$level]));
        } else {
            $this->difficultyFilter[] = $level;
        }
    }

    public function toggleTagFilter(string $tag): void
    {
        $this->resetPage();

        if (in_array($tag, $this->tagFilter, true)) {
            $this->tagFilter = array_values(array_diff($this->tagFilter, [$tag]));
        } else {
            $this->tagFilter[] = $tag;
        }
    }

    public function clearAllFilters(): void
    {
        $this->resetPage();
        $this->programStatus = 'programmed';
        $this->ensembleTypeFilter = [];
        $this->difficultyFilter = [];
        $this->tagFilter = [];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSearchLyrics(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function updatedProgramStatus(): void
    {
        $this->resetPage();
    }

    public function askAi(RepertoireSearchService $service): void
    {
        $this->validate([
            'aiQuery' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $repertoireQuery = $service->createPendingQuery(
            queryText: $this->aiQuery,
            source: RepertoireQuerySource::SongTitles,
            user: Auth::user(),
        );

        $this->aiError = null;
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
            $this->aiError = $status['error'] ?? 'The search failed. Please try again.';
        }
    }

    public function resetAiSearch(): void
    {
        $this->reset(['aiQuery', 'aiRequestId', 'aiSearching', 'aiResultQueryId', 'aiError']);
    }

    public function playVideo(int $songTitleId, int $programId, string $songName, bool $isAudio = false): void
    {
        $this->videoSongTitleId = $songTitleId;
        $this->videoProgramId = $programId;
        $this->videoSongName = $songName;
        $this->isAudio = $isAudio;

        $this->modal('song-video-player')->show();
    }

    /**
     * Build a map of song_title_id => [{program_id, program_name, is_audio}] for songs with viewable videos,
     * scoped to only the given song IDs (the current page) so this stays bounded as the catalog grows.
     *
     * @param  Collection<int, int>  $songTitleIds
     * @return Collection<int|string, array{program_id: int, program_name: string, is_audio: bool}>
     */
    private function getViewableVideoMap(Collection $songTitleIds): Collection
    {
        $userId = Auth::id();

        $rows = DB::table('program_song_title')
            ->join('programs', 'programs.id', '=', 'program_song_title.program_id')
            ->whereIn('program_song_title.song_title_id', $songTitleIds)
            ->whereNotNull('program_song_title.video_path')
            ->where(function ($q) use ($userId) {
                $q->where('programs.user_id', $userId)
                    ->orWhere('program_song_title.video_visibility', VideoVisibility::Public->value);
            })
            ->select('program_song_title.song_title_id', 'programs.id as program_id', 'programs.event_name', 'program_song_title.video_path')
            ->get();

        return $rows->groupBy('song_title_id')->map(function ($items) {
            /** @var object{program_id: int, event_name: string, video_path: string} $row */
            $row = $items->first();

            $ext = strtolower(pathinfo((string) $row->video_path, PATHINFO_EXTENSION));

            return [
                'program_id' => (int) $row->program_id,
                'program_name' => (string) $row->event_name,
                'is_audio' => in_array($ext, ['mp3', 'wav', 'm4a', 'ogg', 'flac', 'aac', 'wma']),
            ];
        });
    }

    /**
     * Build a map of song_title_id => distinct ensemble types that have performed it,
     * scoped to the current My/All filter and to only the given song IDs (the current page)
     * so this stays bounded as the catalog grows.
     *
     * @param  Collection<int, int>  $songTitleIds
     * @return Collection<int|string, Collection<int, EnsembleType>>
     */
    private function getEnsembleTypeMap(Collection $songTitleIds): Collection
    {
        $rows = DB::table('program_song_title')
            ->join('ensembles', 'ensembles.id', '=', 'program_song_title.ensemble_id')
            ->whereIn('program_song_title.song_title_id', $songTitleIds)
            ->when($this->filter === 'my', function ($q) {
                $q->join('programs', 'programs.id', '=', 'program_song_title.program_id')
                    ->where('programs.user_id', Auth::id());
            })
            ->select('program_song_title.song_title_id', 'ensembles.type')
            ->distinct()
            ->get();

        return $rows->groupBy('song_title_id')->map(function ($items) {
            return $items->pluck('type')
                ->map(fn (string $type) => EnsembleType::tryFrom($type))
                ->filter()
                ->unique()
                ->values();
        });
    }

    /**
     * Build a map of song_title_id => difficulty summary. Pass $songTitleIds to scope to only
     * those songs (the current page) so this stays bounded as the catalog grows; omit it to
     * compute across every observed song, e.g. to resolve the difficulty filter's matching IDs.
     *
     * @param  Collection<int, int>|null  $songTitleIds
     * @return Collection<int|string, array{overall: DifficultyLevel, summary: string}>
     */
    private function getDifficultyMap(?Collection $songTitleIds = null): Collection
    {
        $rows = DB::table('song_title_difficulty_observations')
            ->when($songTitleIds !== null, fn ($q) => $q->whereIn('song_title_id', $songTitleIds))
            ->selectRaw('song_title_id, voice_part, AVG(difficulty_value) as part_average')
            ->groupBy('song_title_id', 'voice_part')
            ->get();

        return $rows->groupBy('song_title_id')->map(function (Collection $parts) {
            $byPart = collect(VoicePart::cases())
                ->mapWithKeys(function (VoicePart $part) use ($parts) {
                    $row = $parts->firstWhere('voice_part', $part->value);

                    return [$part->value => $row ? (float) $row->part_average : null];
                })
                ->filter(fn (?float $average) => $average !== null);

            $overall = DifficultyLevel::fromNumericValue((int) round($byPart->average()));

            $summary = $byPart
                ->map(fn (float $average, string $partValue) => VoicePart::from($partValue)->label().': '
                    .DifficultyLevel::fromNumericValue((int) round($average))->label())
                ->implode(' · ');

            return [
                'overall' => $overall,
                'summary' => $summary,
            ];
        });
    }

    public function render(): View
    {
        if (! $this->canViewAll() && $this->filter === 'all') {
            $this->filter = 'my';
        }

        if (! $this->canViewAll() && $this->programStatus !== 'programmed') {
            $this->programStatus = 'programmed';
        }

        // Calculate counts for filters (exclude orphaned song titles with no programs)
        $this->allCount = SongTitle::query()->whereHas('programs')->count();
        $this->myCount = SongTitle::query()
            ->whereHas('programs', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->count();

        $songTitles = collect();
        $viewableVideoMap = collect();

        if ($this->mode === 'browse') {
            $query = SongTitle::query()
                ->with(['composer', 'arranger', 'descriptions', 'tags'])
                ->select('song_titles.*')
                ->leftJoin('artists as composers', 'song_titles.composer_id', '=', 'composers.id')
                ->leftJoin('artists as arrangers', 'song_titles.arranger_id', '=', 'arrangers.id');

            // Add performed count based on filter
            $scopeToMine = function ($q) {
                $q->where('user_id', Auth::id());
            };

            if ($this->filter === 'my') {
                $query->withCount(['programs as performed_count' => $scopeToMine]);
            } else {
                $query->withCount('programs as performed_count');
            }

            match ($this->programStatus) {
                'not_programmed' => $query->whereDoesntHave(
                    'programs',
                    $this->filter === 'my' ? $scopeToMine : null
                ),
                'all' => null,
                default => $query->whereHas(
                    'programs',
                    $this->filter === 'my' ? $scopeToMine : null
                ),
            };

            if ($this->search !== '') {
                $searchTerm = '%'.$this->search.'%';
                $rawSearch = $this->search;
                $includeLyrics = $this->searchLyrics && $this->canViewAll();

                $query->where(function ($q) use ($searchTerm, $rawSearch, $includeLyrics) {
                    $q->where('song_titles.song_title', 'like', $searchTerm)
                        ->orWhere('composers.artist_name', 'like', $searchTerm)
                        ->orWhere('arrangers.artist_name', 'like', $searchTerm);

                    if ($includeLyrics) {
                        $q->orWhereHas('lyrics', function ($lq) use ($searchTerm, $rawSearch) {
                            if (DB::connection()->getDriverName() === 'mysql') {
                                $lq->whereRaw('MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE)', [$rawSearch]);
                            } else {
                                $lq->where('content', 'like', $searchTerm);
                            }
                        });
                    }
                });
            }

            if (! empty($this->ensembleTypeFilter)) {
                $matchingSongTitleIds = DB::table('program_song_title')
                    ->join('ensembles', 'ensembles.id', '=', 'program_song_title.ensemble_id')
                    ->when($this->filter === 'my', function ($q) {
                        $q->join('programs', 'programs.id', '=', 'program_song_title.program_id')
                            ->where('programs.user_id', Auth::id());
                    })
                    ->whereIn('ensembles.type', $this->ensembleTypeFilter)
                    ->pluck('program_song_title.song_title_id');

                $query->whereIn('song_titles.id', $matchingSongTitleIds);
            }

            if (! empty($this->difficultyFilter)) {
                $matchingDifficultyIds = $this->getDifficultyMap()
                    ->filter(fn (array $entry) => in_array($entry['overall']->value, $this->difficultyFilter, true))
                    ->keys();

                $query->whereIn('song_titles.id', $matchingDifficultyIds);
            }

            if (! empty($this->tagFilter)) {
                $matchingTagIds = DB::table('song_title_tags')
                    ->whereIn('tag', $this->tagFilter)
                    ->pluck('song_title_id');

                $query->whereIn('song_titles.id', $matchingTagIds);
            }

            if ($this->sortBy === 'difficulty') {
                $partAverages = DB::table('song_title_difficulty_observations')
                    ->select('song_title_id', 'voice_part')
                    ->selectRaw('AVG(difficulty_value) as part_average')
                    ->groupBy('song_title_id', 'voice_part');

                $overallAverages = DB::query()->fromSub($partAverages, 'part_averages')
                    ->select('song_title_id')
                    ->selectRaw('AVG(part_average) as overall_average')
                    ->groupBy('song_title_id');

                $query->leftJoinSub($overallAverages, 'difficulty_overall', 'difficulty_overall.song_title_id', '=', 'song_titles.id');
            }

            if ($this->sortBy === 'tags') {
                $tagCounts = DB::table('song_title_tags')
                    ->select('song_title_id')
                    ->selectRaw('COUNT(*) as tag_count')
                    ->groupBy('song_title_id');

                $query->leftJoinSub($tagCounts, 'tag_counts', 'tag_counts.song_title_id', '=', 'song_titles.id');
            }

            if (in_array($this->sortBy, ['composer', 'arranger'])) {
                $prefix = $this->sortBy === 'composer' ? 'composers' : 'arrangers';
                $query->orderBy("{$prefix}.artist_last_name", $this->sortDirection)
                    ->orderBy("{$prefix}.artist_first_name", $this->sortDirection);
            } else {
                $sortColumn = match ($this->sortBy) {
                    'performed' => 'performed_count',
                    'difficulty' => 'difficulty_overall.overall_average',
                    'tags' => DB::raw('COALESCE(tag_counts.tag_count, 0)'),
                    default => 'song_titles.song_title',
                };
                $query->orderBy($sortColumn, $this->sortDirection);
            }

            $songTitles = $query->paginate(20);

            $songTitleIds = $songTitles->pluck('id');
            $viewableVideoMap = $this->getViewableVideoMap($songTitleIds);
            $ensembleTypeMap = $this->getEnsembleTypeMap($songTitleIds);
            $difficultyMap = $this->getDifficultyMap($songTitleIds);
        }

        $aiResult = $this->aiResultQueryId ? RepertoireQuery::find($this->aiResultQueryId) : null;

        $availableTags = DB::table('song_title_tags')
            ->select('tag')
            ->distinct()
            ->orderBy('tag')
            ->pluck('tag');

        $activeFilterCount = ($this->programStatus !== 'programmed' ? 1 : 0)
            + (! empty($this->ensembleTypeFilter) ? 1 : 0)
            + (! empty($this->difficultyFilter) ? 1 : 0)
            + (! empty($this->tagFilter) ? 1 : 0);

        return view('livewire.song-titles.index', [
            'songTitles' => $songTitles,
            'viewableVideoMap' => $viewableVideoMap,
            'ensembleTypeMap' => $ensembleTypeMap ?? collect(),
            'difficultyMap' => $difficultyMap ?? collect(),
            'ensembleTypes' => EnsembleType::cases(),
            'difficultyLevels' => DifficultyLevel::cases(),
            'availableTags' => $availableTags,
            'activeFilterCount' => $activeFilterCount,
            'aiResult' => $aiResult,
        ])->layout('components.layouts.app', ['title' => __('Song Titles')]);
    }
}
