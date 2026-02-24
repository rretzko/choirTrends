<?php

declare(strict_types=1);

namespace App\Livewire\Programs;

use App\Models\Artist;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Services\ArtistNameParser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class Edit extends Component
{
    public Program $program;

    public string $eventName = '';

    public string $eventDate = '';

    public string $directorName = '';

    public string $schoolName = '';

    public bool $schoolEditable = true;

    /** @var array<int, array{id: int|null, name: string, editable: bool, songs: list<array{songTitleId: int|null, title: string, titleEditable: bool, composer: string, composerEditable: bool, arranger: string, arrangerEditable: bool, composerId: int|null, arrangerId: int|null, sortOrder: int}>}> */
    public array $ensembles = [];

    public function mount(Program $program): void
    {
        abort_unless($program->user_id === auth()->id(), 403);

        $this->program = $program->load(['school', 'songTitles.composer', 'songTitles.arranger']);

        $this->eventName = $program->event_name;
        $this->eventDate = Carbon::parse($program->event_date)->format('Y-m-d');
        $this->directorName = $program->director_name ?? '';
        $this->schoolName = $program->school->school_name ?? '';

        $this->computeEditability();
    }

    public function addEnsemble(): void
    {
        $this->ensembles[] = [
            'id' => null,
            'name' => '',
            'editable' => true,
            'songs' => [
                [
                    'songTitleId' => null,
                    'title' => '',
                    'titleEditable' => true,
                    'composer' => '',
                    'composerEditable' => true,
                    'arranger' => '',
                    'arrangerEditable' => true,
                    'composerId' => null,
                    'arrangerId' => null,
                    'sortOrder' => 1,
                ],
            ],
        ];
    }

    public function removeEnsemble(int $index): void
    {
        unset($this->ensembles[$index]);
        $this->ensembles = array_values($this->ensembles);
    }

    public function addSong(int $ensembleIndex): void
    {
        $maxSortOrder = max(array_column($this->ensembles[$ensembleIndex]['songs'], 'sortOrder') ?: [0]);

        $this->ensembles[$ensembleIndex]['songs'][] = [
            'songTitleId' => null,
            'title' => '',
            'titleEditable' => true,
            'composer' => '',
            'composerEditable' => true,
            'arranger' => '',
            'arrangerEditable' => true,
            'composerId' => null,
            'arrangerId' => null,
            'sortOrder' => $maxSortOrder + 1,
        ];
    }

    public function removeSong(int $ensembleIndex, int $songIndex): void
    {
        unset($this->ensembles[$ensembleIndex]['songs'][$songIndex]);
        $this->ensembles[$ensembleIndex]['songs'] = array_values($this->ensembles[$ensembleIndex]['songs']);
    }

    public function save(): void
    {
        $this->validate([
            'eventName' => ['required', 'string', 'max:255'],
            'eventDate' => ['required', 'date'],
            'directorName' => ['required', 'string', 'max:255'],
            'schoolName' => ['required', 'string', 'max:255'],
            'ensembles.*.name' => ['nullable', 'string', 'max:255'],
            'ensembles.*.songs.*.title' => ['required', 'string', 'max:255'],
            'ensembles.*.songs.*.composer' => ['nullable', 'string', 'max:255'],
            'ensembles.*.songs.*.arranger' => ['nullable', 'string', 'max:255'],
            'ensembles.*.songs.*.sortOrder' => ['required', 'integer', 'min:1'],
        ]);

        $duplicate = Program::query()
            ->where('user_id', $this->program->user_id)
            ->where('event_name', $this->eventName)
            ->whereDate('event_date', $this->eventDate)
            ->where('id', '!=', $this->program->id)
            ->exists();

        if ($duplicate) {
            $this->addError('eventName', 'A program for this event already exists. Please use a different event name or date.');

            return;
        }

        DB::transaction(function (): void {
            $artistNameParser = new ArtistNameParser;

            // Update program-level fields (always editable)
            $this->program->event_name = $this->eventName;
            $this->program->event_date = $this->eventDate;
            $this->program->director_name = $this->directorName;

            // Update school if editable and changed
            if ($this->schoolEditable && $this->schoolName !== $this->program->school->school_name) {
                $school = School::firstOrCreate(['school_name' => $this->schoolName]);
                $this->program->school_id = $school->id;
                auth()->user()->schools()->syncWithoutDetaching([$school->id]);
            }

            $this->program->save();

            // Rebuild song title attachments
            $songTitleAttachments = [];

            foreach ($this->ensembles as $ensembleData) {
                $ensemble = null;

                if (! empty($ensembleData['name'])) {
                    if ($ensembleData['id'] && $ensembleData['editable']) {
                        $ensemble = Ensemble::find($ensembleData['id']);
                        if ($ensemble && $ensemble->ensemble_name !== $ensembleData['name']) {
                            // If an ensemble with the new name already exists for this school, reuse it
                            $existing = Ensemble::where('school_id', $this->program->school_id)
                                ->where('ensemble_name', $ensembleData['name'])
                                ->first();
                            $ensemble = $existing ?? tap($ensemble, fn (Ensemble $e) => $e->update(['ensemble_name' => $ensembleData['name']]));
                        }
                    } elseif ($ensembleData['id'] && ! $ensembleData['editable']) {
                        $ensemble = Ensemble::find($ensembleData['id']);
                    } else {
                        $ensemble = Ensemble::firstOrCreate([
                            'school_id' => $this->program->school_id,
                            'ensemble_name' => $ensembleData['name'],
                        ]);
                    }
                }

                foreach ($ensembleData['songs'] as $songData) {
                    if (empty($songData['title'])) {
                        continue;
                    }

                    $composerId = null;
                    if (! empty($songData['composer'])) {
                        if ($songData['composerEditable'] || ! $songData['composerId']) {
                            $composerParsed = $artistNameParser->parse($songData['composer']);
                            $composer = Artist::firstOrCreate(
                                ['artist_name' => $composerParsed['artist_name']],
                                $composerParsed
                            );
                            $composerId = $composer->id;
                        } else {
                            $composerId = $songData['composerId'];
                        }
                    }

                    $arrangerId = null;
                    if (! empty($songData['arranger'])) {
                        if ($songData['arrangerEditable'] || ! $songData['arrangerId']) {
                            $arrangerParsed = $artistNameParser->parse($songData['arranger']);
                            $arranger = Artist::firstOrCreate(
                                ['artist_name' => $arrangerParsed['artist_name']],
                                $arrangerParsed
                            );
                            $arrangerId = $arranger->id;
                        } else {
                            $arrangerId = $songData['arrangerId'];
                        }
                    }

                    $songTitle = SongTitle::firstOrCreate([
                        'song_title' => $songData['title'],
                        'composer_id' => $composerId,
                        'arranger_id' => $arrangerId,
                    ]);

                    $songTitleAttachments[$songTitle->id] = [
                        'ensemble_id' => $ensemble?->id,
                        'sort_order' => (int) $songData['sortOrder'],
                    ];
                }
            }

            $this->program->songTitles()->sync($songTitleAttachments);
        });

        session()->flash('success', 'Program updated successfully!');
        $this->redirectRoute('programs.index');
    }

    public function render(): View
    {
        return view('livewire.programs.edit')
            ->layout('components.layouts.app', ['title' => __('Edit Program')]);
    }

    private function computeEditability(): void
    {
        $userId = $this->program->user_id;

        // School: editable when no other user's programs reference this school
        $this->schoolEditable = ! Program::query()
            ->where('school_id', $this->program->school_id)
            ->where('user_id', '!=', $userId)
            ->exists();

        // Group songs by ensemble
        $songsByEnsemble = $this->program->songTitles->groupBy('pivot.ensemble_id');

        $this->ensembles = [];

        foreach ($songsByEnsemble as $ensembleId => $songs) {
            $ensemble = $ensembleId ? Ensemble::find($ensembleId) : null;

            // Ensemble: editable when no other user's programs link songs to this ensemble
            $ensembleEditable = true;
            if ($ensemble) {
                $ensembleEditable = ! DB::table('program_song_title')
                    ->join('programs', 'programs.id', '=', 'program_song_title.program_id')
                    ->where('program_song_title.ensemble_id', $ensemble->id)
                    ->where('programs.user_id', '!=', $userId)
                    ->exists();
            }

            $songsArray = [];

            /** @var SongTitle $songTitle */
            foreach ($songs as $songTitle) {
                // Song title: editable when no other user's programs reference this song_title_id
                $titleEditable = ! DB::table('program_song_title')
                    ->join('programs', 'programs.id', '=', 'program_song_title.program_id')
                    ->where('program_song_title.song_title_id', $songTitle->id)
                    ->where('programs.user_id', '!=', $userId)
                    ->exists();

                // Composer: editable when no other user's programs reference this artist
                $composerEditable = true;
                if ($songTitle->composer_id) {
                    $composerEditable = ! SongTitle::query()
                        ->where('composer_id', $songTitle->composer_id)
                        ->whereHas('programs', fn ($q) => $q->where('user_id', '!=', $userId))
                        ->exists();
                }

                // Arranger: editable when no other user's programs reference this artist
                $arrangerEditable = true;
                if ($songTitle->arranger_id) {
                    $arrangerEditable = ! SongTitle::query()
                        ->where('arranger_id', $songTitle->arranger_id)
                        ->whereHas('programs', fn ($q) => $q->where('user_id', '!=', $userId))
                        ->exists();
                }

                $songsArray[] = [
                    'songTitleId' => $songTitle->id,
                    'title' => $songTitle->song_title,
                    'titleEditable' => $titleEditable,
                    'composer' => $songTitle->composer->artist_name ?? '',
                    'composerEditable' => $composerEditable,
                    'arranger' => $songTitle->arranger->artist_name ?? '',
                    'arrangerEditable' => $arrangerEditable,
                    'composerId' => $songTitle->composer_id,
                    'arrangerId' => $songTitle->arranger_id,
                    'sortOrder' => $songTitle->pivot->sort_order ?? 0,
                ];
            }

            $this->ensembles[] = [
                'id' => $ensemble?->id,
                'name' => $ensemble->ensemble_name ?? '',
                'editable' => $ensembleEditable,
                'songs' => $songsArray,
            ];
        }
    }
}
