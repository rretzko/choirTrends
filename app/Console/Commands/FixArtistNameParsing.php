<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Artist;
use App\Services\ArtistNameParser;
use App\Services\PublicTrendsService;
use Illuminate\Console\Command;

class FixArtistNameParsing extends Command
{
    protected $signature = 'app:fix-artist-name-parsing
        {--dry-run : List the first/last name changes without saving them}
        {--force : Apply the changes (overwrites hand-corrected names; review a --dry-run first)}';

    protected $description = 'Re-parse all artist names to fix first/last name splitting (middle initials, surname prefixes such as "Von", compound surnames)';

    public function handle(ArtistNameParser $parser, PublicTrendsService $trends): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $this->option('force')) {
            $this->error('This re-parses every artist and can overwrite names that were corrected by hand. Run with --dry-run to review, then --force to apply. To fix one artist, use: php artisan artists:rename {id} "{name}"');

            return self::FAILURE;
        }

        $artists = Artist::query()
            ->whereNotNull('artist_first_name')
            ->get();

        if ($artists->isEmpty()) {
            $this->info('No artists need fixing.');

            return self::SUCCESS;
        }

        $fixed = 0;

        foreach ($artists as $artist) {
            $parsed = $parser->parse($artist->artist_name);

            if ($parsed['artist_first_name'] !== $artist->artist_first_name
                || $parsed['artist_last_name'] !== $artist->artist_last_name) {
                $this->line(($dryRun ? '  [DRY RUN] ' : '  ')."#{$artist->id} {$artist->artist_name}: \"{$artist->artist_first_name}\" / \"{$artist->artist_last_name}\" → \"{$parsed['artist_first_name']}\" / \"{$parsed['artist_last_name']}\"");

                if (! $dryRun) {
                    $artist->update([
                        'artist_first_name' => $parsed['artist_first_name'],
                        'artist_last_name' => $parsed['artist_last_name'],
                    ]);
                }

                $fixed++;
            }
        }

        if ($fixed === 0) {
            $this->info('All artist names are already correct.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("{$fixed} artist(s) would change. Re-run without --dry-run to apply.");

            return self::SUCCESS;
        }

        $trends->flush();
        $this->info("Fixed {$fixed} artist(s).");

        return self::SUCCESS;
    }
}
