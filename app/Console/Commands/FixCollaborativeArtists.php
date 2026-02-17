<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Artist;
use App\Services\ArtistNameParser;
use Illuminate\Console\Command;

class FixCollaborativeArtists extends Command
{
    protected $signature = 'app:fix-collaborative-artists';

    protected $description = 'Re-parse collaborative artist names to set first_name to null and last_name to the full name';

    public function handle(): int
    {
        $parser = new ArtistNameParser;

        $artists = Artist::query()
            ->where(function ($query): void {
                $query->where('artist_name', 'LIKE', '% & %')
                    ->orWhere('artist_name', 'LIKE', '% and %')
                    ->orWhere('artist_name', 'LIKE', '%, %');
            })
            ->whereNotNull('artist_first_name')
            ->get();

        if ($artists->isEmpty()) {
            $this->info('No collaborative artists need fixing.');

            return self::SUCCESS;
        }

        $this->info("Found {$artists->count()} collaborative artist(s) to fix.");

        foreach ($artists as $artist) {
            $parsed = $parser->parse($artist->artist_name);

            $artist->update([
                'artist_first_name' => $parsed['artist_first_name'],
                'artist_last_name' => $parsed['artist_last_name'],
            ]);

            $this->line("  Fixed: {$artist->artist_name}");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
