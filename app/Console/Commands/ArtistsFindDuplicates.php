<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Artist;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ArtistsFindDuplicates extends Command
{
    protected $signature = 'artists:find-duplicates
        {--min-songs=0 : Only show groups where the group has at least this many songs in total}';

    protected $description = 'List likely duplicate artists (same surname and initials, or names that differ only in punctuation) with ready-to-run merge commands';

    public function handle(): int
    {
        $artists = Artist::query()
            ->withCount(['composedSongs', 'arrangedSongs'])
            ->get()
            ->each(fn (Artist $artist) => $artist->setAttribute('song_count', $artist->composed_songs_count + $artist->arranged_songs_count));

        $groups = $artists
            ->groupBy(fn (Artist $artist): string => self::duplicateKey($artist))
            ->filter(fn (Collection $group, string $key): bool => $key !== '' && $group->count() > 1)
            ->filter(fn (Collection $group): bool => $group->sum('song_count') >= (int) $this->option('min-songs'))
            ->sortByDesc(fn (Collection $group): int => (int) $group->sum('song_count'));

        if ($groups->isEmpty()) {
            $this->info('No likely duplicates found.');

            return self::SUCCESS;
        }

        foreach ($groups as $group) {
            /** @var Collection<int, Artist> $group */
            $sorted = self::rankKeeperCandidates($group);
            $keeper = $sorted->first();

            $this->newLine();
            foreach ($sorted as $artist) {
                $this->line(sprintf('  #%-5d %-45s %3d song(s)%s', $artist->id, $artist->artist_name, (int) $artist->getAttribute('song_count'), $artist->is($keeper) ? '  ← suggested keeper' : ''));
            }
            $this->line('  php artisan artists:merge '.$keeper->id.' '.$sorted->skip(1)->pluck('id')->join(' ').' --dry-run');
        }

        $this->newLine();
        $this->info("{$groups->count()} likely duplicate group(s). Review each before merging — this is a suggestion list, not a guarantee.");

        return self::SUCCESS;
    }

    /**
     * Best keeper first: a spelled-out given name ("George Frideric Handel") beats initials
     * ("G. F. Handel") because the keeper's name is the one that survives, then most songs.
     *
     * @param  Collection<int, Artist>  $group
     * @return Collection<int, Artist>
     */
    public static function rankKeeperCandidates(Collection $group): Collection
    {
        return $group
            ->sortBy([
                fn (Artist $a, Artist $b): int => self::usesInitialsOnly($a) <=> self::usesInitialsOnly($b),
                fn (Artist $a, Artist $b): int => (int) $b->getAttribute('song_count') <=> (int) $a->getAttribute('song_count'),
                fn (Artist $a, Artist $b): int => mb_strlen($b->artist_name) <=> mb_strlen($a->artist_name),
                fn (Artist $a, Artist $b): int => $a->id <=> $b->id,
            ])
            ->values();
    }

    private static function usesInitialsOnly(Artist $artist): bool
    {
        $givenNames = preg_split('/[\s.]+/u', trim((string) $artist->artist_first_name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return $givenNames !== [] && collect($givenNames)->every(fn (string $part): bool => mb_strlen($part) === 1);
    }

    /**
     * Groups names that differ only in punctuation, accents, case or "&" vs "and", and
     * individual names that share a surname and given-name initials
     * ("G. F. Handel", "G.F. Handel", "George Frideric Handel" → "handel|gf").
     */
    public static function duplicateKey(Artist $artist): string
    {
        $normalize = fn (string $value): string => trim((string) preg_replace(
            ['/&/', '/[^a-z0-9 ]+/', '/\s+/'],
            [' and ', ' ', ' '],
            mb_strtolower(Str::ascii($value)),
        ));

        $firstName = trim((string) $artist->artist_first_name);
        $lastName = trim((string) $artist->artist_last_name);

        if ($firstName === '' || $lastName === '') {
            return 'name|'.$normalize($artist->artist_name);
        }

        $givenNames = preg_split('/[\s.]+/u', $firstName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = implode('', array_map(fn (string $part): string => mb_substr($normalize($part), 0, 1), $givenNames));

        return 'person|'.$normalize($lastName).'|'.$initials;
    }
}
