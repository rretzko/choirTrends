<?php

declare(strict_types=1);

namespace App\Livewire\Founder;

use App\Models\Artist;
use App\Models\Ensemble;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\Response;

class Duplicates extends Component
{
    use WithPagination;

    public string $activeTab = 'schools';

    public ?int $keeperId = null;

    public ?int $duplicateId = null;

    public string $successMessage = '';

    public ?int $editingArtistId = null;

    public string $editArtistName = '';

    public string $editArtistFirstName = '';

    public string $editArtistLastName = '';

    public ?int $editingSongId = null;

    public string $editSongName = '';

    public ?int $editSongComposerId = null;

    public ?int $editSongArrangerId = null;

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->keeperId = null;
        $this->duplicateId = null;
        $this->successMessage = '';
        $this->resetPage();
    }

    public function updatedKeeperId(): void
    {
        if ($this->keeperId && $this->keeperId === $this->duplicateId) {
            $this->duplicateId = null;
        }
    }

    public function manualMerge(): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        if (! $this->keeperId || ! $this->duplicateId || $this->keeperId === $this->duplicateId) {
            return;
        }

        match ($this->activeTab) {
            'artists' => $this->mergeArtists($this->keeperId, (string) $this->duplicateId),
            'song-titles' => $this->mergeSongTitles($this->keeperId, (string) $this->duplicateId),
            'users' => $this->mergeUsers($this->keeperId, (string) $this->duplicateId),
            default => $this->mergeSchools($this->keeperId, (string) $this->duplicateId),
        };

        $this->keeperId = null;
        $this->duplicateId = null;
    }

    public function mergeSchools(int $keeperId, string $duplicateIds): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        /** @var list<int> $ids */
        $ids = array_map('intval', explode(',', $duplicateIds));

        $keeper = School::findOrFail($keeperId);

        DB::transaction(function () use ($keeper, $ids): void {
            foreach ($ids as $duplicateId) {
                $duplicate = School::findOrFail($duplicateId);

                // Reassign programs
                $duplicate->programs()->update(['school_id' => $keeper->id]);

                // Reassign ensembles (skip if ensemble name already exists under keeper)
                $existingEnsembleNames = $keeper->ensembles()->pluck('ensemble_name')->map(fn ($n) => mb_strtolower($n));
                foreach ($duplicate->ensembles as $ensemble) {
                    if ($existingEnsembleNames->contains(mb_strtolower($ensemble->ensemble_name))) {
                        // Reassign program_song_title ensemble references before deleting
                        /** @var Ensemble|null $matchingEnsemble */
                        $matchingEnsemble = $keeper->ensembles()
                            ->whereRaw('LOWER(ensemble_name) = ?', [mb_strtolower($ensemble->ensemble_name)])
                            ->first();
                        DB::table('program_song_title')
                            ->where('ensemble_id', $ensemble->id)
                            ->update(['ensemble_id' => $matchingEnsemble?->id]);
                        $ensemble->delete();
                    } else {
                        $ensemble->update(['school_id' => $keeper->id]);
                    }
                }

                // Reassign school_user pivot entries (skip if association already exists)
                $existingUserIds = $keeper->users()->pluck('users.id');
                foreach ($duplicate->users as $user) {
                    if (! $existingUserIds->contains($user->id)) {
                        $keeper->users()->attach($user->id);
                    }
                }
                $duplicate->users()->detach();

                $duplicate->delete();
            }
        });

        $this->successMessage = __('Schools merged successfully.');
    }

    public function mergeArtists(int $keeperId, string $duplicateIds): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        /** @var list<int> $ids */
        $ids = array_map('intval', explode(',', $duplicateIds));

        $keeper = Artist::findOrFail($keeperId);

        DB::transaction(function () use ($keeper, $ids): void {
            foreach ($ids as $duplicateId) {
                $duplicate = Artist::findOrFail($duplicateId);

                // Reassign composer references
                $composerSongs = SongTitle::where('composer_id', $duplicate->id)->get();
                foreach ($composerSongs as $song) {
                    $existing = SongTitle::where('song_title', $song->song_title)
                        ->where('composer_id', $keeper->id)
                        ->where('arranger_id', $song->arranger_id)
                        ->first();

                    if ($existing) {
                        // Merge song title pivot entries
                        $this->mergeSongTitlePivot($existing->id, $song->id);
                        $song->delete();
                    } else {
                        $song->update(['composer_id' => $keeper->id]);
                    }
                }

                // Reassign arranger references
                $arrangerSongs = SongTitle::where('arranger_id', $duplicate->id)->get();
                foreach ($arrangerSongs as $song) {
                    $existing = SongTitle::where('song_title', $song->song_title)
                        ->where('composer_id', $song->composer_id)
                        ->where('arranger_id', $keeper->id)
                        ->first();

                    if ($existing) {
                        $this->mergeSongTitlePivot($existing->id, $song->id);
                        $song->delete();
                    } else {
                        $song->update(['arranger_id' => $keeper->id]);
                    }
                }

                $duplicate->delete();
            }
        });

        $this->successMessage = __('Artists merged successfully.');
    }

    public function mergeSongTitles(int $keeperId, string $duplicateIds): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        /** @var list<int> $ids */
        $ids = array_map('intval', explode(',', $duplicateIds));

        $keeper = SongTitle::findOrFail($keeperId);

        DB::transaction(function () use ($keeper, $ids): void {
            foreach ($ids as $duplicateId) {
                $duplicate = SongTitle::findOrFail($duplicateId);

                $this->mergeSongTitlePivot($keeper->id, $duplicate->id);

                $duplicate->delete();
            }
        });

        $this->successMessage = __('Song titles merged successfully.');
    }

    /**
     * Reassign program_song_title pivot entries from one song_title to another,
     * handling primary key conflicts.
     */
    private function mergeSongTitlePivot(int $keeperId, int $duplicateId): void
    {
        $existingProgramIds = DB::table('program_song_title')
            ->where('song_title_id', $keeperId)
            ->pluck('program_id');

        $duplicatePivots = DB::table('program_song_title')
            ->where('song_title_id', $duplicateId)
            ->get();

        foreach ($duplicatePivots as $pivot) {
            if ($existingProgramIds->contains($pivot->program_id)) {
                // Conflict: delete the duplicate pivot row
                DB::table('program_song_title')
                    ->where('program_id', $pivot->program_id)
                    ->where('song_title_id', $duplicateId)
                    ->delete();
            } else {
                // Safe to reassign
                DB::table('program_song_title')
                    ->where('program_id', $pivot->program_id)
                    ->where('song_title_id', $duplicateId)
                    ->update(['song_title_id' => $keeperId]);
            }
        }
    }

    public function mergeUsers(int $keeperId, string $duplicateIds): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        /** @var list<int> $ids */
        $ids = array_map('intval', explode(',', $duplicateIds));

        $keeper = User::findOrFail($keeperId);

        DB::transaction(function () use ($keeper, $ids): void {
            foreach ($ids as $duplicateId) {
                $duplicate = User::findOrFail($duplicateId);

                // Reassign programs (skip those that would violate unique constraint)
                foreach ($duplicate->programs as $program) {
                    $conflict = $keeper->programs()
                        ->where('event_name', $program->event_name)
                        ->where('event_date', $program->event_date)
                        ->exists();

                    if (! $conflict) {
                        $program->update(['user_id' => $keeper->id]);
                    }
                    // If conflict, leave the duplicate program to be deleted with the user (cascade)
                    // The user said they will handle duplicate programs manually
                }

                // Reassign school_user pivot entries (skip existing)
                $existingSchoolIds = $keeper->schools()->pluck('schools.id');
                foreach ($duplicate->schools as $school) {
                    if (! $existingSchoolIds->contains($school->id)) {
                        $keeper->schools()->attach($school->id);
                    }
                }
                $duplicate->schools()->detach();

                // Reassign user_logins
                DB::table('user_logins')
                    ->where('user_id', $duplicate->id)
                    ->update(['user_id' => $keeper->id]);

                // Reassign feedbacks
                DB::table('feedbacks')
                    ->where('user_id', $duplicate->id)
                    ->update(['user_id' => $keeper->id]);

                // Reassign feedback_comments
                DB::table('feedback_comments')
                    ->where('user_id', $duplicate->id)
                    ->update(['user_id' => $keeper->id]);

                // Reassign quick_tip_user pivot entries (skip existing)
                $existingQuickTipIds = $keeper->quickTips()->pluck('quick_tips.id');
                DB::table('quick_tip_user')
                    ->where('user_id', $duplicate->id)
                    ->whereNotIn('quick_tip_id', $existingQuickTipIds)
                    ->update(['user_id' => $keeper->id]);
                DB::table('quick_tip_user')
                    ->where('user_id', $duplicate->id)
                    ->delete();

                // Reassign survey_responses
                DB::table('survey_responses')
                    ->where('user_id', $duplicate->id)
                    ->update(['user_id' => $keeper->id]);

                // Delete duplicate's privacy record (keeper keeps theirs)
                DB::table('user_privacies')
                    ->where('user_id', $duplicate->id)
                    ->delete();

                // Clear sessions for duplicate
                DB::table('sessions')
                    ->where('user_id', $duplicate->id)
                    ->update(['user_id' => null]);

                $duplicate->delete();
            }
        });

        $this->successMessage = __('Users merged successfully.');
    }

    public function editArtist(int $id): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        $artist = Artist::findOrFail($id);

        $this->editingArtistId = $artist->id;
        $this->editArtistName = $artist->artist_name;
        $this->editArtistFirstName = $artist->artist_first_name ?? '';
        $this->editArtistLastName = $artist->artist_last_name ?? '';

        Flux::modal('edit-artist')->show();
    }

    public function updateArtist(): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        $this->validate([
            'editArtistName' => 'required|string|max:255',
            'editArtistFirstName' => 'nullable|string|max:255',
            'editArtistLastName' => 'nullable|string|max:255',
        ]);

        $artist = Artist::findOrFail($this->editingArtistId);

        $artist->update([
            'artist_name' => $this->editArtistName,
            'artist_first_name' => $this->editArtistFirstName,
            'artist_last_name' => $this->editArtistLastName,
        ]);

        $this->editingArtistId = null;
        $this->editArtistName = '';
        $this->editArtistFirstName = '';
        $this->editArtistLastName = '';

        Flux::modal('edit-artist')->close();

        $this->successMessage = __('Artist updated successfully.');
    }

    public function editSong(int $id): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        $song = SongTitle::findOrFail($id);

        $this->editingSongId = $song->id;
        $this->editSongName = $song->song_title;
        $this->editSongComposerId = $song->composer_id;
        $this->editSongArrangerId = $song->arranger_id;

        Flux::modal('edit-song')->show();
    }

    public function updateSong(): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        $this->validate([
            'editSongName' => 'required|string|max:255',
            'editSongComposerId' => 'nullable|exists:artists,id',
            'editSongArrangerId' => 'nullable|exists:artists,id',
        ]);

        $song = SongTitle::findOrFail($this->editingSongId);

        $song->update([
            'song_title' => $this->editSongName,
            'composer_id' => $this->editSongComposerId,
            'arranger_id' => $this->editSongArrangerId,
        ]);

        $this->editingSongId = null;
        $this->editSongName = '';
        $this->editSongComposerId = null;
        $this->editSongArrangerId = null;

        Flux::modal('edit-song')->close();

        $this->successMessage = __('Song updated successfully.');
    }

    public function removeSong(int $id): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        $song = SongTitle::findOrFail($id);

        // Detach from programs before deleting
        DB::table('program_song_title')
            ->where('song_title_id', $song->id)
            ->delete();

        $song->delete();

        $this->successMessage = __('Song removed successfully.');
    }

    public function render(): View
    {
        $records = match ($this->activeTab) {
            'artists' => Artist::query()->orderBy('artist_last_name')->orderBy('artist_first_name')->get(),
            'song-titles' => SongTitle::query()->with(['composer', 'arranger'])->orderBy('song_title')->get(),
            'users' => User::query()->withCount(['programs', 'schools'])->orderBy('alpha_name')->get(),
            default => School::query()->orderBy('school_name')->get(),
        };

        $songTitlePages = null;
        $artists = collect();

        if ($this->activeTab === 'song-titles') {
            $songTitlePages = SongTitle::query()
                ->with(['composer', 'arranger'])
                ->orderBy('song_title')
                ->paginate(20);

            $artists = Artist::query()
                ->orderBy('artist_last_name')
                ->orderBy('artist_first_name')
                ->get();
        }

        return view('livewire.founder.duplicates', [
            'records' => $records,
            'songTitlePages' => $songTitlePages,
            'artists' => $artists,
        ])->layout('components.layouts.app', ['title' => __('Duplicates')]);
    }
}
