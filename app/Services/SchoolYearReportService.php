<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Program;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Locale;

/**
 * The public school-year report (/reports/2025-26). Every named piece, composer and arranger
 * met the public school threshold within that school year, and regions with fewer schools than
 * the threshold are named without counts, so no school can be singled out.
 *
 * The school year runs September 1 to July 31; winter is September–January and spring is
 * February–July.
 */
class SchoolYearReportService
{
    private const RANKING_LIMIT = 10;

    private const SEASON_LIMIT = 5;

    private const UNKNOWN_REGION = '';

    /**
     * @var array<string, string>
     */
    private const US_STATES = [
        'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
        'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'DC' => 'Washington, D.C.', 'FL' => 'Florida',
        'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
        'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
        'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri',
        'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey',
        'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
        'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
        'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont',
        'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
    ];

    public function __construct(private PublicTrendsService $trends) {}

    /**
     * School years with enough data to publish, newest first, keyed by start year.
     *
     * @return array<int, string> e.g. [2025 => '2025-26']
     */
    public function availableSchoolYears(): array
    {
        return $this->trends->remember('report_years', function (): array {
            $minimumPrograms = (int) config('trends.report_minimum_programs');
            $minimumSchools = (int) config('trends.report_minimum_schools');

            return Program::query()
                ->has('songTitles')
                ->get(['school_id', 'event_date'])
                ->groupBy(function (Program $program): int {
                    $date = CarbonImmutable::parse($program->getRawOriginal('event_date'));

                    return $date->month >= 9 ? $date->year : $date->year - 1;
                })
                ->filter(fn (Collection $programs): bool => $programs->count() >= $minimumPrograms && $programs->pluck('school_id')->unique()->count() >= $minimumSchools)
                ->keys()
                ->sortDesc()
                ->mapWithKeys(fn (int $startYear): array => [$startYear => self::slugFor($startYear)])
                ->all();
        });
    }

    public static function slugFor(int $startYear): string
    {
        return $startYear.'-'.substr((string) ($startYear + 1), -2);
    }

    public static function labelFor(int $startYear): string
    {
        return $startYear.'–'.substr((string) ($startYear + 1), -2);
    }

    /**
     * Parses "2025-26" into its start year, or null when it is not a valid consecutive pair.
     */
    public static function startYearFromSlug(string $slug): ?int
    {
        if (! preg_match('/^(\d{4})-(\d{2})$/', $slug, $matches)) {
            return null;
        }

        $startYear = (int) $matches[1];

        return substr((string) ($startYear + 1), -2) === $matches[2] ? $startYear : null;
    }

    /**
     * @return array{
     *     startYear: int,
     *     label: string,
     *     stats: array{programs: int, schools: int, directors: int, titles: int, performances: int},
     *     months: list<array{label: string, name: string, programs: int}>,
     *     regions: list<array{name: string, schools: int|null}>,
     *     pieces: Collection<int, TrendingPiece>,
     *     publicPieceCount: int,
     *     composers: Collection<int, TrendingArtist>,
     *     arrangers: Collection<int, TrendingArtist>,
     *     winterComposers: Collection<int, TrendingArtist>,
     *     springComposers: Collection<int, TrendingArtist>,
     *     highlights: list<string>
     * }
     */
    public function report(int $startYear): array
    {
        return $this->trends->remember("report:{$startYear}", function () use ($startYear): array {
            $start = CarbonImmutable::create($startYear, 9, 1)->startOfDay();
            $end = CarbonImmutable::create($startYear + 1, 8, 1)->startOfDay();
            $springStart = CarbonImmutable::create($startYear + 1, 2, 1)->startOfDay();

            $inYear = $this->window($start, $end);
            $allPieces = $this->trends->publicPieces($inYear);
            $composers = $this->trends->publicArtists('composer_id', $inYear, self::RANKING_LIMIT);
            $arrangers = $this->trends->publicArtists('arranger_id', $inYear, self::RANKING_LIMIT);
            $stats = $this->stats($start, $end);

            $report = [
                'startYear' => $startYear,
                'label' => self::labelFor($startYear),
                'stats' => $stats,
                'months' => $this->months($start, $end),
                'regions' => $this->regions($start, $end),
                'pieces' => $allPieces->take(self::RANKING_LIMIT)->values(),
                'publicPieceCount' => $allPieces->count(),
                'composers' => $composers,
                'arrangers' => $arrangers,
                'winterComposers' => $this->trends->publicArtists('composer_id', $this->window($start, $springStart), self::SEASON_LIMIT),
                'springComposers' => $this->trends->publicArtists('composer_id', $this->window($springStart, $end), self::SEASON_LIMIT),
            ];

            $report['highlights'] = $this->highlights($report);

            return $report;
        });
    }

    /**
     * @return \Closure(Builder<Model>): mixed
     */
    private function window(CarbonImmutable $from, CarbonImmutable $until): \Closure
    {
        return fn (Builder $query) => self::withinDates($query, 'programs.event_date', $from, $until);
    }

