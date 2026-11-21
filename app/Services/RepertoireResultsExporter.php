<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RepertoireQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Renders a completed AI repertoire search as a downloadable text, CSV, or PDF document.
 */
class RepertoireResultsExporter
{
    public const array FORMATS = ['txt', 'csv', 'pdf'];

    private const array PARTS = ['soprano' => 'Soprano', 'alto' => 'Alto', 'tenor' => 'Tenor', 'bass' => 'Bass'];

    public function filename(RepertoireQuery $repertoireQuery, string $format): string
    {
        $slug = Str::limit(Str::slug($repertoireQuery->query_text), 60, '');

        return 'ChoirTrends-repertoire-'.($slug !== '' ? $slug : 'search').'.'.$format;
    }

    public function render(RepertoireQuery $repertoireQuery, string $format): string
    {
        return match ($format) {
            'txt' => $this->toText($repertoireQuery),
            'csv' => $this->toCsv($repertoireQuery),
            'pdf' => $this->toPdf($repertoireQuery),
            default => throw new InvalidArgumentException("Unsupported export format [{$format}]."),
        };
    }

    public function contentType(string $format): string
    {
        return match ($format) {
            'txt' => 'text/plain; charset=UTF-8',
            'csv' => 'text/csv; charset=UTF-8',
            'pdf' => 'application/pdf',
            default => throw new InvalidArgumentException("Unsupported export format [{$format}]."),
        };
    }

    public function toText(RepertoireQuery $repertoireQuery): string
    {
        $lines = [
            'ChoirTrends — AI Repertoire Finder',
            'Search: '.$repertoireQuery->query_text,
            'Date: '.$repertoireQuery->created_at?->format('F j, Y'),
        ];

        if ($notes = $this->interpretationNotes($repertoireQuery)) {
            $lines[] = '';
            $lines[] = $notes;
        }

        $lines[] = '';
        $lines[] = 'AI-estimated difficulty and matches — not a substitute for reviewing the score yourself.';

        $songs = $this->songs($repertoireQuery);

        if ($songs === []) {
            $lines[] = '';
            $lines[] = 'No matches found.';
        }

        foreach ($songs as $index => $song) {
            $lines[] = '';
            $lines[] = str_repeat('-', 60);
            $lines[] = ($index + 1).'. '.$song['song_title'];
            $lines[] = $this->creditLine($song);
            $lines[] = 'Source: '.$this->sourceLabel($song);

            if ($song['fit_rationale'] !== '') {
                $lines[] = '';
                $lines[] = $song['fit_rationale'];
            }

            $lines[] = '';
            $lines[] = 'Difficulty: '.collect(self::PARTS)
                ->map(fn (string $label, string $part): string => $label.' '.$this->difficulty($song, $part))
                ->implode(' | ');

            if ($song['tags'] !== []) {
                $lines[] = 'Tags: '.implode(', ', $song['tags']);
            }

            if ($song['youtube_url'] !== '') {
                $lines[] = 'YouTube: '.$song['youtube_url'];
            }
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    public function toCsv(RepertoireQuery $repertoireQuery): string
    {
        $handle = fopen('php://temp', 'r+');

        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, ['Title', 'Composer', 'Arranger', 'Voicing', 'Source', 'Soprano', 'Alto', 'Tenor', 'Bass', 'Tags', 'Why it fits', 'YouTube'], escape: '');

        foreach ($this->songs($repertoireQuery) as $song) {
            fputcsv($handle, [
                $song['song_title'],
                $song['composer'],
                $song['arranger'],
                $song['voicing'],
                $this->sourceLabel($song),
                $this->difficulty($song, 'soprano'),
                $this->difficulty($song, 'alto'),
                $this->difficulty($song, 'tenor'),
                $this->difficulty($song, 'bass'),
                implode(', ', $song['tags']),
                $song['fit_rationale'],
                $song['youtube_url'],
            ], escape: '');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    public function toPdf(RepertoireQuery $repertoireQuery): string
    {
        return Pdf::loadView('pdf.repertoire-results', [
            'repertoireQuery' => $repertoireQuery,
            'interpretationNotes' => $this->interpretationNotes($repertoireQuery),
            'songs' => collect($this->songs($repertoireQuery))->map(fn (array $song): array => [
                ...$song,
                'credit_line' => $this->creditLine($song),
                'source_label' => $this->sourceLabel($song),
                'difficulties' => collect(self::PARTS)
                    ->mapWithKeys(fn (string $label, string $part): array => [$label => $this->difficulty($song, $part)])
                    ->all(),
            ])->all(),
        ])->setPaper('letter')->output();
    }

    /**
     * @return list<array{song_title: string, composer: string, arranger: string, voicing: string, source: string, fit_rationale: string, difficulty_by_part: array<string, string|null>, tags: list<string>, youtube_url: string}>
     */
    private function songs(RepertoireQuery $repertoireQuery): array
    {
        return collect($repertoireQuery->response['results'] ?? [])
            ->filter(fn (mixed $song): bool => is_array($song))
            ->map(fn (array $song): array => [
                'song_title' => (string) ($song['song_title'] ?? ''),
                'composer' => (string) ($song['composer'] ?? ''),
                'arranger' => (string) ($song['arranger'] ?? ''),
                'voicing' => (string) ($song['voicing'] ?? ''),
                'source' => (string) ($song['source'] ?? ''),
                'fit_rationale' => (string) ($song['fit_rationale'] ?? ''),
                'difficulty_by_part' => is_array($song['difficulty_by_part'] ?? null) ? $song['difficulty_by_part'] : [],
                'tags' => array_values(array_map('strval', is_array($song['tags'] ?? null) ? $song['tags'] : [])),
                'youtube_url' => (string) ($song['youtube_url'] ?? ''),
            ])
            ->values()
            ->all();
    }

    private function interpretationNotes(RepertoireQuery $repertoireQuery): ?string
    {
        $notes = $repertoireQuery->response['query_interpretation']['interpretation_notes'] ?? null;

        return is_string($notes) && $notes !== '' ? $notes : null;
    }

    /**
     * @param  array{composer: string, arranger: string, voicing: string}  $song
     */
    private function creditLine(array $song): string
    {
        $credit = $song['composer'];

        if ($song['arranger'] !== '') {
            $credit .= ' / arr. '.$song['arranger'];
        }

        if ($song['voicing'] !== '') {
            $credit .= ' · '.$song['voicing'];
        }

        return $credit;
    }

    /**
     * @param  array{source: string}  $song
     */
    private function sourceLabel(array $song): string
    {
        return $song['source'] === 'internal_catalog' ? 'In ChoirTrends' : 'Web';
    }

    /**
     * @param  array{difficulty_by_part: array<string, string|null>}  $song
     */
    private function difficulty(array $song, string $part): string
    {
        return Str::ucfirst((string) ($song['difficulty_by_part'][$part] ?? 'unknown'));
    }
}
