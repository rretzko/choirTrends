<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Artist;
use App\Services\CatalogMergeService;
use App\Services\PublicTrendsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ArtistsMerge extends Command
{
    protected $signature = 'artists:merge
        {keeper : Id of the artist record to keep}
        {duplicates* : Ids of the duplicate artist records to merge into the keeper}
        {--dry-run : Show what would change, then roll everything back}';

    protected $description = 'Merge duplicate artists into one, moving songs, performances and all song data without loss';

    public function handle(CatalogMergeService $merger, PublicTrendsService $trends): int
    {
        $keeper = Artist::query()->find((int) $this->argument('keeper'));

        if ($keeper === null) {
            $this->error("Keeper artist {$this->argument('keeper')} not found.");

            return self::FAILURE;
        }

        /** @var list<string> $duplicateArguments */
        $duplicateArguments = (array) $this->argument('duplicates');
        $duplicateIds = array_values(array_unique(array_map('intval', $duplicateArguments)));

        if (in_array($keeper->id, $duplicateIds, true)) {
            $this->error('The keeper cannot also be listed as a duplicate.');

            return self::FAILURE;
        }

        $duplicates = Artist::query()->whereKey($duplicateIds)->get()->keyBy('id');
        $missing = array_diff($duplicateIds, $duplicates->keys()->all());

        if ($missing !== []) {
            $this->error('Duplicate artist(s) not found: '.implode(', ', $missing).'. Nothing was changed.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->info(($dryRun ? '[DRY RUN] ' : '')."Keeper: #{$keeper->id} {$keeper->artist_name}");

        DB::beginTransaction();

        try {
            foreach ($duplicateIds as $duplicateId) {
                $duplicate = $duplicates[$duplicateId];
                $report = $merger->mergeArtist($keeper, $duplicate);

                $this->line(sprintf(
                    '  merged #%d %s: %d song(s) reassigned, %d song(s) folded into an existing keeper song, %d representation(s) moved',
                    $duplicate->id,
                    $duplicate->artist_name,
                    $report['songs_reassigned'],
                    $report['songs_merged'],
                    $report['representations_moved'],
                ));
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
}
