<?php

declare(strict_types=1);

namespace App\Livewire\DigitalPrograms;

use App\Livewire\Concerns\HasDigitalProgramState;
use App\Models\DigitalProgram;
use App\Models\DigitalProgramRoster;
use App\Models\DigitalProgramSongSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class Configure extends Component
{
    use HasDigitalProgramState;

    public int $digitalProgramId;

    public ?int $resolvedProgramId = null;

    // ─── Lifecycle ───────────────────────────────────────────────────────────

    public function mount(DigitalProgram $digitalProgram): void
    {
        abort_unless($digitalProgram->user_id === auth()->id(), 403);

        $digitalProgram->load(['songSettings', 'honors', 'rosters.honors']);

        $this->digitalProgramId = $digitalProgram->id;
        $this->resolvedProgramId = $digitalProgram->program_id;

        // Scalar fields
        $this->theme = $digitalProgram->theme;
        $this->printOrientation = $digitalProgram->print_orientation;
        $this->welcomeMessage = $digitalProgram->welcome_message ?? '';
        $this->acknowledgments = $digitalProgram->acknowledgments ?? '';
        $this->sponsorText = $digitalProgram->sponsor_text ?? '';
        $this->intermissionAfterEnsemble = $digitalProgram->intermission_after_ensemble;
        $this->lyricsCopyrightAcknowledged = (bool) $digitalProgram->lyrics_copyright_acknowledged;
        $this->studentNamesAcknowledged = (bool) $digitalProgram->student_names_acknowledged;

        // Array state from DB
        $this->loadExistingSongSettings($digitalProgram);
        $this->loadExistingRosterAndHonors($digitalProgram);
    }

    // ─── Actions ─────────────────────────────────────────────────────────────

    public function save(bool $publish = false): void
    {
        $this->validate($this->updateRules());

        DB::transaction(function () use ($publish): void {
            $dp = DigitalProgram::query()
                ->where('id', $this->digitalProgramId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $dp->update([
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

            // Upsert song settings
            foreach ($this->songSettings as $setting) {
                DigitalProgramSongSetting::updateOrCreate(
                    [
                        'digital_program_id' => $dp->id,
                        'song_title_id' => $setting['songTitleId'],
                    ],
                    ['show_lyrics' => $setting['showLyrics']]
                );
            }

            // Replace honors and rosters wholesale
            $this->saveHonorsAndRosters($dp->id);
        });

        session()->flash('success', $publish ? __('Digital program published!') : __('Changes saved.'));
        $this->redirectRoute('digital-programs.index');
    }

    // ─── Validation ──────────────────────────────────────────────────────────

    private function updateRules(): array
    {
        $rules = $this->themeRules();

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
        $dp = DigitalProgram::with(['program.school', 'program.ensembles'])->find($this->digitalProgramId);

        $program = $dp?->program;
        $ensembles = $program?->ensembles ?? collect();

        return view('livewire.digital-programs.configure', [
            'dp' => $dp,
            'program' => $program,
            'ensembles' => $ensembles,
            'themes' => self::$themes,
            'voiceParts' => DigitalProgramRoster::VOICE_PARTS,
        ])->layout('components.layouts.app', ['title' => __('Edit Digital Program')]);
    }
}
