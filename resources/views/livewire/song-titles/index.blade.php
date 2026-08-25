<div>
    <div class="mb-6 space-y-4">
        <flux:heading size="xl">{{ __('Songs') }}</flux:heading>

        {{-- First-Time Orientation Callout --}}
        <div x-data="{ dismissed: localStorage.getItem('songTitlesOrientationDismissed') === 'true' }" x-show="! dismissed" x-collapse>
            <div x-show="! dismissed" x-transition>
                <flux:callout icon="light-bulb" color="sky">
                    <flux:callout.heading>{{ __('Discovering repertoire') }}</flux:callout.heading>
                    <flux:callout.text>
                        {{ __('Browse every song title from submitted programs. The "Programmed" column shows how many times a piece has appeared across all programs. Click the YouTube icon to hear a sample performance. Use the search bar to find songs, composers, or arrangers by name.') }}
                    </flux:callout.text>
                    <x-slot name="controls">
                        <flux:button icon="x-mark" variant="ghost" x-on:click="dismissed = true; localStorage.setItem('songTitlesOrientationDismissed', 'true')" />
                    </x-slot>
                </flux:callout>
            </div>
        </div>
    </div>

    <flux:tab.group>
        <flux:tabs wire:model="mode" variant="segmented" class="mb-6">
            <flux:tab name="browse" icon="queue-list">{{ __('Browse') }}</flux:tab>
            <flux:tab name="ai_search" icon="sparkles">{{ __('Ask AI') }}</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="browse">
        <div class="mb-6 flex justify-center gap-2">
            <flux:button wire:click="$set('filter', 'my')" :variant="$filter === 'my' ? 'primary' : 'ghost'" size="sm" title="{{ __('Songs from my programs') }}">
                {{ __('My') }} ({{ $myCount }})
            </flux:button>
            @if ($compliance['canViewAll'])
                <flux:button wire:click="$set('filter', 'all')" :variant="$filter === 'all' ? 'primary' : 'ghost'" size="sm" title="{{ __('All songs from submitted programs') }}">
                    {{ __('All') }} ({{ $allCount }})
                </flux:button>
            @else
                <flux:tooltip content="{{ __('Upload programs to unlock community data') }}">
                    <div>
                        <flux:button disabled variant="ghost" size="sm">
                            {{ __('All') }} ({{ $allCount }})
                        </flux:button>
                    </div>
                </flux:tooltip>
            @endif
        </div>

        <div class="mb-4 md:w-1/2">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search song titles, composers, arrangers...') }}" icon="magnifying-glass" />
        @if ($compliance['canViewAll'])
            <div class="mt-2">
                <flux:checkbox wire:model.live="searchLyrics" label="{{ __('Also search lyrics') }}" />
            </div>
        @endif
        </div>

        <div class="mb-4 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">{{ __('Ensemble Type') }}</span>
            @foreach ($ensembleTypes as $type)
                <flux:button
                    wire:click="toggleEnsembleTypeFilter('{{ $type->value }}')"
                    :variant="in_array($type->value, $ensembleTypeFilter) ? 'primary' : 'ghost'"
                    size="sm"
                >
                    {{ $type->label() }}
                </flux:button>
            @endforeach
            @if (! empty($ensembleTypeFilter))
                <flux:button wire:click="$set('ensembleTypeFilter', [])" variant="ghost" size="sm" icon="x-mark">
                    {{ __('Clear') }}
                </flux:button>
            @endif
        </div>

    {{-- Mobile card layout --}}
    <div class="space-y-2 md:hidden">
        @forelse ($songTitles as $songTitle)
            <div wire:key="song-title-card-{{ $songTitle->id }}" class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                            {{ $songTitle->song_title }}
                        </div>
                        <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $songTitle->composer?->artist_name }}
                            @if ($songTitle->composer && $songTitle->arranger)
                                / arr. {{ $songTitle->arranger?->artist_name }}
                            @elseif ($songTitle->arranger)
                                arr. {{ $songTitle->arranger?->artist_name }}
                            @endif
                        </div>
                        @if ($ensembleTypeMap->has($songTitle->id))
                            <div class="mt-1.5 flex flex-wrap gap-1">
                                @foreach ($ensembleTypeMap->get($songTitle->id) as $type)
                                    <flux:badge size="sm" rounded>{{ $type->label() }}</flux:badge>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($viewableVideoMap->has($songTitle->id))
                            @php $mapEntry = $viewableVideoMap->get($songTitle->id); @endphp
                            <button
                                wire:click="playVideo({{ $songTitle->id }}, {{ $mapEntry['program_id'] }}, '{{ addslashes($songTitle->song_title) }}', {{ $mapEntry['is_audio'] ? 'true' : 'false' }})"
                                title="{{ $mapEntry['is_audio'] ? __('Listen') : __('Watch Video') }}"
                                class="cursor-pointer"
                            >
                                <flux:icon name="{{ $mapEntry['is_audio'] ? 'musical-note' : 'video-camera' }}" class="size-5 text-teal-600 hover:text-teal-500" />
                            </button>
                        @endif
                        <a href="https://www.youtube.com/results?search_query={{ urlencode($songTitle->song_title . ' ' . ($songTitle->composer?->artist_name ?? '')) }}" target="_blank" rel="noopener noreferrer" title="{{ __('Search YouTube') }}">
                            <svg class="size-5 text-red-600 hover:text-red-500" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        </a>
                        <flux:badge size="sm" variant="pill">{{ $songTitle->performed_count }}</flux:badge>
                    </div>

                </div>
            </div>
        @empty
            <div class="rounded-xl border border-neutral-200 px-6 py-12 text-center dark:border-neutral-700">
                <flux:icon name="queue-list" class="mx-auto size-10 text-neutral-300 dark:text-neutral-600 mb-3" />
                <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">{{ __('No song titles found') }}</p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mb-4">{{ __('Song titles are extracted when you upload a concert program.') }}</p>
                <flux:button href="{{ route('addProgram') }}" variant="primary" size="sm" icon="plus">
                    {{ __('Add Program') }}
                </flux:button>
            </div>
        @endforelse
    </div>

    {{-- Desktop table layout --}}
    <div class="hidden overflow-hidden rounded-xl border border-neutral-200 md:block dark:border-neutral-700">
        <table class="w-full table-fixed divide-y divide-neutral-200 dark:divide-neutral-700">
            <colgroup>
                <col class="w-[5%]" />
                <col class="w-[27%]" />
                <col class="w-[20%]" />
                <col class="w-[15%]" />
                <col class="w-[11%]" />
                <col class="w-[11%]" />
                <col class="w-[11%]" />
            </colgroup>
            <thead class="bg-neutral-50 dark:bg-neutral-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        #
                    </th>
                    <th wire:click="sort('song_title')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">
                        <div class="flex items-center gap-1">
                            {{ __('Song Title') }}
                            @if ($sortBy === 'song_title')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @else
                                <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        <div class="space-y-0.5">
                            <div wire:click="sort('composer')" class="flex cursor-pointer items-center gap-1 hover:text-neutral-700 dark:hover:text-neutral-200">
                                {{ __('Composer') }}
                                @if ($sortBy === 'composer')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                                @else
                                    <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                                @endif
                            </div>
                            <div wire:click="sort('arranger')" class="flex cursor-pointer items-center gap-1 hover:text-neutral-700 dark:hover:text-neutral-200">
                                {{ __('Arranger') }}
                                @if ($sortBy === 'arranger')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                                @else
                                    <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                                @endif
                            </div>
                        </div>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('Ensemble Type') }}
                    </th>
                    <th wire:click="sort('performed')" class="cursor-pointer px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">
                        <div class="flex items-center justify-center gap-1">
                            {{ __('Programmed') }}
                            @if ($sortBy === 'performed')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @else
                                <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                            @endif
                        </div>
                    </th>
                    <th class="px-2 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('Media') }}
                    </th>
                    <th class="px-2 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('YouTube') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                @forelse ($songTitles as $songTitle)
                    <tr wire:key="song-title-{{ $songTitle->id }}">
                        <td class="whitespace-nowrap px-4 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $loop->iteration }}
                        </td>
                        <td class="truncate px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100">
                            {{ $songTitle->song_title }}
                        </td>
                        <td class="px-4 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            @if ($songTitle->composer)
                                <div class="truncate">{{ $songTitle->composer->artist_name }}</div>
                            @endif
                            @if ($songTitle->arranger)
                                <div class="truncate text-xs">arr. {{ $songTitle->arranger->artist_name }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($ensembleTypeMap->get($songTitle->id, collect()) as $type)
                                    <flux:badge size="sm" rounded>{{ $type->label() }}</flux:badge>
                                @endforeach
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-2 text-center text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $songTitle->performed_count }}
                        </td>
                        <td class="px-2 py-2">
                            <div class="flex items-center justify-center">
                                @if ($viewableVideoMap->has($songTitle->id))
                                    @php $mapEntry = $viewableVideoMap->get($songTitle->id); @endphp
                                    <button
                                        wire:click="playVideo({{ $songTitle->id }}, {{ $mapEntry['program_id'] }}, '{{ addslashes($songTitle->song_title) }}', {{ $mapEntry['is_audio'] ? 'true' : 'false' }})"
                                        title="{{ $mapEntry['is_audio'] ? __('Listen') : __('Watch Video') }}"
                                        class="cursor-pointer"
                                    >
                                        <flux:icon name="{{ $mapEntry['is_audio'] ? 'musical-note' : 'video-camera' }}" class="size-5 text-teal-600 hover:text-teal-500" />
                                    </button>
                                @endif
                            </div>
                        </td>
                        <td class="px-2 py-2">
                            <div class="flex items-center justify-center">
                                <a href="https://www.youtube.com/results?search_query={{ urlencode($songTitle->song_title . ' ' . ($songTitle->composer?->artist_name ?? '')) }}" target="_blank" rel="noopener noreferrer" title="{{ __('Search YouTube') }}">
                                    <svg class="size-5 text-red-600 hover:text-red-500" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="mx-auto max-w-sm">
                                <flux:icon name="queue-list" class="mx-auto size-10 text-neutral-300 dark:text-neutral-600 mb-3" />
                                <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">{{ __('No song titles found') }}</p>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400 mb-4">{{ __('Song titles are extracted when you upload a concert program.') }}</p>
                                <flux:button href="{{ route('addProgram') }}" variant="primary" size="sm" icon="plus">
                                    {{ __('Add Program') }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
        </flux:tab.panel>

        <flux:tab.panel name="ai_search">
            <div class="max-w-3xl space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Ask about repertoire') }}</flux:heading>
                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        {{ __('Describe what you\'re looking for in plain language — voicing, difficulty balance, theme, or occasion.') }}
                    </p>
                </div>

                <form wire:submit="askAi" class="space-y-3">
                    <flux:textarea
                        wire:model="aiQuery"
                        rows="3"
                        :disabled="$aiSearching"
                        placeholder="{{ __('e.g. High school SATB pieces that are easy for the men and challenging for the women') }}"
                    />
                    @error('aiQuery')
                        <flux:text size="sm" class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror

                    <div class="flex items-center gap-3">
                        <flux:button type="submit" variant="primary" :disabled="$aiSearching">
                            {{ $aiSearching ? __('Searching…') : __('Search') }}
                        </flux:button>
                        @if ($aiResultQueryId || $aiError)
                            <flux:button type="button" variant="ghost" wire:click="resetAiSearch">
                                {{ __('New search') }}
                            </flux:button>
                        @endif
                    </div>
                </form>

                @if ($aiSearching)
                    <div wire:poll.2s="checkAiSearchStatus" class="flex items-center gap-3 rounded-xl border border-neutral-200 px-4 py-3 text-sm text-neutral-600 dark:border-neutral-700 dark:text-neutral-400">
                        <flux:icon name="arrow-path" class="size-4 animate-spin" />
                        {{ __('Searching the catalog and the web — this can take up to 30 seconds…') }}
                    </div>
                @endif

                @if ($aiError)
                    <flux:callout icon="exclamation-triangle" color="red">
                        <flux:callout.heading>{{ __('Search failed') }}</flux:callout.heading>
                        <flux:callout.text>{{ $aiError }}</flux:callout.text>
                    </flux:callout>
                @endif

                @if ($aiResult)
                    <x-repertoire.results :result="$aiResult" />
                @endif
            </div>
        </flux:tab.panel>
    </flux:tab.group>

    {{-- Media Player Modal --}}
    <flux:modal name="song-video-player" class="max-w-3xl">
        @if ($videoSongTitleId && $videoProgramId)
            <div class="space-y-4">
                <flux:heading size="lg">{{ $videoSongName }}</flux:heading>
                @if ($isAudio)
                    <audio
                        controls
                        class="w-full"
                        src="{{ route('videos.song', [$videoProgramId, $videoSongTitleId]) }}"
                    >
                        {{ __('Your browser does not support the audio tag.') }}
                    </audio>
                @else
                    <video
                        controls
                        class="w-full rounded-lg"
                        src="{{ route('videos.song', [$videoProgramId, $videoSongTitleId]) }}"
                    >
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
