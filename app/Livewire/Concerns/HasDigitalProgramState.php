<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\Artist;
use App\Models\DigitalProgram;
use App\Models\DigitalProgramHonor;
use App\Models\DigitalProgramRoster;
use App\Models\DigitalProgramSongSetting;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\SongTitle;
use App\Models\UserSongLyrics;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    // ─── Wizard Ensembles (Step 4) ────────────────────────────────────────────

    /** @var list<array{id: int|null, name: string, type: string}> */
    public array $wizardEnsembles = [];

    public string $newEnsembleName = '';

    public string $newEnsembleType = 'Satb';

    public bool $showNewEnsembleForm = false;

    // ─── Ensemble Songs (Step 5) ──────────────────────────────────────────────

    /**
     * Songs per wizard ensemble, indexed by the same integer as $wizardEnsembles.
     *
     * @var array<int, list<array{songTitleId: int|null, title: string, composer: string, arranger: string, showLyrics: bool}>>
     */
    public array $ensembleSongs = [];

    public bool $lyricsCopyrightAcknowledged = false;

    // ─── Songs (Configure / load-from-existing path) ──────────────────────────

    /** @var list<array{songTitleId: int, title: string, composer: string, hasLyrics: bool, showLyrics: bool}> */
    public array $songSettings = [];

    // ─── Roster ───────────────────────────────────────────────────────────────

    public bool $studentNamesAcknowledged = false;

    /** @var array<string, array{type: string, message: string}> */
    public array $rosterCsvResults = [];

    /** @var array{type: string, message: string}|array{} */
    public array $songsCsvResult = [];

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

    // ─── Ensemble mutations (wizard step 4) ───────────────────────────────────

    public function addSelectedEnsemble(int $ensembleId): void
    {
        foreach ($this->wizardEnsembles as $ens) {
            if ($ens['id'] === $ensembleId) {
                return;
            }
        }

        $ensemble = Ensemble::find($ensembleId);

        if (! $ensemble) {
            return;
        }

        $index = count($this->wizardEnsembles);
        $this->wizardEnsembles[] = [
            'id' => $ensembleId,
            'name' => $ensemble->ensemble_name,
            'type' => $ensemble->type->value,
        ];
        $this->ensembleSongs[$index] = [];

        // Seed an empty roster entry so the Roster section shows this ensemble immediately.
        $key = (string) $ensembleId;

        if (! isset($this->honors[$key])) {
            $this->honors[$key] = [];
            $this->rosters[$key] = [];
        }
    }

    public function removeWizardEnsemble(int $index): void
    {
        $ensembleId = $this->wizardEnsembles[$index]['id'] ?? null;

        array_splice($this->wizardEnsembles, $index, 1);
        array_splice($this->ensembleSongs, $index, 1);
        $this->ensembleSongs = array_values($this->ensembleSongs);

        if ($ensembleId !== null) {
            $key = (string) $ensembleId;
            unset($this->honors[$key], $this->rosters[$key]);
        }
    }

    public function moveWizardEnsembleUp(int $index): void
    {
        if ($index <= 0) {
            return;
        }

        [$this->wizardEnsembles[$index - 1], $this->wizardEnsembles[$index]] =
            [$this->wizardEnsembles[$index], $this->wizardEnsembles[$index - 1]];

        [$this->ensembleSongs[$index - 1], $this->ensembleSongs[$index]] =
            [$this->ensembleSongs[$index] ?? [], $this->ensembleSongs[$index - 1] ?? []];
    }

    public function moveWizardEnsembleDown(int $index): void
    {
        if ($index >= count($this->wizardEnsembles) - 1) {
            return;
        }

        [$this->wizardEnsembles[$index], $this->wizardEnsembles[$index + 1]] =
            [$this->wizardEnsembles[$index + 1], $this->wizardEnsembles[$index]];

        [$this->ensembleSongs[$index], $this->ensembleSongs[$index + 1]] =
            [$this->ensembleSongs[$index + 1] ?? [], $this->ensembleSongs[$index] ?? []];
    }

    public function createWizardEnsemble(): void
    {
        if (empty(trim($this->newEnsembleName))) {
            $this->addError('newEnsembleName', 'Ensemble name is required.');

            return;
        }

        $index = count($this->wizardEnsembles);
        $this->wizardEnsembles[] = [
            'id' => null,
            'name' => trim($this->newEnsembleName),
            'type' => $this->newEnsembleType,
        ];
        $this->ensembleSongs[$index] = [];
        $this->newEnsembleName = '';
        $this->showNewEnsembleForm = false;
    }

    // ─── Song mutations (wizard step 5) ───────────────────────────────────────

    public function addSongRow(int $ensembleIndex): void
    {
        $this->ensembleSongs[$ensembleIndex][] = [
            'songTitleId' => null,
            'title' => '',
            'composer' => '',
            'arranger' => '',
            'showLyrics' => false,
            'programNotes' => '',
        ];

        $this->dispatch('focus-song-title',
            ensembleIndex: $ensembleIndex,
            songIndex: count($this->ensembleSongs[$ensembleIndex]) - 1,
        );
    }

    public function removeSongRow(int $ensembleIndex, int $songIndex): void
    {
        array_splice($this->ensembleSongs[$ensembleIndex], $songIndex, 1);
    }

    public function moveSongRowUp(int $ensembleIndex, int $songIndex): void
    {
        if ($songIndex <= 0) {
            return;
        }

        [$this->ensembleSongs[$ensembleIndex][$songIndex - 1], $this->ensembleSongs[$ensembleIndex][$songIndex]] =
            [$this->ensembleSongs[$ensembleIndex][$songIndex], $this->ensembleSongs[$ensembleIndex][$songIndex - 1]];
    }

    public function moveSongRowDown(int $ensembleIndex, int $songIndex): void
    {
        if ($songIndex >= count($this->ensembleSongs[$ensembleIndex]) - 1) {
            return;
        }

        [$this->ensembleSongs[$ensembleIndex][$songIndex], $this->ensembleSongs[$ensembleIndex][$songIndex + 1]] =
            [$this->ensembleSongs[$ensembleIndex][$songIndex + 1], $this->ensembleSongs[$ensembleIndex][$songIndex]];
    }

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

    public function toggleHonorIndex(string $ensembleKey, int $studentIndex, int $honorIndex): void
    {
        $current = array_map('intval', $this->rosters[$ensembleKey][$studentIndex]['honorIndexes'] ?? []);

        if (in_array($honorIndex, $current, true)) {
            $this->rosters[$ensembleKey][$studentIndex]['honorIndexes'] = array_values(
                array_filter($current, fn (int $v) => $v !== $honorIndex)
            );
        } else {
            $current[] = $honorIndex;
            $this->rosters[$ensembleKey][$studentIndex]['honorIndexes'] = $current;
        }
    }

    // ─── Roster CSV ──────────────────────────────────────────────────────────

    public function downloadRosterTemplate(string $ensembleKey): StreamedResponse
    {
        $columns = ['Student Name', 'Voice Part'];

        foreach ($this->honors[$ensembleKey] ?? [] as $honor) {
            $label = trim($honor['label'] ?? '');

            if ($label !== '') {
                $columns[] = $label;
            }
        }

        $slugName = 'roster';

        foreach ($this->wizardEnsembles as $ens) {
            if (isset($ens['id']) && (string) $ens['id'] === $ensembleKey) {
                $slugName = Str::slug($ens['name']);
                break;
            }
        }

        return response()->streamDownload(function () use ($columns): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);

            // Example row
            $example = ['Jane Smith', 'Soprano I'];

            foreach (array_slice($columns, 2) as $_) {
                $example[] = '1';
            }

            fputcsv($out, $example);
            fclose($out);
        }, $slugName.'-roster-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function processRosterCsv(string $ensembleKey, string $csvContent): void
    {
        $content = str_replace(["\r\n", "\r"], "\n", $csvContent);
        $lines = array_values(array_filter(
            array_map('trim', explode("\n", $content)),
            fn (string $l) => $l !== ''
        ));

        if (count($lines) < 2) {
            $this->rosterCsvResults[$ensembleKey] = [
                'type' => 'error',
                'message' => __('The file must contain a header row and at least one student row.'),
            ];

            return;
        }

        $headers = array_map('trim', str_getcsv(array_shift($lines)));

        $nameCol = null;
        $voiceCol = null;
        $honorColMap = []; // CSV column index → $honors array index

        foreach ($headers as $ci => $header) {
            $lower = strtolower($header);

            if (in_array($lower, ['student name', 'name', 'student'], true)) {
                $nameCol = $ci;
            } elseif (in_array($lower, ['voice part', 'voice', 'part', 'section'], true)) {
                $voiceCol = $ci;
            } else {
                foreach ($this->honors[$ensembleKey] ?? [] as $hi => $honor) {
                    $label = trim($honor['label'] ?? '');

                    if ($label !== '' && strtolower($label) === $lower) {
                        $honorColMap[$ci] = $hi;
                        break;
                    }
                }
            }
        }

        if ($nameCol === null) {
            $this->rosterCsvResults[$ensembleKey] = [
                'type' => 'error',
                'message' => __('CSV must include a "Student Name" column.'),
            ];

            return;
        }

        $validParts = DigitalProgramRoster::VOICE_PARTS;
        $imported = [];

        foreach ($lines as $line) {
            $row = str_getcsv($line);
            $name = trim($row[$nameCol] ?? '');

            if ($name === '') {
                continue;
            }

            $voicePart = '';

            if ($voiceCol !== null) {
                $raw = trim($row[$voiceCol] ?? '');

                if (in_array($raw, $validParts, true)) {
                    $voicePart = $raw;
                }
            }

            $honorIndexes = [];

            foreach ($honorColMap as $ci => $hi) {
                $val = strtolower(trim($row[$ci] ?? ''));

                if (in_array($val, ['1', 'yes', 'true', 'x', 'y'], true)) {
                    $honorIndexes[] = $hi;
                }
            }

            $imported[] = [
                'student_name' => $name,
                'voice_part' => $voicePart,
                'honorIndexes' => $honorIndexes,
            ];
        }

        $this->rosters[$ensembleKey] = $imported;

        $this->rosterCsvResults[$ensembleKey] = [
            'type' => 'success',
            'message' => trans_choice(
                ':count student imported.|:count students imported.',
                count($imported),
                ['count' => count($imported)]
            ),
        ];
    }

    // ─── State queries ────────────────────────────────────────────────────────

    public function anyLyricsEnabled(): bool
    {
        foreach ($this->songSettings as $setting) {
            if ($setting['showLyrics']) {
                return true;
            }
        }

        foreach ($this->ensembleSongs as $songs) {
            foreach ($songs as $song) {
                if (! empty($song['showLyrics'])) {
                    return true;
                }
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

    protected function initializeWizardEnsembles(int $programId): void
    {
        $this->wizardEnsembles = [];
        $this->ensembleSongs = [];

        $program = Program::find($programId);

        if (! $program) {
            return;
        }

        // Load all songs ordered by persisted ensemble position, then song position.
        // Grouping preserves insertion order so ensembles appear in the saved sequence.
        $allSongs = $program->songTitles()
            ->with('composer', 'arranger')
            ->orderByPivot('ensemble_sort_order')
            ->orderByPivot('sort_order')
            ->get();

        $ensembleIds = $allSongs
            ->pluck('pivot.ensemble_id')
            ->filter()
            ->unique()
            ->values();

        $ensemblesById = Ensemble::whereIn('id', $ensembleIds->all())->get()->keyBy('id');

        foreach ($ensembleIds as $ensembleId) {
            $ensemble = $ensemblesById[$ensembleId] ?? null;

            if (! $ensemble) {
                continue;
            }

            $index = count($this->wizardEnsembles);
            $this->wizardEnsembles[] = [
                'id' => $ensemble->id,
                'name' => $ensemble->ensemble_name,
                'type' => $ensemble->type->value,
            ];

            $this->ensembleSongs[$index] = $allSongs
                ->filter(fn (SongTitle $st) => $st->pivot->ensemble_id === $ensemble->id)
                ->map(fn (SongTitle $st) => [
                    'songTitleId' => $st->id,
                    'title' => $st->song_title,
                    'composer' => $st->composer !== null ? $st->composer->artist_name : '',
                    'arranger' => $st->arranger !== null ? $st->arranger->artist_name : '',
                    'showLyrics' => false,
                    'programNotes' => '',
                ])->values()->all();
        }
    }

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

        foreach ($program->ensembles()->orderByPivot('ensemble_sort_order')->get() as $ensemble) {
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

    protected function saveWizardEnsemblesAndSongs(int $programId, int $digitalProgramId): void
    {
        $program = Program::findOrFail($programId);
        $syncData = [];
        $songLyricsMap = [];

        foreach ($this->wizardEnsembles as $index => $ens) {
            if ($ens['id'] === null) {
                // Ensemble was not yet persisted (school_id was unavailable in step 4).
                // Skip — it cannot be linked to songs without a real id.
                continue;
            }

            $ensembleId = $ens['id'];

            $sortOrder = 0;

            foreach ($this->ensembleSongs[$index] ?? [] as $songData) {
                $title = trim($songData['title'] ?? '');

                if ($title === '') {
                    continue;
                }

                $composerId = null;
                $composerName = trim($songData['composer'] ?? '');

                if ($composerName !== '') {
                    $artist = Artist::firstOrCreate(['artist_name' => $composerName]);
                    $composerId = $artist->id;
                }

                $arrangerId = null;
                $arrangerName = trim($songData['arranger'] ?? '');

                if ($arrangerName !== '') {
                    $artist = Artist::firstOrCreate(['artist_name' => $arrangerName]);
                    $arrangerId = $artist->id;
                }

                $songTitle = SongTitle::firstOrCreate(
                    ['song_title' => $title],
                    ['composer_id' => $composerId, 'arranger_id' => $arrangerId]
                );

                $syncData[$songTitle->id] = [
                    'ensemble_id' => $ensembleId,
                    'sort_order' => ++$sortOrder,
                    'ensemble_sort_order' => $index + 1,
                ];

                $songLyricsMap[$songTitle->id] = [
                    'show_lyrics' => (bool) ($songData['showLyrics'] ?? false),
                    'program_notes' => trim($songData['programNotes'] ?? '') ?: null,
                ];
            }
        }

        $program->songTitles()->sync($syncData);

        DigitalProgramSongSetting::where('digital_program_id', $digitalProgramId)->delete();

        foreach ($songLyricsMap as $songTitleId => $data) {
            DigitalProgramSongSetting::create([
                'digital_program_id' => $digitalProgramId,
                'song_title_id' => $songTitleId,
                'show_lyrics' => $data['show_lyrics'],
                'program_notes' => $data['program_notes'],
            ]);
        }
    }

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

    // ─── Load-from-existing helpers (used by Configure component) ────────────

    protected function loadExistingSongSettings(DigitalProgram $dp): void
    {
        if (! $dp->program_id) {
            $this->songSettings = [];

            return;
        }

        $this->initializeSongSettings($dp->program_id);

        $existing = $dp->songSettings->keyBy('song_title_id');

        foreach ($this->songSettings as &$setting) {
            if ($existing->has($setting['songTitleId'])) {
                $setting['showLyrics'] = (bool) $existing[$setting['songTitleId']]->show_lyrics;
            }
        }
    }

    protected function loadExistingRosterAndHonors(DigitalProgram $dp): void
    {
        $dp->load(['honors', 'rosters.honors']);

        if ($dp->program_id) {
            $this->initializeRosterData($dp->program_id);
        }

        // Populate honors from DB (initializeRosterData already seeded empty arrays)
        foreach ($dp->honors as $honor) {
            $key = (string) ($honor->ensemble_id ?? 'general');
            $this->honors[$key][] = ['label' => $honor->label];
        }

        // Build honor DB id → array index map per ensemble key
        $honorIdToIndex = [];

        foreach ($dp->honors->groupBy(fn ($h) => (string) ($h->ensemble_id ?? 'general')) as $key => $honors) {
            foreach ($honors->values() as $idx => $honor) {
                $honorIdToIndex[$honor->id] = $idx;
            }
        }

        // Populate rosters from DB
        foreach ($dp->rosters as $roster) {
            $key = (string) ($roster->ensemble_id ?? 'general');

            if (! isset($this->rosters[$key])) {
                $this->rosters[$key] = [];
            }

            $honorIndexes = $roster->honors
                ->map(fn ($h) => $honorIdToIndex[$h->id] ?? null)
                ->filter(fn ($i) => $i !== null)
                ->values()
                ->all();

            $this->rosters[$key][] = [
                'student_name' => $roster->student_name,
                'voice_part' => $roster->voice_part ?? '',
                'honorIndexes' => $honorIndexes,
            ];
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
