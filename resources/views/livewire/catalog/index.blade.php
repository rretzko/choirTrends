<div class="max-w-full overflow-hidden">
    <div class="mb-6 space-y-4">
        <div>
            <flux:heading size="xl">{{ __('Program Catalog') }}</flux:heading>
            <flux:text class="mt-1 text-neutral-500 dark:text-neutral-400">
                {{ __('Programs by :name', ['name' => $user->name]) }}
            </flux:text>
        </div>

        @if ($schools->count() > 1)
            <div class="flex items-center gap-2">
                <select
                    wire:model.live="schoolFilter"
                    class="rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100"
                >
                    <option value="">{{ __('All Schools') }}</option>
                    @foreach ($schools as $school)
                        <option value="{{ $school->id }}">{{ $school->school_name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    {{-- Desktop table --}}
    <div class="hidden overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700 sm:block">
        <table class="w-full table-fixed divide-y divide-neutral-200 dark:divide-neutral-700">
            <thead class="bg-neutral-50 dark:bg-neutral-800">
                <tr>
                    <th class="w-1/4 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('School') }}
                    </th>
                    <th class="w-1/4 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        <div>
                            {{ __('Program Name') }}
                            <div class="text-[10px] normal-case tracking-normal">{{ __('(click for details)') }}</div>
                        </div>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('Program Date') }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('Director') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                @forelse ($programs as $program)
                    <tr wire:key="catalog-program-{{ $program->id }}">
                        <td class="px-4 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $program->school->school_name ?? '' }}
                        </td>
                        <td class="px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100">
                            <flux:button
                                wire:click="showProgramDetails({{ $program->id }})"
                                variant="filled"
                                size="sm"
                                class="!whitespace-normal text-left max-w-full"
                            >
                                {{ $program->event_name }}
                            </flux:button>
                        </td>
                        <td class="whitespace-nowrap px-4 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $program->event_date->format('M j, Y') }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $program->director_name ?? '' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center">
                            <div class="mx-auto max-w-sm">
                                <flux:icon name="document-text" class="mx-auto size-10 text-neutral-300 dark:text-neutral-600 mb-3" />
                                <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">{{ __('No programs found') }}</p>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('No programs match the current filter.') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="space-y-3 sm:hidden">
        @forelse ($programs as $program)
            <button
                wire:key="catalog-mobile-{{ $program->id }}"
                wire:click="showProgramDetails({{ $program->id }})"
                class="w-full rounded-xl border border-neutral-200 bg-white p-4 text-left transition-colors hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800 dark:hover:bg-neutral-700"
            >
                <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ $program->event_name }}</div>
                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ $program->school->school_name ?? '' }}</div>
                <div class="mt-1 flex items-center justify-between text-xs text-neutral-400 dark:text-neutral-500">
                    <span>{{ $program->event_date->format('M j, Y') }}</span>
                    <span>{{ $program->director_name ?? '' }}</span>
                </div>
            </button>
        @empty
            <div class="rounded-xl border border-neutral-200 bg-white p-8 text-center dark:border-neutral-700 dark:bg-neutral-800">
                <flux:icon name="document-text" class="mx-auto size-10 text-neutral-300 dark:text-neutral-600 mb-3" />
                <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">{{ __('No programs found') }}</p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('No programs match the current filter.') }}</p>
            </div>
        @endforelse
    </div>

    {{-- Program Details Modal --}}
    <flux:modal name="catalog-program-details" class="max-w-2xl">
        @if ($selectedProgram)
            <div class="space-y-6">
                <flux:heading size="lg">{{ $selectedProgram->event_name }}</flux:heading>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text class="font-medium text-neutral-500 dark:text-neutral-400">{{ __('Program Date') }}</flux:text>
                        <flux:text>{{ $selectedProgram->event_date->format('M j, Y') }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="font-medium text-neutral-500 dark:text-neutral-400">{{ __('School') }}</flux:text>
                        <flux:text>{{ $selectedProgram->school->school_name ?? '' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="font-medium text-neutral-500 dark:text-neutral-400">{{ __('Director') }}</flux:text>
                        <flux:text>{{ $selectedProgram->director_name ?? '' }}</flux:text>
                    </div>
                </div>

                @if ($selectedProgram->video_path)
                    <div>
                        <flux:button wire:click="playConcertVideo" type="button" variant="filled" size="sm" icon="video-camera" class="text-teal-600">
                            {{ __('Watch Concert Video') }}
                        </flux:button>
                    </div>
                @endif

                @if ($songsByEnsemble->isNotEmpty())
                    <div class="space-y-4">
                        @foreach ($songsByEnsemble as $group)
                            <div>
                                <flux:heading size="sm" class="mb-2">
                                    {{ $group['ensemble']?->ensemble_name ?? __('Other') }}
                                </flux:heading>
                                <ul class="space-y-1 pl-4">
                                    @foreach ($group['songs'] as $songTitle)
                                        <li class="text-sm text-neutral-900 dark:text-neutral-100">
                                            @if ($songTitle->pivot?->video_path)
                                                @php $songIsAudio = in_array(strtolower(pathinfo($songTitle->pivot->video_path, PATHINFO_EXTENSION)), ['mp3', 'wav', 'm4a', 'ogg', 'flac', 'aac', 'wma']); @endphp
                                                <button
                                                    wire:click="playSongVideo({{ $songTitle->id }}, '{{ addslashes($songTitle->song_title) }}', {{ $songIsAudio ? 'true' : 'false' }})"
                                                    title="{{ $songIsAudio ? __('Listen') : __('Watch Video') }}"
                                                    class="mr-1 inline cursor-pointer align-middle"
                                                >
                                                    <flux:icon name="{{ $songIsAudio ? 'musical-note' : 'video-camera' }}" class="inline size-4 text-teal-600 hover:text-teal-500" />
                                                </button>
                                            @endif
                                            {{ $songTitle->song_title }}
                                            @if ($songTitle->composer || $songTitle->arranger)
                                                <span class="text-neutral-500 dark:text-neutral-400">
                                                    —
                                                    @if ($songTitle->composer)
                                                        {{ $songTitle->composer->artist_name }}
                                                    @endif
                                                    @if ($songTitle->composer && $songTitle->arranger)
                                                        /
                                                    @endif
                                                    @if ($songTitle->arranger)
                                                        arr. {{ $songTitle->arranger->artist_name }}
                                                    @endif
                                                </span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Close') }}</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- Media Player Modal --}}
    <flux:modal name="catalog-video-player" class="max-w-3xl">
        @if ($videoProgramId)
            <div class="space-y-4">
                <flux:heading size="lg">
                    {{ $showConcertVideo ? $selectedProgram?->event_name : $videoSongName }}
                </flux:heading>
                @php
                    $mediaSrc = $showConcertVideo
                        ? route('catalog.videos.program', [$user->catalog_token, $videoProgramId])
                        : route('catalog.videos.song', [$user->catalog_token, $videoProgramId, $videoSongTitleId]);
                @endphp
                @if ($isAudio && ! $showConcertVideo)
                    <audio controls class="w-full" src="{{ $mediaSrc }}">
                        {{ __('Your browser does not support the audio tag.') }}
                    </audio>
                @else
                    <video controls class="w-full rounded-lg" src="{{ $mediaSrc }}">
                        {{ __('Your browser does not support the video tag.') }}
                    </video>
                @endif
                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Close') }}</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
