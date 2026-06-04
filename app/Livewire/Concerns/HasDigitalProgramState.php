<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\DigitalProgramHonor;
use App\Models\DigitalProgramRoster;
use App\Models\Program;
use App\Models\SongTitle;
use App\Models\UserSongLyrics;

trait HasDigitalProgramState
{
    // ─── Style ────────────────────────────────────────────────────────────────

    public string $theme = 'Formal';

    public string $printOrientation = 'Portrait';

    // ─── Content ──────────────────────────────────────────────────────────────

    public string $welcomeMessage = '';

    public string $acknowledgments = '';

    public string $sponsorText = '';

    public ?int $intermissionAfterEnsemble = null;

    // ─── Songs ────────────────────────────────────────────────────────────────

    /** @var list<array{songTitleId: int, title: string, composer: string, hasLyrics: bool, showLyrics: bool}> */
    public array $songSettings = [];

    public bool $lyricsCopyrightAcknowledged = false;

    // ─── Roster ───────────────────────────────────────────────────────────────

    public bool $studentNamesAcknowledged = false;

    /** @var array<string, list<array{label: string}>> */
    public array $honors = [];

    /** @var array<string, list<array{student_name: string, voice_part: string, honorIndexes: list<int>}>> */
    public array $rosters = [];

    // ─── Theme definitions ────────────────────────────────────────────────────

    /** @var array<string, array{label: string, swatch: string, description: string}> */
    public static array $themes = [
        'WinterConcert' => ['label' => 'Winter Concert', 'swatch' => '#1e293b', 'description' => 'Deep navy with icy silver accents'],
        'SpringFestival' => ['label' => 'Spring Festival', 'swatch' => '#f0fdf4', 'description' => 'Fresh greens with warm coral'],
        'Graduation' => ['label' => 'Graduation', 'swatch' => '#172554', 'description' => 'Classic navy and gold'],
        'Holiday' => ['label' => 'Holiday', 'swatch' => '#450a0a', 'description' => 'Rich burgundy with golden warmth'],
        'Formal' => ['label' => 'Formal', 'swatch' => '#09090b', 'description' => 'Timeless black and silver'],
        'Minimalist' => ['label' => 'Minimalist', 'swatch' => '#fafaf9', 'description' => 'Clean off-white and stone'],
    ];

    // ─── Roster mutations ────────────────────────────────────────────────────

    public function addHonor(string $ensembleKey): void
    {
        $this->honors[$ensembleKey][] = ['label' => ''];
    }

    public function removeHonor(string $ensembleKey, int $index): void
    {
        foreach ($this->rosters[$ensembleKey] ?? [] as $si => $student) {
            $updated = [];

            foreach ($student['honorIndexes'] as $hi) {
                if ($hi === $index) {
                    continue;
                }

                $updated[] = $hi > $index ? $hi - 1 : $hi;
            }

            $this->rosters[$ensembleKey][$si]['honorIndexes'] = $updated;
        }

        array_splice($this->honors[$ensembleKey], $index, 1);
    }

    public function addStudent(string $ensembleKey): void
    {
        $this->rosters[$ensembleKey][] = [
            'student_name' => '',
            'voice_part' => '',
            'honorIndexes' => [],
        ];
    }

    public function removeStudent(string $ensembleKey, int $index): void
    {
        array_splice($this->rosters[$ensembleKey], $index, 1);
    }

    // ─── State queries ────────────────────────────────────────────────────────

    public function anyLyricsEnabled(): bool
    {
        foreach ($this->songSettings as $setting) {
            if ($setting['showLyrics']) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyStudents(): bool
    {
        foreach ($this->rosters as $students) {
            foreach ($students as $student) {
                if (! empty(trim((string) ($student['student_name'] ?? '')))) {
                    return true;
                }
            }
        }

        return false;
    }

    // ─── Initialization helpers ───────────────────────────────────────────────

    protected function initializeSongSettings(int $programId): void
    {
        $userId = auth()->id();
        $songTitles = Program::find($programId)?->songTitles()->with('composer')->get() ?? collect();
        $songTitleIds = $songTitles->pluck('id')->all();

        $lyricsIds = array_flip(
            UserSongLyrics::query()
                ->where('user_id', $userId)
                ->whereIn('song_title_id', $songTitleIds)
                ->pluck('song_title_id')
                ->all()
        );

        $this->songSettings = $songTitles->map(fn (SongTitle $st) => [
            'songTitleId' => $st->id,
            'title' => $st->song_title,
            'composer' => $st->composer !== null ? $st->composer->artist_name : '',
            'hasLyrics' => isset($lyricsIds[$st->id]),
            'showLyrics' => false,
        ])->values()->all();
    }

    protected function initializeRosterData(int $programId): void
    {
        $program = Program::find($programId);

        if (! $program) {
            return;
        }

        $this->honors = [];
        $this->rosters = [];

        foreach ($program->ensembles()->get() as $ensemble) {
            $key = (string) $ensemble->id;
            $this->honors[$key] = [];
            $this->rosters[$key] = [];
        }

        if ($program->songTitles()->wherePivotNull('ensemble_id')->exists()) {
            $this->honors['general'] = [];
            $this->rosters['general'] = [];
        }
    }

    // ─── Persistence helpers ──────────────────────────────────────────────────

    protected function saveHonorsAndRosters(int $digitalProgramId): void
    {
        DigitalProgramHonor::where('digital_program_id', $digitalProgramId)->delete();
        DigitalProgramRoster::where('digital_program_id', $digitalProgramId)->delete();

        foreach ($this->honors as $ensembleKey => $ensembleHonors) {
            $ensembleId = $ensembleKey === 'general' ? null : (int) $ensembleKey;
            $savedHonorIds = [];

            foreach ($ensembleHonors as $index => $honor) {
                if (empty(trim((string) ($honor['label'] ?? '')))) {
                    continue;
                }

                $created = DigitalProgramHonor::create([
                    'digital_program_id' => $digitalProgramId,
                    'ensemble_id' => $ensembleId,
                    'label' => trim((string) $honor['label']),
                    'sort_order' => $index + 1,
                ]);

                $savedHonorIds[$index] = $created->id;
            }

            $sortOrder = 0;

            foreach ($this->rosters[$ensembleKey] ?? [] as $student) {
                if (empty(trim((string) ($student['student_name'] ?? '')))) {
                    continue;
                }

                $roster = DigitalProgramRoster::create([
                    'digital_program_id' => $digitalProgramId,
                    'ensemble_id' => $ensembleId,
                    'voice_part' => ($student['voice_part'] ?? '') ?: null,
                    'student_name' => trim((string) $student['student_name']),
                    'sort_order' => ++$sortOrder,
                ]);

                $honorIds = array_values(array_filter(
                    array_map(
                        fn (int $i) => $savedHonorIds[$i] ?? null,
                        array_map('intval', $student['honorIndexes'] ?? [])
                    )
                ));

                if (! empty($honorIds)) {
                    $roster->honors()->attach($honorIds);
                }
            }
        }
    }

    // ─── Validation ──────────────────────────────────────────────────────────

    protected function themeRules(): array
    {
        return [
            'theme' => ['required', 'in:'.implode(',', array_keys(self::$themes))],
            'printOrientation' => ['required', 'in:Portrait,Landscape'],
        ];
    }
}
