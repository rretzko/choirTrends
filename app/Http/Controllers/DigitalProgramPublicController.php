<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DigitalProgram;
use App\Models\Ensemble;
use App\Models\UserSongLyrics;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\View\View;

class DigitalProgramPublicController extends Controller
{
    public function __invoke(string $slug): View
    {
        $dp = DigitalProgram::with([
            'program.school',
            'program.songTitles.composer',
            'program.songTitles.arranger',
            'honors',
            'rosters.honors',
            'songSettings',
        ])->where('slug', $slug)->firstOrFail();

        abort_unless($dp->is_published, 404);

        // ── Song groups sorted by ensemble_sort_order ────────────────────────
        $ensembleGroups = collect();

        if ($dp->program) {
            $songsByEnsemble = $dp->program->songTitles
                ->groupBy(fn ($st) => $st->pivot->ensemble_id ?? 'general');

            $ensembleIds = $songsByEnsemble->keys()
                ->filter(fn ($k) => $k !== 'general')
                ->map(fn ($k) => (int) $k)
                ->all();

            $ensemblesById = ! empty($ensembleIds)
                ? Ensemble::whereIn('id', $ensembleIds)->get()->keyBy('id')
                : collect();

            $ensembleGroups = $songsByEnsemble->map(fn ($songs, $key) => [
                'key' => (string) $key,
                'ensemble' => $key === 'general' ? null : ($ensemblesById[(int) $key] ?? null),
                'ensemble_sort_order' => $songs->first()?->pivot->ensemble_sort_order ?? 999,
                'ensemble_director' => $songs->first()?->pivot?->ensemble_director,
                'songs' => $songs->sortBy('pivot.sort_order')->values(),
            ])->sortBy('ensemble_sort_order')->values();
        }

        // ── Rosters and honors keyed by ensemble key ─────────────────────────
        $rostersByEnsemble = $dp->rosters
            ->groupBy(fn ($r) => (string) ($r->ensemble_id ?? 'general'));

        $honorsByEnsemble = $dp->honors
            ->groupBy(fn ($h) => (string) ($h->ensemble_id ?? 'general'));

        // ── Add synthetic ensemble groups for roster/honor sections not covered by songs ──
        $coveredKeys = $ensembleGroups->pluck('key')->all();

        $extraKeys = collect($rostersByEnsemble->keys()->merge($honorsByEnsemble->keys()))
            ->unique()
            ->reject(fn ($k) => in_array($k, $coveredKeys));

        foreach ($extraKeys as $ek) {
            $ensembleId = $ek === 'general' ? null : (int) $ek;

            $ensembleGroups->push([
                'key' => $ek,
                'ensemble' => $ensembleId ? Ensemble::find($ensembleId) : null,
                'ensemble_sort_order' => 999,
                'ensemble_director' => null,
                'songs' => collect(),
            ]);
        }

        // ── Per-song program notes ───────────────────────────────────────────
        $notesBySongId = $dp->songSettings
            ->whereNotNull('program_notes')
            ->pluck('program_notes', 'song_title_id')
            ->all();

        // ── Lyrics for enabled songs ─────────────────────────────────────────
        $showLyricsSongIds = $dp->songSettings
            ->where('show_lyrics', true)
            ->pluck('song_title_id')
            ->all();

        $lyricsBySongId = ! empty($showLyricsSongIds)
            ? UserSongLyrics::query()
                ->where('user_id', $dp->user_id)
                ->whereIn('song_title_id', $showLyricsSongIds)
                ->get()
                ->keyBy('song_title_id')
            : collect();

        // ── QR code (SVG, embedded inline) ───────────────────────────────────
        $renderer = new ImageRenderer(new RendererStyle(160), new SvgImageBackEnd);
        $qrCode = (new Writer($renderer))->writeString(
            route('program.public', ['slug' => $dp->slug])
        );

        // ── Theme CSS variables ───────────────────────────────────────────────
        $themeStyle = self::themeStyles()[$dp->theme] ?? self::themeStyles()['Formal'];

        return view('digital-programs.show', compact(
            'dp',
            'ensembleGroups',
            'rostersByEnsemble',
            'honorsByEnsemble',
            'showLyricsSongIds',
            'lyricsBySongId',
            'notesBySongId',
            'qrCode',
            'themeStyle',
        ));
    }

