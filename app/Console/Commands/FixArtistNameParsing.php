<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Artist;
use App\Services\ArtistNameParser;
use Illuminate\Console\Command;

class FixArtistNameParsing extends Command
{
    protected $signature = 'app:fix-artist-name-parsing';

    protected $description = 'Re-parse all artist names to fix first/last name splitting (moves middle initials to first name)';

    public function handle(): int
    {
        $parser = new ArtistNameParser;

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
                $this->line("  {$artist->artist_name}: \"{$artist->artist_first_name}\" / \"{$artist->artist_last_name}\" → \"{$parsed['artist_first_name']}\" / \"{$parsed['artist_last_name']}\"");

                $artist->update([
                    'artist_first_name' => $parsed['artist_first_name'],
                    'artist_last_name' => $parsed['artist_last_name'],
                ]);

                $fixed++;
            }
        }

        if ($fixed === 0) {
            $this->info('All artist names are already correct.');
        } else {
            $this->info("Fixed {$fixed} artist(s).");
        }

        return self::SUCCESS;
    }
}