    /**
     * @return array{programs: int, schools: int, directors: int, titles: int, performances: int}
     */
    private function stats(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $row = Program::query()
            ->join('program_song_title', 'program_song_title.program_id', '=', 'programs.id')
            ->tap(fn (Builder $query) => self::withinDates($query, 'programs.event_date', $start, $end))
            ->selectRaw('COUNT(DISTINCT programs.id) as programs')
            ->selectRaw('COUNT(DISTINCT programs.school_id) as schools')
            ->selectRaw('COUNT(DISTINCT programs.user_id) as directors')
            ->selectRaw('COUNT(DISTINCT program_song_title.song_title_id) as titles')
            ->selectRaw('COUNT(*) as performances')
            ->toBase()
            ->first();

        return [
            'programs' => (int) ($row->programs ?? 0),
            'schools' => (int) ($row->schools ?? 0),
            'directors' => (int) ($row->directors ?? 0),
            'titles' => (int) ($row->titles ?? 0),
            'performances' => (int) ($row->performances ?? 0),
        ];
    }

    /**
     * Concerts per month, September through July, including empty months.
     *
     * @return list<array{label: string, name: string, programs: int}>
     */
    private function months(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $counts = Program::query()
            ->has('songTitles')
            ->tap(fn (Builder $query) => self::withinDates($query, 'event_date', $start, $end))
            ->pluck('event_date')
            ->countBy(fn (mixed $date): string => CarbonImmutable::parse($date)->format('Y-m'));

        $months = [];

        for ($month = $start; $month->lessThan($end); $month = $month->addMonth()) {
            $months[] = ['label' => $month->format('M'), 'name' => $month->format('F'), 'programs' => (int) ($counts[$month->format('Y-m')] ?? 0)];
        }

        return $months;
    }

    /**
     * Regions with at least the public threshold of schools, largest first with counts, then the
     * smaller regions alphabetically and without counts (so their order reveals nothing either).
     * Schools with no recorded location are left out.
     *
     * @return list<array{name: string, schools: int|null}>
     */
    private function regions(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $minimum = $this->trends->minimumSchools();

        return Program::query()
            ->has('songTitles')
            ->join('schools', 'schools.id', '=', 'programs.school_id')
            ->tap(fn (Builder $query) => self::withinDates($query, 'programs.event_date', $start, $end))
            ->get(['schools.id as school_id', 'schools.geo_state', 'schools.country'])
            ->unique('school_id')
            ->groupBy(fn (Program $row): string => self::regionName($row->getAttribute('geo_state'), $row->getAttribute('country')))
            ->forget(self::UNKNOWN_REGION)
            ->map(fn (Collection $schools, string $name): array => ['name' => $name, 'schools' => $schools->count() >= $minimum ? $schools->count() : null])
            ->sortBy([
                fn (array $a, array $b): int => ($b['schools'] ?? 0) <=> ($a['schools'] ?? 0),
                fn (array $a, array $b): int => $a['name'] <=> $b['name'],
            ])
            ->values()
            ->all();
    }

    /**
     * Half-open date window [from, until): robust whether event_date is stored as a DATE or a datetime.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private static function withinDates(Builder $query, string $column, CarbonImmutable $from, CarbonImmutable $until): Builder
    {
        return $query->where($column, '>=', $from->toDateString())->where($column, '<', $until->toDateString());
    }

    public static function regionName(mixed $state, mixed $country): string
    {
        $state = strtoupper(trim((string) $state));
        $country = strtoupper(trim((string) $country));

        if (($country === 'US' || $country === '') && isset(self::US_STATES[$state])) {
            return self::US_STATES[$state];
        }

        if ($country !== '' && $country !== 'US') {
            $name = class_exists(Locale::class) ? Locale::getDisplayRegion('-'.$country, 'en') : '';

            return $name !== '' && $name !== $country ? $name : $country;
        }

        return self::UNKNOWN_REGION;
    }

    /**
     * Plain-language findings generated from the numbers, so they never drift from the data.
     *
     * @param  array<string, mixed>  $report
     * @return list<string>
     */
    private function highlights(array $report): array
    {
        $highlights = [];
        $minimum = $this->trends->minimumSchools();

        /** @var Collection<int, TrendingPiece> $pieces */
        $pieces = $report['pieces'];
        /** @var Collection<int, TrendingArtist> $composers */
        $composers = $report['composers'];
        /** @var Collection<int, TrendingArtist> $arrangers */
        $arrangers = $report['arrangers'];
        /** @var list<array{label: string, name: string, programs: int}> $months */
        $months = $report['months'];
        $stats = $report['stats'];

        if ($piece = $pieces->first()) {
            $highlights[] = sprintf(
                'The most widely programmed piece was “%s”%s, sung by %d different schools.',
                $piece->title,
                $piece->composer ? ' by '.$piece->composer : '',
                $piece->schoolCount,
            );
        }

        if ($composer = $composers->first()) {
            $highlights[] = sprintf('%s was the most widely programmed composer, with music on concerts at %d schools.', $composer->name, $composer->schoolCount);
        }

        if ($arranger = $arrangers->first()) {
            $highlights[] = sprintf('%s was the most widely programmed arranger, at %d schools.', $arranger->name, $arranger->schoolCount);
        }

        if ($stats['performances'] > 0 && $stats['titles'] > 0) {
            $highlights[] = sprintf(
                '%s performances covered %s different titles — most pieces appeared on only one school’s concert, and %d reached %d or more schools.',
                number_format($stats['performances']),
                number_format($stats['titles']),
                (int) $report['publicPieceCount'],
                $minimum,
            );
        }

        $busiest = collect($months)->sortByDesc('programs')->first();
        if ($busiest && $busiest['programs'] > 0) {
            $highlights[] = sprintf('%s was the busiest concert month, with %d of the year’s %d programs.', $busiest['name'], $busiest['programs'], $stats['programs']);
        }

        return $highlights;
    }
}
