<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Program') }}</flux:heading>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Program-level fields --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <flux:field>
                <flux:label>{{ __('Event Name') }}</flux:label>
                <flux:input wire:model="eventName" />
                <flux:error name="eventName" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Event Date') }}</flux:label>
                <flux:input wire:model="eventDate" type="date" />
                <flux:error name="eventDate" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Director Name') }}</flux:label>
                <flux:input wire:model="directorName" />
                <flux:error name="directorName" />
            </flux:field>

            <flux:field>
                <flux:label class="!flex w-full justify-between">
                    <span>{{ __('School Name') }}</span>
                    @unless ($schoolEditable)
                        <span class="mr-2 inline-flex items-center gap-1 text-amber-600 dark:text-amber-400"><flux:icon name="lock-closed" class="size-3" /> {{ __('Shared') }}</span>
                    @endunless
                </flux:label>
                <flux:input wire:model="schoolName" :disabled="! $schoolEditable" />
                <flux:error name="schoolName" />
            </flux:field>
        </div>

        {{-- Concert Video --}}
        <div class="rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
            <flux:heading size="sm" class="mb-3">{{ __('Concert Video') }}</flux:heading>

            @if ($hasProgramVideo)
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 text-sm text-teal-600 dark:text-teal-400">
                        <flux:icon name="video-camera" class="size-5" />
                        <span>{{ __('Video uploaded') }}</span>
                    </div>
                    <flux:button wire:click="toggleProgramVideoVisibility" type="button" variant="ghost" size="sm">
                        <flux:icon name="{{ $programVideoVisibility === 'Public' ? 'eye' : 'eye-slash' }}" class="size-4" />
                        {{ $programVideoVisibility === 'Public' ? __('Public') : __('Private') }}
                    </flux:button>
                    <flux:button wire:click="removeProgramVideo" wire:confirm="{{ __('Remove this video?') }}" type="button" variant="ghost" size="sm" class="text-red-600 hover:text-red-500">
                        <flux:icon name="trash" class="size-4" />
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
                    <input type="file" wire:model="programVideo" accept=".mp4,.mov,.avi,.wmv" class="block w-full text-sm text-neutral-500 file:mr-4 file:rounded file:border-0 file:bg-neutral-100 file:px-4 file:py-2 file:text-sm file:font-medium hover:file:bg-neutral-200 dark:text-neutral-400 dark:file:bg-neutral-700 dark:hover:file:bg-neutral-600" />
                    <div x-show="uploading" x-cloak class="mt-2">
                        <div class="h-2 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                            <div class="h-2 rounded-full bg-teal-500 transition-all" x-bind:style="'width: ' + progress + '%'"></div>
                        </div>
                        <p class="mt-1 text-xs text-neutral-500" x-text="progress + '% uploaded'"></p>
                    </div>
                    @error('programVideo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif
        </div>

        {{-- Ensembles --}}
        @foreach ($ensembles as $eIndex => $ensemble)
            <div wire:key="ensemble-{{ $eIndex }}" class="rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div class="grow">
                        <flux:field>
                            <flux:label class="!flex w-full justify-between">
                                <span>{{ __('Ensemble Name') }}</span>
                                @unless ($ensemble['editable'])
                                    <span class="mr-2 inline-flex items-center gap-1 text-amber-600 dark:text-amber-400"><flux:icon name="lock-closed" class="size-3" /> {{ __('Shared') }}</span>
                                @endunless
                            </flux:label>
                            <flux:input wire:model="ensembles.{{ $eIndex }}.name" :disabled="! $ensemble['editable']" />
                        </flux:field>
                    </div>

                    <flux:button wire:click="removeEnsemble({{ $eIndex }})" type="button" variant="danger" size="sm" icon="trash">
                        {{ __('Remove') }}
                    </flux:button>
                </div>

                {{-- Songs --}}
                @foreach ($ensemble['songs'] as $sIndex => $song)
                    <div wire:key="song-{{ $eIndex }}-{{ $sIndex }}" class="mb-4 ml-4 flex items-start gap-2 rounded border border-neutral-100 p-3 dark:border-neutral-800">
                        <flux:field class="w-16 shrink-0">
                            <flux:label>{{ __('Order') }}</flux:label>
                            <flux:input wire:model="ensembles.{{ $eIndex }}.songs.{{ $sIndex }}.sortOrder" type="number" min="1" />
                        </flux:field>

                        <div class="grid grow grid-cols-1 gap-4 sm:grid-cols-3">
                            <flux:field>
                                <flux:label class="!flex w-full justify-between">
                                    <span>{{ __('Song Title') }}</span>
                                    @unless ($song['titleEditable'])
                                        <span class="mr-2 inline-flex items-center gap-1 text-amber-600 dark:text-amber-400"><flux:icon name="lock-closed" class="size-3" /> {{ __('Shared') }}</span>
                                    @endunless
                                </flux:label>
                                <flux:input wire:model="ensembles.{{ $eIndex }}.songs.{{ $sIndex }}.title" :disabled="! $song['titleEditable']" />
                                <flux:error name="ensembles.{{ $eIndex }}.songs.{{ $sIndex }}.title" />
                            </flux:field>

                            <flux:field>
                                <flux:label class="!flex w-full justify-between">
                                    <span>{{ __('Composer') }}</span>
                                    @unless ($song['composerEditable'])
                                        <span class="mr-2 inline-flex items-center gap-1 text-amber-600 dark:text-amber-400"><flux:icon name="lock-closed" class="size-3" /> {{ __('Shared') }}</span>
                                    @endunless
                                </flux:label>
                                <flux:input wire:model="ensembles.{{ $eIndex }}.songs.{{ $sIndex }}.composer" :disabled="! $song['composerEditable']" />
                            </flux:field>

                            <flux:field>
                                <flux:label class="!flex w-full justify-between">
                                    <span>{{ __('Arranger') }}</span>
                                    @unless ($song['arrangerEditable'])
                                        <span class="mr-2 inline-flex items-center gap-1 text-amber-600 dark:text-amber-400"><flux:icon name="lock-closed" class="size-3" /> {{ __('Shared') }}</span>
                                    @endunless
                                </flux:label>
                                <flux:input wire:model="ensembles.{{ $eIndex }}.songs.{{ $sIndex }}.arranger" :disabled="! $song['arrangerEditable']" />
                            </flux:field>
                        </div>

                        <flux:button wire:click="removeSong({{ $eIndex }}, {{ $sIndex }})" type="button" variant="ghost" size="sm" icon="trash" title="{{ __('Remove Song') }}" class="mt-6 shrink-0" />

                        {{-- Song Video --}}
                        @if ($song['songTitleId'])
                            <div class="mt-2 w-full border-t border-neutral-100 pt-2 dark:border-neutral-800">
                                @if ($song['videoPath'])
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center gap-1 text-xs text-teal-600 dark:text-teal-400">
                                            @if (in_array(pathinfo($song['videoPath'], PATHINFO_EXTENSION), ['mp3', 'wav', 'm4a', 'ogg', 'flac', 'aac', 'wma']))
                                                <flux:icon name="musical-note" class="size-4" />
                                                <span>{{ __('Audio') }}</span>
                                            @else
                                                <flux:icon name="video-camera" class="size-4" />
                                                <span>{{ __('Video') }}</span>
                                            @endif
                                        </div>
                                        <flux:button wire:click="toggleSongVideoVisibility({{ $eIndex }}, {{ $sIndex }})" type="button" variant="ghost" size="xs">
                                            <flux:icon name="{{ $song['videoVisibility'] === 'Public' ? 'eye' : 'eye-slash' }}" class="size-3" />
                                            {{ $song['videoVisibility'] === 'Public' ? __('Public') : __('Private') }}
                                        </flux:button>
                                        <flux:button wire:click="removeSongVideo({{ $eIndex }}, {{ $sIndex }})" wire:confirm="{{ __('Remove this file?') }}" type="button" variant="ghost" size="xs" class="text-red-600 hover:text-red-500">
                                            <flux:icon name="trash" class="size-3" />
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
                                        class="flex items-center gap-2"
                                    >
                                        <input type="file" wire:model="songVideos.{{ $eIndex }}-{{ $sIndex }}" accept=".mp4,.mov,.avi,.wmv,.mp3,.wav,.m4a,.ogg,.flac,.aac,.wma" class="block w-full text-xs text-neutral-500 file:mr-2 file:rounded file:border-0 file:bg-neutral-100 file:px-2 file:py-1 file:text-xs dark:text-neutral-400 dark:file:bg-neutral-700" />
                                        <flux:button wire:click="uploadSongVideo({{ $eIndex }}, {{ $sIndex }})" type="button" variant="ghost" size="xs" icon="arrow-up-tray" title="{{ __('Upload') }}" />
                                        <div x-show="uploading" x-cloak class="flex items-center gap-1">
                                            <div class="h-1.5 w-16 overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                                <div class="h-1.5 rounded-full bg-teal-500 transition-all" x-bind:style="'width: ' + progress + '%'"></div>
                                            </div>
                                        </div>
                                        @error("songVideos.{$eIndex}-{$sIndex}") <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach

                <flux:button wire:click="addSong({{ $eIndex }})" type="button" variant="ghost" size="sm" icon="plus">
                    {{ __('Add Song') }}
                </flux:button>
            </div>
        @endforeach

        <div>
            <flux:button wire:click="addEnsemble" type="button" variant="ghost" size="sm" icon="plus">
                {{ __('Add Ensemble') }}
            </flux:button>
        </div>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
            <flux:button href="{{ route('programs.index') }}" variant="ghost">{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</div>
