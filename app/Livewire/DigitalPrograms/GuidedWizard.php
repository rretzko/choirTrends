<?php

declare(strict_types=1);

namespace App\Livewire\DigitalPrograms;

use App\Livewire\Concerns\HasDigitalProgramState;
use App\Models\DigitalProgram;
use App\Models\DigitalProgramRoster;
use App\Models\DigitalProgramSongSetting;
use App\Models\Program;
use App\Models\School;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Component;

class GuidedWizard extends Component
{
    use HasDigitalProgramState;

    public int $step = 1;

    // Step 1
    public string $startChoice = ''; // 'existing' | 'new'

    public ?int $selectedProgramId = null;

    public bool $editingExistingProgram = false;

    public string $newEventName = '';

    public string $newEventDate = '';

    public string $newDirectorName = '';

    public string $newSchoolName = '';

    // Resolved after step 1 completes
    public ?int $digitalProgramId = null;

    public ?int $resolvedProgramId = null;

    // ─── Navigation ──────────────────────────────────────────────────────────

    public function nextStep(): void
    {
        match ($this->step) {
            1 => $this->completeStep1(),
            2 => $this->completeStep2(),
            3 => $this->completeStep3(),
            4 => $this->completeStep4(),
            5 => $this->completeStep5(),
            default => null,
        };
    }

    public function previousStep(): void
    {
        if ($this->editingExistingProgram) {
            $this->editingExistingProgram = false;

            return;
        }

        if ($this->step > 1) {
            $this->step--;
        }
    }

    // ─── Step completions ────────────────────────────────────────────────────

    private function completeStep1(): void
    {
        // Phase 1 (existing path only): validate selection, load into edit fields.
        if ($this->startChoice === 'existing' && ! $this->editingExistingProgram) {
            $this->validate([
                'startChoice' => ['required', 'in:existing,new'],
                'selectedProgramId' => ['required', 'integer', 'exists:programs,id'],
            ]);

            $program = Program::query()
                ->where('id', $this->selectedProgramId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $this->newEventName = $program->event_name;
            $this->newEventDate = Carbon::parse($program->event_date)->format('Y-m-d');
            $this->newDirectorName = $program->director_name ?? '';
            $this->newSchoolName = $program->school !== null ? $program->school->school_name : '';
            $this->editingExistingProgram = true;

            return;
        }

        // Phase 2: validate edit fields, persist, advance.
        $this->validate($this->step1Rules());

        $school = School::firstOrCreate(['school_name' => trim($this->newSchoolName)]);
        auth()->user()->schools()->syncWithoutDetaching([$school->id]);

        if ($this->startChoice === 'existing') {
            $program = Program::query()
                ->where('id', $this->selectedProgramId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $program->update([
                'event_name' => trim($this->newEventName),
                'event_date' => $this->newEventDate,
                'director_name' => trim($this->newDirectorName),
                'school_id' => $school->id,
            ]);
        } else {
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

        $this->resolvedProgramId = $program->id;

        $digitalProgram = DigitalProgram::create([
            'user_id' => auth()->id(),
            'program_id' => $this->resolvedProgramId,
            'theme' => $this->theme,
            'print_orientation' => $this->printOrientation,
        ]);

        $this->digitalProgramId = $digitalProgram->id;

        $this->initializeSongSettings($this->resolvedProgramId);
        $this->initializeRosterData($this->resolvedProgramId);

        $this->step = 2;
    }

    private function completeStep2(): void
    {
        $this->validate($this->themeRules());

        $this->fetchDigitalProgram()->update([
            'theme' => $this->theme,
            'print_orientation' => $this->printOrientation,
        ]);

        $this->step = 3;
    }

    private function completeStep3(): void
    {
        $this->fetchDigitalProgram()->update([
            'welcome_message' => $this->welcomeMessage ?: null,
            'acknowledgments' => $this->acknowledgments ?: null,
            'sponsor_text' => $this->sponsorText ?: null,
            'intermission_after_ensemble' => $this->intermissionAfterEnsemble,
        ]);

        $this->step = 4;
    }

    private function completeStep4(): void
    {
        if ($this->anyLyricsEnabled() && ! $this->lyricsCopyrightAcknowledged) {
            $this->addError('lyricsCopyrightAcknowledged', 'You must acknowledge the copyright statement before enabling lyrics.');

            return;
        }

        $this->fetchDigitalProgram()->update([
            'lyrics_copyright_acknowledged' => $this->lyricsCopyrightAcknowledged,
        ]);

        foreach ($this->songSettings as $setting) {
            DigitalProgramSongSetting::updateOrCreate(
                [
                    'digital_program_id' => $this->digitalProgramId,
                    'song_title_id' => $setting['songTitleId'],
                ],
                ['show_lyrics' => $setting['showLyrics']]
            );
        }

        $this->step = 5;
    }

    private function completeStep5(): void
    {
        if ($this->hasAnyStudents() && ! $this->studentNamesAcknowledged) {
            $this->addError('studentNamesAcknowledged', 'You must acknowledge the student names statement before saving a roster.');

            return;
        }

        $this->fetchDigitalProgram()->update([
            'student_names_acknowledged' => $this->studentNamesAcknowledged,
        ]);

        $this->saveHonorsAndRosters($this->digitalProgramId);

        $this->step = 6;
    }

    // ─── Step 6 actions ──────────────────────────────────────────────────────

    public function publish(): void
    {
        $this->fetchDigitalProgram()->update(['is_published' => true]);
        session()->flash('success', 'Your digital program has been published!');
        $this->redirectRoute('digital-programs.index');
    }

    public function saveDraft(): void
    {
        session()->flash('success', 'Draft saved. You can publish it later from Create Program.');
        $this->redirectRoute('digital-programs.index');
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function fetchDigitalProgram(): DigitalProgram
    {
        return DigitalProgram::findOrFail($this->digitalProgramId);
    }

    private function resolvedProgram(): ?Program
    {
        return $this->resolvedProgramId ? Program::find($this->resolvedProgramId) : null;
    }

    private function step1Rules(): array
    {
        $rules = [
            'startChoice' => ['required', 'in:existing,new'],
            'newEventName' => ['required', 'string', 'max:255'],
            'newEventDate' => ['required', 'date'],
            'newDirectorName' => ['required', 'string', 'max:255'],
            'newSchoolName' => ['required', 'string', 'max:255'],
        ];

        if ($this->startChoice === 'existing') {
            $rules['selectedProgramId'] = ['required', 'integer', 'exists:programs,id'];
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

        $program = $this->resolvedProgram();
        $program?->load(['school', 'ensembles']);
        $ensembles = $program !== null ? $program->ensembles : collect();

        $digitalProgram = $this->digitalProgramId
            ? $this->fetchDigitalProgram()
            : null;

        return view('livewire.digital-programs.guided-wizard', [
            'userPrograms' => $userPrograms,
            'resolvedProgram' => $program,
            'ensembles' => $ensembles,
            'themes' => self::$themes,
            'voiceParts' => DigitalProgramRoster::VOICE_PARTS,
            'digitalProgram' => $digitalProgram,
        ])->layout('components.layouts.app', ['title' => __('Create Digital Program — Guided')]);
    }
}
