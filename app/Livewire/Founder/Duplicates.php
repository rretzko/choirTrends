<?php

declare(strict_types=1);

namespace App\Livewire\Founder;

use App\Models\Artist;
use App\Models\Ensemble;
use App\Models\School;
use App\Models\SongTitle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class Duplicates extends Component
{
    public string $activeTab = 'schools';

    /** @var array<string, int> */
    public array $selectedKeepers = [];

    public string $successMessage = '';

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->selectedKeepers = [];
    }

    /**
     * Find schools that share the same name (case-insensitive) but different postal codes.
     */
    public function findSchoolDuplicates(): Collection
    {
        $duplicateNames = School::query()
            ->select(DB::raw('LOWER(school_name) as lower_name'))
            ->groupBy('lower_name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('lower_name');

        if ($duplicateNames->isEmpty()) {
            return collect();
        }

        return School::query()
            ->whereIn(DB::raw('LOWER(school_name)'), $duplicateNames)
            ->orderBy('school_name')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (School $school) => mb_strtolower($school->school_name));
    }

    /**
     * Find artists that share the same name (case-insensitive).
     */
    public function findArtistDuplicates(): Collection
    {
        $duplicateNames = Artist::query()
            ->select(DB::raw('LOWER(artist_name) as lower_name'))
            ->groupBy('lower_name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('lower_name');

        if ($duplicateNames->isEmpty()) {
            return collect();
        }

        return Artist::query()
            ->whereIn(DB::raw('LOWER(artist_name)'), $duplicateNames)
            ->orderBy('artist_name')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Artist $artist) => mb_strtolower($artist->artist_name));
    }

    /**
     * Find song titles that share the same title (case-insensitive) with different composer/arranger combos.
     */
    public function findSongTitleDuplicates(): Collection
    {
        $duplicateNames = SongTitle::query()
            ->select(DB::raw('LOWER(song_title) as lower_title'))
            ->groupBy('lower_title')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('lower_title');

        if ($duplicateNames->isEmpty()) {
            return collect();
        }

        return SongTitle::query()
            ->with(['composer', 'arranger'])
            ->whereIn(DB::raw('LOWER(song_title)'), $duplicateNames)
            ->orderBy('song_title')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (SongTitle $songTitle) => mb_strtolower($songTitle->song_title));
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

        $this->selectedKeepers = [];

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

        $this->selectedKeepers = [];

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

        $this->selectedKeepers = [];

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

    public function render(): View
    {
        $duplicates = match ($this->activeTab) {
            'artists' => $this->findArtistDuplicates(),
            'song-titles' => $this->findSongTitleDuplicates(),
            default => $this->findSchoolDuplicates(),
        };

        return view('livewire.founder.duplicates', [
            'duplicateGroups' => $duplicates,
        ])->layout('components.layouts.app', ['title' => __('Duplicates')]);
    }
}
