<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SongTitle;
use App\Services\PublicTrendsService;
use App\Services\SongEnrichmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Throwable;

class SongsEnrich extends Command
{
    protected $signature = 'songs:enrich
        {songs?* : Song title ids to enrich}
        {--public-missing : Enrich every public song page that lacks an AI summary, tags or difficulty}
        {--refresh : Replace earlier AI-written summary, tags and difficulty instead of only filling gaps}
        {--limit=20 : Maximum number of songs to process in this run}
        {--dry-run : List the songs that would be enriched without calling the AI}';

    protected $description = 'Research songs with Claude (web search) and record the AI summary, tags and difficulty shown on public song pages';

    public function handle(SongEnrichmentService $enricher, PublicTrendsService $trends): int
    {
        $songs = $this->selectSongs($enricher, $trends);

        if ($songs === null) {
            return self::FAILURE;
        }

        if ($songs->isEmpty()) {
            $this->info('No songs need enrichment.');

            return self::SUCCESS;
        }

        foreach ($songs as $song) {
            $missing = collect($enricher->missingData($song))->filter()->keys()->join(', ');
            $this->line(sprintf('  #%-5d %-50s %s', $song->id, mb_strimwidth($song->song_title, 0, 48, '…'), $this->option('refresh') ? 'refresh all AI data' : "missing: {$missing}"));
        }

        if ($this->option('dry-run')) {
            $this->warn("Dry run — {$songs->count()} song(s) would be researched (one AI request with web search each). Nothing was changed.");

            return self::SUCCESS;
        }

        $succeeded = 0;

        foreach ($songs as $song) {
            try {
                $result = $enricher->enrich($song, (bool) $this->option('refresh'));
            } catch (Throwable $exception) {
                $this->error("  #{$song->id} {$song->song_title}: failed — {$exception->getMessage()}");

                continue;
            }

            if (! $result['identified']) {
                $this->warn("  #{$song->id} {$song->song_title}: not confidently identified, nothing recorded. {$result['notes']}");

                continue;
            }

            $recorded = $result['recorded'] !== [] ? implode(', ', $result['recorded']) : 'nothing new';
            $this->info("  #{$song->id} {$song->song_title}: recorded {$recorded}. {$result['notes']}");
            $succeeded++;
        }

        $trends->flush();
        $this->info("Done: {$succeeded} of {$songs->count()} song(s) enriched.");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, SongTitle>|null null when the arguments are invalid
     */
    private function selectSongs(SongEnrichmentService $enricher, PublicTrendsService $trends): ?Collection
    {
        /** @var list<string> $songArguments */
        $songArguments = (array) $this->argument('songs');
        $ids = array_values(array_unique(array_map('intval', $songArguments)));
        $limit = max(1, (int) $this->option('limit'));

        if ($ids === [] && ! $this->option('public-missing')) {
            $this->error('Pass song ids, or --public-missing to enrich public song pages that lack data.');

            return null;
        }

        if ($ids !== []) {
            $songs = SongTitle::query()->whereKey($ids)->get();
            $missingIds = array_diff($ids, $songs->modelKeys());

            if ($missingIds !== []) {
                $this->error('Song(s) not found: '.implode(', ', $missingIds).'.');

                return null;
            }

            return $songs->take($limit)->values();
        }

        return SongTitle::query()
            ->whereKey(array_keys($trends->publicSongTitleIdLookup()))
            ->orderBy('song_title')
            ->get()
            ->filter(fn (SongTitle $song): bool => $this->option('refresh') || in_array(true, $enricher->missingData($song), true))
            ->take($limit)
            ->values();
    }
}
