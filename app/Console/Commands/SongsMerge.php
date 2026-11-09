<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SongTitle;
use App\Services\CatalogMergeService;
use App\Services\PublicTrendsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SongsMerge extends Command
{
    protected $signature = 'songs:merge
        {keeper : Id of the song title to keep}
        {duplicates* : Ids of the duplicate song titles to merge into the keeper}
        {--title= : Optionally correct the keeper title after merging, e.g. --title="Measure Me, Sky!"}
        {--dry-run : Show what would change, then roll everything back}';

    protected $description = 'Merge duplicate song titles into one, moving performances, enrichment and directors\' files without loss';

    public function handle(CatalogMergeService $merger, PublicTrendsService $trends): int
    {
        $keeper = SongTitle::query()->find((int) $this->argument('keeper'));

        if ($keeper === null) {
            $this->error("Keeper song {$this->argument('keeper')} not found.");

            return self::FAILURE;
        }

        /** @var list<string> $duplicateArguments */
        $duplicateArguments = (array) $this->argument('duplicates');
        $duplicateIds = array_values(array_unique(array_map('intval', $duplicateArguments)));

        if (in_array($keeper->id, $duplicateIds, true)) {
            $this->error('The keeper cannot also be listed as a duplicate.');

            return self::FAILURE;
        }

        $duplicates = SongTitle::query()->whereKey($duplicateIds)->get()->keyBy('id');
        $missing = array_diff($duplicateIds, $duplicates->keys()->all());

        if ($missing !== []) {
            $this->error('Duplicate song(s) not found: '.implode(', ', $missing).'. Nothing was changed.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->info(($dryRun ? '[DRY RUN] ' : '')."Keeper: #{$keeper->id} {$keeper->song_title}");

        DB::beginTransaction();

        try {
            foreach ($duplicateIds as $duplicateId) {
                $duplicate = $duplicates[$duplicateId];
                $report = $merger->mergeSongTitle($keeper, $duplicate);

                $this->line(sprintf('  merged #%d %s: %d performance(s) moved', $duplicate->id, $duplicate->song_title, $report['performances_moved']));
            }

            $newTitle = trim((string) $this->option('title'));

            if ($newTitle !== '' && $newTitle !== $keeper->song_title) {
                $this->retitleKeeper($keeper, $newTitle);
            }
        } catch (Throwable $exception) {
            DB::rollBack();
            $this->error('Merge failed, nothing was changed: '.$exception->getMessage());

            return self::FAILURE;
        }

        if ($dryRun) {
            DB::rollBack();
            $this->warn('Dry run complete — no changes were saved. Re-run without --dry-run to apply.');

            return self::SUCCESS;
        }

        DB::commit();
        $trends->flush();
        $this->info('Merge complete.');

        return self::SUCCESS;
    }

    /**
     * Renames the keeper, refusing if another song with the same composer and arranger already
     * has that title (merge it in as a duplicate instead).
     */
    private function retitleKeeper(SongTitle $keeper, string $newTitle): void
    {
        $conflict = SongTitle::query()
            ->whereKeyNot($keeper->id)
            ->whereRaw('LOWER(song_title) = ?', [mb_strtolower($newTitle)])
            ->when($keeper->composer_id === null, fn ($query) => $query->whereNull('composer_id'), fn ($query) => $query->where('composer_id', $keeper->composer_id))
            ->when($keeper->arranger_id === null, fn ($query) => $query->whereNull('arranger_id'), fn ($query) => $query->where('arranger_id', $keeper->arranger_id))
            ->first();

        if ($conflict !== null) {
            throw new RuntimeException("Song #{$conflict->id} already has the title \"{$conflict->song_title}\" for this composer and arranger; list it as a duplicate instead.");
        }

        $this->line("  retitled keeper: \"{$keeper->song_title}\" → \"{$newTitle}\"");
        SongTitle::query()->whereKey($keeper->id)->update(['song_title' => $newTitle]);
    }
}
