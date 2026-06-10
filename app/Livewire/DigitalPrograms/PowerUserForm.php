<?php

declare(strict_types=1);

namespace App\Livewire\DigitalPrograms;

use App\Livewire\Concerns\HasDigitalProgramState;
use App\Models\DigitalProgram;
use App\Models\DigitalProgramRoster;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PowerUserForm extends Component
{
    use HasDigitalProgramState;

    // ─── Program selection ────────────────────────────────────────────────────

    public string $startChoice = ''; // 'existing' | 'new'

    public ?int $selectedProgramId = null;

    public string $newEventName = '';

    public string $newEventDate = '';

    public string $newDirectorName = '';

    public string $newSchoolName = '';

    // ─── Resolved state ───────────────────────────────────────────────────────

    public bool $programLoaded = false;

    public ?int $resolvedProgramId = null;

    public ?int $digitalProgramId = null;

    // ─── Mount ───────────────────────────────────────────────────────────────

    public function mount(?DigitalProgram $digitalProgram = null): void
    {
        if (! $digitalProgram || ! $digitalProgram->exists) {
            return;
        }

        abort_unless($digitalProgram->user_id === auth()->user()->digitalProgramsOwnerId(), 403);

        $this->startChoice = 'existing';
        $this->selectedProgramId = $digitalProgram->program_id;
        $this->loadProgram();
    }

    // ─── Actions ─────────────────────────────────────────────────────────────

    public function loadProgram(): void
    {
        $this->validate($this->programSelectionRules());

        if ($this->startChoice === 'existing') {
            $program = Program::query()
                ->where('id', $this->selectedProgramId)
                ->where('user_id', auth()->user()->digitalProgramsOwnerId())
                ->firstOrFail();

            $this->resolvedProgramId = $program->id;
        } else {
            // For "new", just lock in the header data — the Program record is
            // created on Save to avoid orphaned records if the user abandons.
            $this->resolvedProgramId = null;
        }

        if ($this->resolvedProgramId) {
            $this->initializeWizardEnsembles($this->resolvedProgramId);

            // Prefer an existing digital program that has content (same ordering as the wizard).
            $existingDp = DigitalProgram::where('user_id', auth()->user()->digitalProgramsOwnerId())
                ->where('program_id', $this->resolvedProgramId)
                ->orderByRaw('CASE WHEN welcome_message IS NOT NULL OR acknowledgments IS NOT NULL OR sponsor_text IS NOT NULL THEN 1 ELSE 0 END DESC')
                ->orderByDesc('updated_at')
                ->first();

            if ($existingDp) {
                $this->digitalProgramId = $existingDp->id;
                $this->welcomeMessage = $existingDp->welcome_message ?? '';
                $this->acknowledgments = $existingDp->acknowledgments ?? '';
                $this->sponsorText = $existingDp->sponsor_text ?? '';
                $this->intermissionAfterEnsemble = $existingDp->intermission_after_ensemble;
                $this->theme = $existingDp->theme ?? $this->theme;
                $this->printOrientation = $existingDp->print_orientation ?? $this->printOrientation;
                $this->lyricsCopyrightAcknowledged = (bool) $existingDp->lyrics_copyright_acknowledged;
                $this->studentNamesAcknowledged = (bool) $existingDp->student_names_acknowledged;

                // Sync showLyrics and programNotes from any previously saved song settings.
                $existingDp->loadMissing('songSettings');
                $settingsMap = $existingDp->songSettings->keyBy('song_title_id');

                foreach ($this->ensembleSongs as $ensIdx => $songs) {
                    foreach ($songs as $songIdx => $song) {
                        $stId = $song['songTitleId'] ?? null;

                        if ($stId && $settingsMap->has($stId)) {
                            $this->ensembleSongs[$ensIdx][$songIdx]['showLyrics'] =
                                (bool) $settingsMap[$stId]->show_lyrics;
                            $this->ensembleSongs[$ensIdx][$songIdx]['programNotes'] =
                                $settingsMap[$stId]->program_notes ?? '';
                        }
                    }
                }

                $this->loadExistingRosterAndHonors($existingDp);
            } else {
                $this->initializeRosterData($this->resolvedProgramId);
            }
        }

        $this->programLoaded = true;
    }

    public function save(bool $publish = false): void
    {
        abort_if($publish && auth()->user()->isAssistant(), 403);

        $this->validate($this->formRules());

        DB::transaction(function () use ($publish): void {
            // 1. Resolve or create the Program
            if ($this->startChoice === 'existing') {
                $program = Program::query()
                    ->where('id', $this->selectedProgramId)
                    ->where('user_id', auth()->user()->digitalProgramsOwnerId())
                    ->firstOrFail();
            } else {
                $school = School::firstOrCreate(['school_name' => trim($this->newSchoolName)]);
                auth()->user()->digitalProgramsOwner()->schools()->syncWithoutDetaching([$school->id]);

                $program = Program::firstOrCreate(
                    [
                        'user_id' => auth()->user()->digitalProgramsOwnerId(),
                        'event_name' => trim($this->newEventName),
                        'event_date' => $this->newEventDate,
                    ],
                    [
                        'school_id' => $school->id,
                        'director_name' => trim($this->newDirectorName),
                    ]
                );
            }

            $dpData = [
                'theme' => $this->theme,
                'print_orientation' => $this->printOrientation,
                'welcome_message' => $this->welcomeMessage ?: null,
                'acknowledgments' => $this->acknowledgments ?: null,
                'sponsor_text' => $this->sponsorText ?: null,
                'intermission_after_ensemble' => $this->intermissionAfterEnsemble,
                'lyrics_copyright_acknowledged' => $this->lyricsCopyrightAcknowledged,
                'student_names_acknowledged' => $this->studentNamesAcknowledged,
                'is_published' => $publish,
            ];

            // 2. Update existing DigitalProgram or create a new one
            if ($this->digitalProgramId) {
                $dp = DigitalProgram::findOrFail($this->digitalProgramId);

                if (auth()->user()->isAssistant()) {
                    $dpData['is_published'] = $dp->is_published;
                }

                $dp->update($dpData);
            } else {
                $dp = DigitalProgram::create(array_merge($dpData, [
                    'user_id' => auth()->user()->digitalProgramsOwnerId(),
                    'program_id' => $program->id,
                ]));
            }

            // 3. Persist any new ensembles before saving songs
            $schoolId = $program->school_id;

            foreach ($this->wizardEnsembles as $index => $ens) {
                if ($ens['id'] === null && $schoolId !== null && ! empty(trim($ens['name']))) {
                    $ensemble = Ensemble::firstOrCreate(
                        ['school_id' => $schoolId, 'ensemble_name' => trim($ens['name'])],
                        ['type' => $ens['type'], 'a_cappella' => false]
                    );
                    $this->wizardEnsembles[$index]['id'] = $ensemble->id;
                }
            }

            // 4. Save songs and song settings
            $this->saveWizardEnsemblesAndSongs($program->id, $dp->id);

            // 5. Honors & rosters
            $this->saveHonorsAndRosters($dp->id);
        });

        $message = $publish ? 'Digital program published!' : 'Draft saved.';
        session()->flash('success', $message);
        $this->redirectRoute('digital-programs.index');
    }

    // ─── Song CSV ────────────────────────────────────────────────────────────

    public function downloadSongsCsvTemplate(): StreamedResponse
    {
        $columns = ['Ensemble', 'Song Title', 'Composer', 'Arranger', 'Show Lyrics', 'Program Notes'];

        return response()->streamDownload(function () use ($columns): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);

            $hasSongs = false;

            foreach ($this->wizardEnsembles as $ensIdx => $ens) {
                foreach ($this->ensembleSongs[$ensIdx] ?? [] as $song) {
                    if (! empty(trim($song['title'] ?? ''))) {
                        fputcsv($out, [
                            $ens['name'],
                            $song['title'],
                            $song['composer'] ?? '',
                            $song['arranger'] ?? '',
                            ($song['showLyrics'] ?? false) ? '1' : '0',
                            $song['programNotes'] ?? '',
                        ]);
                        $hasSongs = true;
                    }
                }
            }

            if (! $hasSongs) {
                fputcsv($out, ['Concert Choir', 'Ave Maria', 'Franz Schubert', '', '0', '']);
                fputcsv($out, ['Concert Choir', 'Gloria', 'Antonio Vivaldi', '', '0', 'Soloist: Jane Smith']);
            }

            fclose($out);
        }, 'songs-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function processSongsCsv(string $csvContent): void
    {
        // Strip UTF-8 BOM if present (added by Excel and some other tools).
        $csvContent = ltrim($csvContent, "\xEF\xBB\xBF");
        $content = str_replace(["\r\n", "\r"], "\n", $csvContent);
        $lines = array_values(array_filter(
            array_map('trim', explode("\n", $content)),
            fn (string $l) => $l !== ''
        ));

        if (count($lines) < 2) {
            $this->songsCsvResult = [
                'type' => 'error',
                'message' => __('The file must contain a header row and at least one song row.'),
            ];

            return;
        }

        $headers = array_map('trim', str_getcsv(array_shift($lines)));

        $ensembleCol = null;
        $titleCol = null;
        $composerCol = null;
        $arrangerCol = null;
        $lyricsCol = null;
        $notesCol = null;

        foreach ($headers as $ci => $header) {
            $lower = strtolower($header);

            if (in_array($lower, ['ensemble', 'ensemble name'], true)) {
                $ensembleCol = $ci;
            } elseif (in_array($lower, ['song title', 'title', 'song'], true)) {
                $titleCol = $ci;
            } elseif ($lower === 'composer') {
                $composerCol = $ci;
            } elseif ($lower === 'arranger') {
                $arrangerCol = $ci;
            } elseif (in_array($lower, ['show lyrics', 'lyrics'], true)) {
                $lyricsCol = $ci;
            } elseif (in_array($lower, ['program notes', 'notes'], true)) {
                $notesCol = $ci;
            }
        }

        if ($titleCol === null) {
            $this->songsCsvResult = [
                'type' => 'error',
                'message' => __('CSV must include a "Song Title" column.'),
            ];

            return;
        }

        $byEnsemble = [];

        foreach ($lines as $line) {
            $row = str_getcsv($line);
            $title = trim($row[$titleCol] ?? '');

            if ($title === '') {
                continue;
            }

            $ensembleName = $ensembleCol !== null ? trim($row[$ensembleCol] ?? '') : '';

            $byEnsemble[$ensembleName][] = [
                'songTitleId' => null,
                'title' => $title,
                'composer' => trim($row[$composerCol] ?? ''),
                'arranger' => trim($row[$arrangerCol] ?? ''),
                'showLyrics' => in_array(strtolower(trim($row[$lyricsCol] ?? '')), ['1', 'yes', 'true', 'x', 'y'], true),
                'programNotes' => trim($row[$notesCol] ?? ''),
            ];
        }

        // Try to match uploaded ensemble names to existing school ensembles.
        $knownEnsembles = collect();
        $schoolId = null;

        if ($this->resolvedProgramId) {
            $schoolId = Program::find($this->resolvedProgramId)?->school_id;

            if ($schoolId !== null) {
                $knownEnsembles = Ensemble::where('school_id', $schoolId)->get()->keyBy('ensemble_name');
            }
        }

        // Replace all previous entries.
        $this->wizardEnsembles = [];
        $this->ensembleSongs = [];

        foreach ($byEnsemble as $ensembleName => $songs) {
            $name = $ensembleName !== '' ? $ensembleName : 'General';
            $known = $knownEnsembles->get($name);

            $idx = count($this->wizardEnsembles);
            $this->wizardEnsembles[] = [
                'id' => $known?->id,
                'name' => $name,
                'type' => $known?->type->value ?? 'Satb',
            ];
            $this->ensembleSongs[$idx] = $songs;
        }

        // Persist any still-unresolved ensembles immediately so the Roster section can key on real IDs.
        if ($schoolId !== null) {
            foreach ($this->wizardEnsembles as $idx => $ens) {
                if ($ens['id'] === null && ! empty(trim($ens['name']))) {
                    $ensemble = Ensemble::firstOrCreate(
                        ['school_id' => $schoolId, 'ensemble_name' => trim($ens['name'])],
                        ['type' => $ens['type'], 'a_cappella' => false]
                    );
                    $this->wizardEnsembles[$idx]['id'] = $ensemble->id;
                }
            }
        }

        // Sync honors/rosters — preserve existing data for retained ensembles, seed empty entries for new ones.
        $updatedHonors = [];
        $updatedRosters = [];

        if (isset($this->honors['general'])) {
            $updatedHonors['general'] = $this->honors['general'];
            $updatedRosters['general'] = $this->rosters['general'] ?? [];
        }

        foreach ($this->wizardEnsembles as $ens) {
            if ($ens['id'] !== null) {
                $key = (string) $ens['id'];
                $updatedHonors[$key] = $this->honors[$key] ?? [];
                $updatedRosters[$key] = $this->rosters[$key] ?? [];
            }
        }

        $this->honors = $updatedHonors;
        $this->rosters = $updatedRosters;

        $count = array_sum(array_map('count', $this->ensembleSongs));
        $this->songsCsvResult = [
            'type' => 'success',
            'message' => trans_choice(':count song imported.|:count songs imported.', $count, ['count' => $count]),
        ];
    }

    // ─── Ensemble mutations (Power User override) ────────────────────────────

    public function createWizardEnsemble(): void
    {
        if (empty(trim($this->newEnsembleName))) {
            $this->addError('newEnsembleName', 'Ensemble name is required.');

            return;
        }

        // Persist immediately when a program is loaded so we get a real ID for roster keying.
        $ensembleId = null;

        if ($this->resolvedProgramId) {
            $schoolId = Program::find($this->resolvedProgramId)?->school_id;

            if ($schoolId !== null) {
                $ensemble = Ensemble::firstOrCreate(
                    ['school_id' => $schoolId, 'ensemble_name' => trim($this->newEnsembleName)],
                    ['type' => $this->newEnsembleType, 'a_cappella' => false]
                );
                $ensembleId = $ensemble->id;
            }
        }

        $index = count($this->wizardEnsembles);
        $this->wizardEnsembles[] = [
            'id' => $ensembleId,
            'name' => trim($this->newEnsembleName),
            'type' => $this->newEnsembleType,
        ];
        $this->ensembleSongs[$index] = [];

        // Seed an empty roster entry if we have a real ID.
        if ($ensembleId !== null) {
            $key = (string) $ensembleId;

            if (! isset($this->honors[$key])) {
                $this->honors[$key] = [];
                $this->rosters[$key] = [];
            }
        }

        $this->newEnsembleName = '';
        $this->showNewEnsembleForm = false;
    }

    // ─── Validation ──────────────────────────────────────────────────────────

    private function programSelectionRules(): array
    {
        $rules = ['startChoice' => ['required', 'in:existing,new']];

        if ($this->startChoice === 'existing') {
            $rules['selectedProgramId'] = ['required', 'integer', 'exists:programs,id'];
        } else {
            $rules['newEventName'] = ['required', 'string', 'max:255'];
            $rules['newEventDate'] = ['required', 'date'];
            $rules['newDirectorName'] = ['required', 'string', 'max:255'];
            $rules['newSchoolName'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }

    private function formRules(): array
    {
        $rules = array_merge(
            $this->programSelectionRules(),
            $this->themeRules()
        );

        if ($this->anyLyricsEnabled()) {
            $rules['lyricsCopyrightAcknowledged'] = ['accepted'];
        }

        if ($this->hasAnyStudents()) {
            $rules['studentNamesAcknowledged'] = ['accepted'];
        }

        return $rules;
    }

    // ─── Render ──────────────────────────────────────────────────────────────

    public function render(): View
    {
        $userPrograms = Program::query()
            ->where('user_id', auth()->user()->digitalProgramsOwnerId())
            ->with('school')
            ->orderByDesc('event_date')
            ->get();

        $resolvedProgram = $this->resolvedProgramId
            ? Program::find($this->resolvedProgramId)
            : null;

        $resolvedProgram?->load(['school', 'ensembles']);
        $ensembles = $resolvedProgram !== null ? $resolvedProgram->ensembles : collect();

        $school = $resolvedProgram?->school;
        $schoolEnsembles = $school !== null
            ? Ensemble::where('school_id', $school->id)->orderBy('ensemble_name')->get()
            : collect();

        $digitalProgram = $this->digitalProgramId
            ? DigitalProgram::find($this->digitalProgramId)
            : null;

        return view('livewire.digital-programs.power-user-form', [
            'userPrograms' => $userPrograms,
            'resolvedProgram' => $resolvedProgram,
            'ensembles' => $ensembles,
            'schoolEnsembles' => $schoolEnsembles,
            'digitalProgram' => $digitalProgram,
            'themes' => self::$themes,
            'voiceParts' => DigitalProgramRoster::VOICE_PARTS,
        ])->layout('components.layouts.app', ['title' => __('Create Digital Program — Power User')]);
    }
}