    /** @return array<string, array<string, string>> */
    private static function themeStyles(): array
    {
        return [
            'WinterConcert' => [
                'bg' => '#0f172a',
                'surface' => '#1e293b',
                'surface2' => '#263347',
                'text' => '#e2e8f0',
                'text_muted' => '#94a3b8',
                'accent' => '#93c5fd',
                'border' => '#334155',
                'header_bg' => '#020617',
                'header_text' => '#f1f5f9',
                'song_title' => '#bfdbfe',
                'section_label' => '#64748b',
                'lyrics_bg' => '#1e3a5f',
                'inter_text' => '#64748b',
                'font' => "Georgia, 'Times New Roman', serif",
                'heading_font' => "Georgia, 'Times New Roman', serif",
            ],
            'SpringFestival' => [
                'bg' => '#f0fdf4',
                'surface' => '#dcfce7',
                'surface2' => '#bbf7d0',
                'text' => '#14532d',
                'text_muted' => '#16a34a',
                'accent' => '#ea580c',
                'border' => '#86efac',
                'header_bg' => '#14532d',
                'header_text' => '#f0fdf4',
                'song_title' => '#15803d',
                'section_label' => '#4ade80',
                'lyrics_bg' => '#bbf7d0',
                'inter_text' => '#4ade80',
                'font' => "system-ui, 'Segoe UI', sans-serif",
                'heading_font' => "Georgia, 'Times New Roman', serif",
            ],
            'Graduation' => [
                'bg' => '#1e1b4b',
                'surface' => '#172554',
                'surface2' => '#1e3a8a',
                'text' => '#fef9c3',
                'text_muted' => '#fde68a',
                'accent' => '#eab308',
                'border' => '#312e81',
                'header_bg' => '#0f0a2e',
                'header_text' => '#fefce8',
                'song_title' => '#fde68a',
                'section_label' => '#818cf8',
                'lyrics_bg' => '#1e3a8a',
                'inter_text' => '#818cf8',
                'font' => "Georgia, 'Times New Roman', serif",
                'heading_font' => "Georgia, 'Times New Roman', serif",
            ],
            'Holiday' => [
                'bg' => '#3b0c0c',
                'surface' => '#4c1212',
                'surface2' => '#5c1a1a',
                'text' => '#fef3c7',
                'text_muted' => '#fde68a',
                'accent' => '#fbbf24',
                'border' => '#7f1d1d',
                'header_bg' => '#1a0505',
                'header_text' => '#fef9e7',
                'song_title' => '#fde68a',
                'section_label' => '#f87171',
                'lyrics_bg' => '#5c1a1a',
                'inter_text' => '#f87171',
                'font' => "Georgia, 'Times New Roman', serif",
                'heading_font' => "Georgia, 'Times New Roman', serif",
            ],
            'Formal' => [
                'bg' => '#09090b',
                'surface' => '#18181b',
                'surface2' => '#27272a',
                'text' => '#f4f4f5',
                'text_muted' => '#a1a1aa',
                'accent' => '#d4d4d8',
                'border' => '#3f3f46',
                'header_bg' => '#000000',
                'header_text' => '#ffffff',
                'song_title' => '#e4e4e7',
                'section_label' => '#71717a',
                'lyrics_bg' => '#27272a',
                'inter_text' => '#52525b',
                'font' => "Georgia, 'Times New Roman', serif",
                'heading_font' => "Georgia, 'Times New Roman', serif",
            ],
            'Minimalist' => [
                'bg' => '#fafaf9',
                'surface' => '#f5f5f4',
                'surface2' => '#e7e5e4',
                'text' => '#1c1917',
                'text_muted' => '#78716c',
                'accent' => '#57534e',
                'border' => '#d6d3d1',
                'header_bg' => '#1c1917',
                'header_text' => '#fafaf9',
                'song_title' => '#292524',
                'section_label' => '#a8a29e',
                'lyrics_bg' => '#e7e5e4',
                'inter_text' => '#a8a29e',
                'font' => "system-ui, 'Segoe UI', sans-serif",
                'heading_font' => "Georgia, 'Times New Roman', serif",
            ],
        ];
    }
}
