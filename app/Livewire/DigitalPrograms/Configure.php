<?php

declare(strict_types=1);

namespace App\Livewire\DigitalPrograms;

use App\Livewire\Concerns\HasDigitalProgramState;
use App\Models\DigitalProgram;
use App\Models\DigitalProgramRoster;
use App\Models\DigitalProgramSongSetting;
use App\Models\Ensemble;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class Configure extends Component
{
    use HasDigitalProgramState;

    public int $digitalProgramId;

    public ?int $resolvedProgramId = null;

    public bool $editingSongs = false;

    // ─── Lifecycle ───────────────────────────────────────────────────────────

    public function mount(DigitalProgram $digitalProgram): void
    {
        abort_unless($digitalProgram->user_id === auth()->user()->digitalProgramsOwnerId(), 403);

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

        // Song settings (for hasLyrics flag and legacy fallback)
        $this->loadExistingSongSettings($digitalProgram);

        // Wizard ensemble/song structure
        if ($this->resolvedProgramId !== null) {
            $this->initializeWizardEnsembles($this->resolvedProgramId);

            // Sync showLyrics from existing song settings into ensembleSongs
            $lyricsMap = $digitalProgram->songSettings->keyBy('song_title_id');

            foreach ($this->ensembleSongs as $ensIdx => $songs) {
                foreach ($songs as $songIdx => $song) {
                    $stId = $song['songTitleId'] ?? null;

                    if ($stId && $lyricsMap->has($stId)) {
                        $this->ensembleSongs[$ensIdx][$songIdx]['showLyrics'] =
                            (bool) $lyricsMap[$stId]->show_lyrics;
                        $this->ensembleSongs[$ensIdx][$songIdx]['programNotes'] =
                            $lyricsMap[$stId]->program_notes ?? '';
                    }
                }
            }
        }

        $this->loadExistingRosterAndHonors($digitalProgram);
    }

    // ─── Actions ─────────────────────────────────────────────────────────────

    public function toggleEditSongs(): void
    {
        $this->editingSongs = ! $this->editingSongs;
    }

    public function save(bool $publish = false): void
    {
        abort_if($publish && auth()->user()->isAssistant(), 403);

        $this->validate($this->updateRules());

        DB::transaction(function () use ($publish): void {
            $dp = DigitalProgram::query()
                ->where('id', $this->digitalProgramId)
                ->where('user_id', auth()->user()->digitalProgramsOwnerId())
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
                'is_published' => auth()->user()->isAssistant() ? $dp->is_published : $publish,
            ]);

            // Persist song structure and lyrics from ensembleSongs
            if ($this->resolvedProgramId !== null) {
                $this->saveWizardEnsemblesAndSongs($this->resolvedProgramId, $dp->id);
            } else {
                // Fallback: no linked program, just upsert lyrics toggles
                foreach ($this->songSettings as $setting) {
                    DigitalProgramSongSetting::updateOrCreate(
                        ['digital_program_id' => $dp->id, 'song_title_id' => $setting['songTitleId']],
                        ['show_lyrics' => $setting['showLyrics']]
                    );
                }
            }

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
        $dp = DigitalProgram::with(['program.school'])->find($this->digitalProgramId);

        $program = $dp?->program;
        $ensembles = $program !== null
            ? $program->ensembles()->orderByPivot('ensemble_sort_order')->get()
            : collect();

        // Songs grouped by ensemble_id for the read-only view
        $songsByEnsemble = [];

        if ($program !== null) {
            foreach ($program->songTitles()->with('composer', 'arranger')->get() as $song) {
                $key = (string) ($song->pivot->ensemble_id ?? 'general');
                $songsByEnsemble[$key][] = $song;
            }
        }

        // School ensembles available for the edit mode picker
        $school = $program?->school;
        $schoolEnsembles = $school !== null
            ? Ensemble::where('school_id', $school->id)->orderBy('ensemble_name')->get()
            : collect();

        return view('livewire.digital-programs.configure', [
            'dp' => $dp,
            'program' => $program,
            'ensembles' => $ensembles,
            'songsByEnsemble' => $songsByEnsemble,
            'schoolEnsembles' => $schoolEnsembles,
            'themes' => self::$themes,
            'voiceParts' => DigitalProgramRoster::VOICE_PARTS,
        ])->layout('components.layouts.app', ['title' => __('Edit Digital Program')]);
    }
}
