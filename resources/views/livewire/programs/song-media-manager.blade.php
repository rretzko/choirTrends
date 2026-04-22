<div>
    <flux:modal name="song-media-manager" class="max-w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Manage Media') }}</flux:heading>
                @if ($songTitleLabel !== '')
                    <flux:text class="mt-1 text-neutral-500 dark:text-neutral-400">{{ $songTitleLabel }}</flux:text>
                @endif
            </div>

            {{-- Audio / Video --}}
            <section class="space-y-3">
                <flux:heading size="sm">{{ __('Audio / Video') }}</flux:heading>

                @if ($videoPath)
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2 text-sm text-teal-600 dark:text-teal-400">
                            @php
                                $extension = pathinfo($videoPath, PATHINFO_EXTENSION);
                                $isAudio = in_array($extension, ['mp3', 'wav', 'm4a', 'ogg', 'flac', 'aac', 'wma'], true);
                            @endphp
                            <flux:icon name="{{ $isAudio ? 'musical-note' : 'video-camera' }}" class="size-5" />
                            <span>{{ $isAudio ? __('Audio uploaded') : __('Video uploaded') }}</span>
                        </div>

                        <flux:button wire:click="toggleAudioVideoVisibility" type="button" variant="ghost" size="sm">
                            <flux:icon name="{{ $videoVisibility === 'Public' ? 'eye' : 'eye-slash' }}" class="size-4" />
                            {{ $videoVisibility === 'Public' ? __('Public') : __('Private') }}
                        </flux:button>

                        <flux:button wire:click="removeAudioVideo" wire:confirm="{{ __('Remove this file?') }}" type="button" variant="ghost" size="sm" class="text-red-600 hover:text-red-500">
                            <flux:icon name="trash" class="size-4" />
                            {{ __('Delete') }}
                        </flux:button>
                    </div>
                @else
                    <div
                        x-data="{ uploading: false, progress: 0 }"
                        x-on:livewire-upload-start="uploading = true"
                        x-on:livewire-upload-finish="uploading = false"
                        x-on:livewire-upload-cancel="uploading = false"
                        x-on:livewire-upload-error="uploading = false"
                        x-on:livewire-upload-progress="progress = $event.detail.progress"
                    >
                        <input type="file" wire:model="audioVideoUpload" accept=".mp4,.mov,.avi,.wmv,.mp3,.wav,.m4a,.ogg,.flac,.aac,.wma" class="block w-full text-sm text-neutral-500 file:mr-4 file:rounded file:border-0 file:bg-neutral-100 file:px-4 file:py-2 file:text-sm file:font-medium hover:file:bg-neutral-200 dark:text-neutral-400 dark:file:bg-neutral-700 dark:hover:file:bg-neutral-600" />
                        <div x-show="uploading" x-cloak class="mt-2">
                            <div class="h-2 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                <div class="h-2 rounded-full bg-teal-500 transition-all" x-bind:style="'width: ' + progress + '%'"></div>
                            </div>
                            <p class="mt-1 text-xs text-neutral-500" x-text="progress + '% uploaded'"></p>
                        </div>
                        @error('audioVideoUpload') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif
            </section>

            {{-- Lyrics / Text --}}
            <section class="space-y-3">
                <flux:heading size="sm">{{ __('Lyrics / Text') }}</flux:heading>

                <flux:field>
                    <flux:textarea wire:model="lyrics" rows="8" placeholder="{{ __('Paste or type lyrics here') }}" />
                    <flux:error name="lyrics" />
                </flux:field>

                <div class="flex items-center gap-2">
                    <flux:button wire:click="saveLyrics" type="button" variant="primary" size="sm">
                        {{ $hasLyrics ? __('Update Lyrics') : __('Save Lyrics') }}
                    </flux:button>

                    @if ($hasLyrics)
                        <flux:button wire:click="deleteLyrics" wire:confirm="{{ __('Delete lyrics for this song?') }}" type="button" variant="ghost" size="sm" class="text-red-600 hover:text-red-500">
                            <flux:icon name="trash" class="size-4" />
                            {{ __('Delete') }}
                        </flux:button>
                    @endif
                </div>

                <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                    {{ __('Lyrics are stored for your personal reference. They are never shown to other users.') }}
                </flux:text>
            </section>

            {{-- Sheet Music --}}
            <section class="space-y-3">
                <flux:heading size="sm">{{ __('Sheet Music') }}</flux:heading>

                @if ($this->sheetMusicFiles->count() > 0)
                    <ul class="divide-y divide-neutral-100 rounded border border-neutral-200 dark:divide-neutral-800 dark:border-neutral-700">
                        @foreach ($this->sheetMusicFiles as $file)
                            <li wire:key="sheet-music-{{ $file->id }}" class="flex items-center justify-between gap-3 px-3 py-2">
                                <div class="flex min-w-0 items-center gap-2">
                                    <flux:icon name="document" class="size-5 text-neutral-500" />
                                    <span class="truncate text-sm" title="{{ $file->original_filename }}">{{ $file->original_filename }}</span>
                                    <span class="text-xs text-neutral-500">({{ number_format($file->file_size / 1024 / 1024, 1) }} MB)</span>
                                </div>
                                <div class="flex shrink-0 items-center gap-1">
                                    <flux:button href="{{ route('media.sheet-music.show', $file) }}" target="_blank" rel="noopener" type="button" variant="ghost" size="xs" icon="eye" title="{{ __('View') }}" />
                                    <flux:button wire:click="deleteSheetMusic({{ $file->id }})" wire:confirm="{{ __('Delete this sheet music file?') }}" type="button" variant="ghost" size="xs" class="text-red-600 hover:text-red-500">
                                        <flux:icon name="trash" class="size-3" />
                                    </flux:button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div
                    x-data="{ uploading: false, progress: 0 }"
                    x-on:livewire-upload-start="uploading = true"
                    x-on:livewire-upload-finish="uploading = false"
                    x-on:livewire-upload-cancel="uploading = false"
                    x-on:livewire-upload-error="uploading = false"
                    x-on:livewire-upload-progress="progress = $event.detail.progress"
                >
                    <input type="file" wire:model="sheetMusicUpload" accept=".pdf,.png,.jpg,.jpeg" class="block w-full text-sm text-neutral-500 file:mr-4 file:rounded file:border-0 file:bg-neutral-100 file:px-4 file:py-2 file:text-sm file:font-medium hover:file:bg-neutral-200 dark:text-neutral-400 dark:file:bg-neutral-700 dark:hover:file:bg-neutral-600" />
                    <div x-show="uploading" x-cloak class="mt-2">
                        <div class="h-2 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                            <div class="h-2 rounded-full bg-teal-500 transition-all" x-bind:style="'width: ' + progress + '%'"></div>
                        </div>
                        <p class="mt-1 text-xs text-neutral-500" x-text="progress + '% uploaded'"></p>
                    </div>
                    @error('sheetMusicUpload') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <flux:text class="text-xs text-amber-600 dark:text-amber-400">
                    <flux:icon name="exclamation-triangle" class="inline size-3" />
                    {{ __('Sheet music files are private to your account and never shared with other users.') }}
                </flux:text>
            </section>

            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Close') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
