<?php

declare(strict_types=1);

namespace App\Livewire\DigitalPrograms;

use App\Livewire\Concerns\HasDigitalProgramState;
use App\Models\DigitalProgram;
use App\Models\DigitalProgramRoster;
use App\Models\DigitalProgramSongSetting;
use App\Models\Program;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

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

    // ─── Actions ─────────────────────────────────────────────────────────────

    public function loadProgram(): void
    {
        $this->validate($this->programSelectionRules());

        if ($this->startChoice === 'existing') {
            $program = Program::query()
                ->where('id', $this->selectedProgramId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $this->resolvedProgramId = $program->id;
        } else {
            // For "new", just lock in the header data — the Program record is
            // created on Save to avoid orphaned records if the user abandons.
            $this->resolvedProgramId = null;
        }

        if ($this->resolvedProgramId) {
            $this->initializeSongSettings($this->resolvedProgramId);
            $this->initializeRosterData($this->resolvedProgramId);
        }

        $this->programLoaded = true;
    }

    public function save(bool $publish = false): void
    {
        $this->validate($this->formRules());

        DB::transaction(function () use ($publish): void {
            // 1. Resolve or create the Program
            if ($this->startChoice === 'existing') {
                $program = Program::query()
                    ->where('id', $this->selectedProgramId)
                    ->where('user_id', auth()->id())
                    ->firstOrFail();
            } else {
                $school = School::firstOrCreate(['school_name' => trim($this->newSchoolName)]);
                auth()->user()->schools()->syncWithoutDetaching([$school->id]);

                $program = Program::firstOrCreate(
                    [
                        'user_id' => auth()->id(),
                        'event_name' => trim($this->newEventName),
                        'event_date' => $this->newEventDate,
                    ],
                    [
                        'school_id' => $school->id,
                        'director_name' => trim($this->newDirectorName),
                    ]
                );
            }

            // 2. Create the DigitalProgram in one shot
            $dp = DigitalProgram::create([
                'user_id' => auth()->id(),
                'program_id' => $program->id,
                'theme' => $this->theme,
                'print_orientation' => $this->printOrientation,
                'welcome_message' => $this->welcomeMessage ?: null,
                'acknowledgments' => $this->acknowledgments ?: null,
                'sponsor_text' => $this->sponsorText ?: null,
                'intermission_after_ensemble' => $this->intermissionAfterEnsemble,
                'lyrics_copyright_acknowledged' => $this->lyricsCopyrightAcknowledged,
                'student_names_acknowledged' => $this->studentNamesAcknowledged,
                'is_published' => $publish,
            ]);

            // 3. Song settings
            foreach ($this->songSettings as $setting) {
                DigitalProgramSongSetting::create([
                    'digital_program_id' => $dp->id,
                    'song_title_id' => $setting['songTitleId'],
                    'show_lyrics' => $setting['showLyrics'],
                ]);
            }

            // 4. Honors & rosters
            $this->saveHonorsAndRosters($dp->id);
        });

        $message = $publish ? 'Digital program published!' : 'Draft saved.';
        session()->flash('success', $message);
        $this->redirectRoute('digital-programs.index');
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
            ->where('user_id', auth()->id())
            ->with('school')
            ->orderByDesc('event_date')
            ->get();

        $resolvedProgram = $this->resolvedProgramId
            ? Program::find($this->resolvedProgramId)
            : null;

        $resolvedProgram?->load(['school', 'ensembles']);
        $ensembles = $resolvedProgram !== null ? $resolvedProgram->ensembles : collect();

        return view('livewire.digital-programs.power-user-form', [
            'userPrograms' => $userPrograms,
            'resolvedProgram' => $resolvedProgram,
            'ensembles' => $ensembles,
            'themes' => self::$themes,
            'voiceParts' => DigitalProgramRoster::VOICE_PARTS,
        ])->layout('components.layouts.app', ['title' => __('Create Digital Program — Power User')]);
    }
}
