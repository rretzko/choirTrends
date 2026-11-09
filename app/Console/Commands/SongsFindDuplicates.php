<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SongTitle;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SongsFindDuplicates extends Command
{
    protected $signature = 'songs:find-duplicates
        {--artist= : Only check songs composed or arranged by this artist id}';

    protected $description = 'List likely duplicate song titles (same composer and arranger, titles that differ only in articles, movement labels, "from …" notes or catalogue numbers) with ready-to-run merge commands';

    public function handle(): int
    {
        $artistId = $this->option('artist');

        $songs = SongTitle::query()
            ->with(['composer:id,artist_name', 'arranger:id,artist_name'])
            ->withCount(['programs', 'descriptions', 'tags'])
            ->where(fn ($query) => $query->whereNotNull('composer_id')->orWhereNotNull('arranger_id'))
            ->when($artistId !== null, fn ($query) => $query->where(fn ($inner) => $inner
                ->where('composer_id', (int) $artistId)
                ->orWhere('arranger_id', (int) $artistId)))
            ->get();

        $groups = $songs
            ->groupBy(fn (SongTitle $song): string => $song->composer_id.'|'.$song->arranger_id.'|'.self::normalizeTitle($song->song_title))
            ->filter(fn (Collection $group, string $key): bool => $group->count() > 1 && ! str_ends_with($key, '|'))
            ->sortByDesc(fn (Collection $group): int => (int) $group->sum('programs_count'));

        if ($groups->isEmpty()) {
            $this->info('No likely duplicate song titles found.');

            return self::SUCCESS;
        }

        foreach ($groups as $group) {
            /** @var Collection<int, SongTitle> $group */
            $sorted = self::rankKeeperCandidates($group);
            $keeper = $sorted->first();
            $byline = collect([
                $keeper->composer?->artist_name,
                $keeper->arranger ? 'arr. '.$keeper->arranger->artist_name : null,
            ])->filter()->join(', ');

            $this->newLine();
            $this->line("  {$byline}");
            foreach ($sorted as $song) {
                $this->line(sprintf(
                    '    #%-5d %-60s %2d performance(s)%s',
                    $song->id,
                    Str::limit($song->song_title, 58),
                    (int) $song->getAttribute('programs_count'),
                    $song->is($keeper) ? '  ← suggested keeper' : '',
                ));
            }
            $this->line('    php artisan songs:merge '.$keeper->id.' '.$sorted->skip(1)->pluck('id')->join(' ').' --dry-run');
        }

        $this->newLine();
        $this->info("{$groups->count()} likely duplicate group(s). Review each before merging — this is a suggestion list, not a guarantee.");

        return self::SUCCESS;
    }

    /**
     * Reduces a title to the words that identify the piece, so that "The Hallelujah Chorus
     * from The Messiah", "Hallelujah, from Messiah" and "Chorus: Hallelujah" all match.
     */
    public static function normalizeTitle(string $title): string
    {
        $value = mb_strtolower(Str::ascii($title));

        $patterns = [
            '/\([^)]*\)?/',                                                    // parenthetical notes, even unclosed: "(from Samson)"
            '/\[[^\]]*\]?/',                                                   // bracketed notes
            '/[,;]?\s+from\s+.*$/',                                            // ", from Messiah" / " from Requiem in d minor"
            '/[,;]?\s*\b(?:k|kv|hwv|bwv|rv|op|opus)\.?\s*\d+[a-z]?\b/',       // catalogue numbers: ", K. 339", "HWV 56" ("No. 4" is kept: it names the work)
            '/^\s*[ivxlc]+\.\s+/',                                             // roman movement numbers: "III. "
            '/^\s*[^:]{1,25}:\s*/',                                            // short movement labels: "Chorus: ", "Air: "
            '/^\s*\d+\.\s+/',                                                  // numbered movements: "1. "
            '/[^a-z0-9 ]+/',                                                   // punctuation and quotes
            '/^\s*(?:the|a|an)\s+/',                                           // leading article
            '/\s+chorus\s*$/',                                                 // trailing "Chorus"
            '/\s+/',
        ];

        $replacements = ['', '', '', '', '', '', '', ' ', '', '', ' '];

        return trim((string) preg_replace($patterns, $replacements, $value));
    }

    /**
     * Best keeper first: most performances, then most enrichment, then a title without
     * extra notes ("from …", parentheses, "Chorus:" labels, catalogue numbers), then the
     * fuller title, so "The Road Home" beats "Road Home" and "Hallelujah Chorus" beats
     * "Hallelujah, from Messiah".
     *
     * @param  Collection<int, SongTitle>  $group
     * @return Collection<int, SongTitle>
     */
    public static function rankKeeperCandidates(Collection $group): Collection
    {
        return $group
            ->sortBy([
                fn (SongTitle $a, SongTitle $b): int => (int) $b->getAttribute('programs_count') <=> (int) $a->getAttribute('programs_count'),
                fn (SongTitle $a, SongTitle $b): int => self::enrichmentCount($b) <=> self::enrichmentCount($a),
                fn (SongTitle $a, SongTitle $b): int => self::hasTitleNotes($a->song_title) <=> self::hasTitleNotes($b->song_title),
                fn (SongTitle $a, SongTitle $b): int => mb_strlen($b->song_title) <=> mb_strlen($a->song_title),
                fn (SongTitle $a, SongTitle $b): int => $a->id <=> $b->id,
            ])
            ->values();
    }

    public static function hasTitleNotes(string $title): bool
    {
        return (bool) preg_match('/[(\[]|\bfrom\b|^[^:]{1,25}:|^\s*[ivxlc]+\.\s|\b(?:k|kv|hwv|bwv|rv|op|opus)\.?\s*\d/i', $title);
    }

    private static function enrichmentCount(SongTitle $song): int
    {
        return (int) $song->getAttribute('descriptions_count') + (int) $song->getAttribute('tags_count');
    }
}
