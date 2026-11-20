<?php

declare(strict_types=1);

namespace App\Livewire\Programs;

use App\Enums\VideoVisibility;
use App\Models\Program;
use App\Models\SongTitle;
use App\Models\UserSongFile;
use App\Models\UserSongLyrics;
use App\Services\YouTubeUrl;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class SongMediaManager extends Component
{
    use WithFileUploads;

    public Program $program;

    public ?int $songTitleId = null;

    public string $songTitleLabel = '';

    public ?string $videoPath = null;

    public string $videoVisibility = 'Private';

    /** @var TemporaryUploadedFile|null */
    public $audioVideoUpload;

    public ?string $youtubeUrl = null;

    public string $youtubeUrlInput = '';

    public string $lyrics = '';

    public bool $hasLyrics = false;

    /** @var TemporaryUploadedFile|null */
    public $sheetMusicUpload;

    public function mount(Program $program): void
    {
        $this->program = $program;
    }

    #[On('open-song-media-manager')]
    public function open(int $songTitleId): void
    {
        abort_unless($this->program->user_id === auth()->id(), 403);

        $songTitle = SongTitle::findOrFail($songTitleId);

        /** @var object{video_path: string|null, video_visibility: string|null, youtube_url: string|null}|null $pivot */
        $pivot = $this->program->songTitles()
            ->wherePivot('song_title_id', $songTitleId)
            ->first()
            ?->pivot;

        abort_unless($pivot, 404);

        $this->songTitleId = $songTitleId;
        $this->songTitleLabel = $songTitle->song_title;
        $this->videoPath = $pivot->video_path;
        $this->videoVisibility = $pivot->video_visibility ?? 'Private';
        $this->audioVideoUpload = null;
        $this->youtubeUrl = $pivot->youtube_url;
        $this->youtubeUrlInput = $pivot->youtube_url ?? '';

        $existingLyrics = UserSongLyrics::query()
            ->where('user_id', auth()->id())
            ->where('song_title_id', $songTitleId)
            ->first();

        $this->lyrics = $existingLyrics !== null ? $existingLyrics->content : '';
        $this->hasLyrics = $existingLyrics !== null;

        $this->sheetMusicUpload = null;

        $this->resetErrorBag();

        $this->modal('song-media-manager')->show();
    }

    /**
     * @return Collection<int, UserSongFile>
     */
    public function getSheetMusicFilesProperty(): Collection
    {
        if ($this->songTitleId === null) {
            /** @var Collection<int, UserSongFile> $empty */
            $empty = new Collection;

            return $empty;
        }

        return UserSongFile::query()
            ->where('user_id', auth()->id())
            ->where('song_title_id', $this->songTitleId)
            ->where('type', 'sheet_music')
            ->orderBy('created_at')
            ->get();
    }

    public function updatedAudioVideoUpload(): void
    {
        if ($this->songTitleId === null) {
            return;
        }

        $this->validate([
            'audioVideoUpload' => ['file', 'mimes:mp4,mov,avi,wmv,mp3,wav,m4a,ogg,flac,aac,wma', 'max:512000'],
        ]);

        $path = $this->audioVideoUpload->store('mp4s/songs');

        $visibility = $this->youtubeUrl !== null ? $this->videoVisibility : VideoVisibility::Private->value;

        $this->program->songTitles()->updateExistingPivot($this->songTitleId, [
            'video_path' => $path,
            'video_visibility' => $visibility,
            'video_uploaded_at' => now(),
        ]);

        $this->videoPath = $path;
        $this->videoVisibility = $visibility;
        $this->audioVideoUpload = null;

        $this->dispatch('song-media-updated', songTitleId: $this->songTitleId);
    }

    public function removeAudioVideo(): void
    {
        if ($this->songTitleId === null || $this->videoPath === null) {
            return;
        }

        Storage::delete($this->videoPath);

        $this->program->songTitles()->updateExistingPivot($this->songTitleId, [
            'video_path' => null,
            'video_visibility' => $this->youtubeUrl !== null ? $this->videoVisibility : null,
            'video_uploaded_at' => null,
        ]);

        $this->videoPath = null;

        if ($this->youtubeUrl === null) {
            $this->videoVisibility = VideoVisibility::Private->value;
        }

        $this->dispatch('song-media-updated', songTitleId: $this->songTitleId);
    }

    public function saveYouTubeUrl(): void
    {
        if ($this->songTitleId === null) {
            return;
        }

        $this->validate(
            ['youtubeUrlInput' => ['required', 'string', 'max:2048', YouTubeUrl::rule()]],
            ['youtubeUrlInput.required' => __('Paste a YouTube link first.')],
        );

        $normalized = YouTubeUrl::normalize($this->youtubeUrlInput);
        $visibility = $this->videoPath !== null || $this->youtubeUrl !== null
            ? $this->videoVisibility
            : VideoVisibility::Private->value;

        $this->program->songTitles()->updateExistingPivot($this->songTitleId, [
            'youtube_url' => $normalized,
            'video_visibility' => $visibility,
        ]);

        $this->youtubeUrl = $normalized;
        $this->youtubeUrlInput = (string) $normalized;
        $this->videoVisibility = $visibility;

        $this->dispatch('song-media-updated', songTitleId: $this->songTitleId);
    }

    public function removeYouTubeUrl(): void
    {
        if ($this->songTitleId === null || $this->youtubeUrl === null) {
            return;
        }

        $this->program->songTitles()->updateExistingPivot($this->songTitleId, [
            'youtube_url' => null,
            'video_visibility' => $this->videoPath !== null ? $this->videoVisibility : null,
        ]);

        $this->youtubeUrl = null;
        $this->youtubeUrlInput = '';

        if ($this->videoPath === null) {
            $this->videoVisibility = VideoVisibility::Private->value;
        }

        $this->resetErrorBag('youtubeUrlInput');

        $this->dispatch('song-media-updated', songTitleId: $this->songTitleId);
    }

    public function toggleAudioVideoVisibility(): void
    {
        if ($this->songTitleId === null || ($this->videoPath === null && $this->youtubeUrl === null)) {
            return;
        }

        $new = $this->videoVisibility === 'Private'
            ? VideoVisibility::Public->value
            : VideoVisibility::Private->value;

        $this->program->songTitles()->updateExistingPivot($this->songTitleId, [
            'video_visibility' => $new,
        ]);

        $this->videoVisibility = $new;

        $this->dispatch('song-media-updated', songTitleId: $this->songTitleId);
    }

    public function saveLyrics(): void
    {
        if ($this->songTitleId === null) {
            return;
        }

        $this->validate([
            'lyrics' => ['required', 'string', 'max:100000'],
        ]);

        UserSongLyrics::query()->updateOrCreate(
            [
                'user_id' => auth()->id(),
                'song_title_id' => $this->songTitleId,
            ],
            [
                'content' => $this->lyrics,
                'source' => 'manual',
            ],
        );

        $this->hasLyrics = true;

        $this->dispatch('song-media-updated', songTitleId: $this->songTitleId);
    }

    public function deleteLyrics(): void
    {
        if ($this->songTitleId === null) {
            return;
        }

        UserSongLyrics::query()
            ->where('user_id', auth()->id())
            ->where('song_title_id', $this->songTitleId)
            ->delete();

        $this->lyrics = '';
        $this->hasLyrics = false;

        $this->dispatch('song-media-updated', songTitleId: $this->songTitleId);
    }

    public function updatedSheetMusicUpload(): void
    {
        if ($this->songTitleId === null) {
            return;
        }

        $this->validate([
            'sheetMusicUpload' => ['file', 'mimes:pdf,png,jpg,jpeg', 'max:51200'],
        ]);

        $userId = (int) auth()->id();
        $originalName = $this->sheetMusicUpload->getClientOriginalName();
        $mimeType = $this->sheetMusicUpload->getMimeType() ?: 'application/octet-stream';
        $fileSize = $this->sheetMusicUpload->getSize() ?: 0;

        $storedPath = $this->sheetMusicUpload->store("sheet-music/{$userId}/{$this->songTitleId}");

        UserSongFile::query()->create([
            'user_id' => $userId,
            'song_title_id' => $this->songTitleId,
            'file_path' => $storedPath,
            'original_filename' => $originalName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'type' => 'sheet_music',
        ]);

        $this->sheetMusicUpload = null;

        $this->dispatch('song-media-updated', songTitleId: $this->songTitleId);
    }

    public function deleteSheetMusic(int $fileId): void
    {
        if ($this->songTitleId === null) {
            return;
        }

        $file = UserSongFile::query()
            ->where('id', $fileId)
            ->where('user_id', auth()->id())
            ->where('song_title_id', $this->songTitleId)
            ->first();

        if (! $file) {
            return;
        }

        Storage::delete($file->file_path);
        $file->delete();

        $this->dispatch('song-media-updated', songTitleId: $this->songTitleId);
    }

    public function render(): View
    {
        return view('livewire.programs.song-media-manager');
    }
}
