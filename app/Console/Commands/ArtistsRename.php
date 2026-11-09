<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Artist;
use App\Services\ArtistNameParser;
use App\Services\PublicTrendsService;
use Illuminate\Console\Command;

class ArtistsRename extends Command
{
    protected $signature = 'artists:rename
        {artist : Id of the artist to rename}
        {name : The corrected full name, e.g. "Craig Hella Johnson"}
        {--dry-run : Show the change without saving it}';

    protected $description = 'Correct an artist name and re-split it into first and last name';

    public function handle(ArtistNameParser $parser, PublicTrendsService $trends): int
    {
        $artist = Artist::query()->find((int) $this->argument('artist'));

        if ($artist === null) {
            $this->error("Artist {$this->argument('artist')} not found.");

            return self::FAILURE;
        }

        $parsed = $parser->parse((string) $this->argument('name'));

        $conflict = Artist::query()
            ->whereKeyNot($artist->id)
            ->whereRaw('LOWER(artist_name) = ?', [mb_strtolower($parsed['artist_name'])])
            ->first();

        if ($conflict !== null) {
            $this->error("Artist #{$conflict->id} is already named \"{$conflict->artist_name}\". Merge instead: php artisan artists:merge {$conflict->id} {$artist->id}");

            return self::FAILURE;
        }

        $this->line(sprintf(
            '%s#%d "%s" (%s / %s) → "%s" (%s / %s)',
            $this->option('dry-run') ? '[DRY RUN] ' : '',
            $artist->id,
            $artist->artist_name,
            $artist->artist_first_name ?? '—',
            $artist->artist_last_name ?? '—',
            $parsed['artist_name'],
            $parsed['artist_first_name'] ?? '—',
            $parsed['artist_last_name'] ?? '—',
        ));

        if ($this->option('dry-run')) {
            $this->warn('Dry run — no changes were saved.');

            return self::SUCCESS;
        }

        Artist::query()->whereKey($artist->id)->update($parsed);
        $trends->flush();
        $this->info('Renamed.');

        return self::SUCCESS;
    }
}
