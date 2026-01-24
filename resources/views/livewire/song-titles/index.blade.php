<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Song Titles') }}</flux:heading>

        <div class="flex gap-2">
            <flux:button wire:click="$set('filter', 'my')" :variant="$filter === 'my' ? 'primary' : 'ghost'" size="sm">
                {{ __('My') }} ({{ $myCount }})
            </flux:button>
            <flux:button wire:click="$set('filter', 'all')" :variant="$filter === 'all' ? 'primary' : 'ghost'" size="sm">
                {{ __('All') }} ({{ $allCount }})
            </flux:button>
        </div>
    </div>

    <div class="mb-4 md:w-1/2">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search song titles, composers, arrangers...') }}" icon="magnifying-glass" />
    </div>

    <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
            <thead class="bg-neutral-50 dark:bg-neutral-800">
                <tr>
                    <th class="w-16 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        #
                    </th>
                    <th wire:click="sort('song_title')" class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">
                        <div class="flex items-center gap-1">
                            {{ __('Song Title') }}
                            @if ($sortBy === 'song_title')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @endif
                        </div>
                    </th>
                    <th wire:click="sort('composer')" class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">
                        <div class="flex items-center gap-1">
                            {{ __('Composer') }}
                            @if ($sortBy === 'composer')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @endif
                        </div>
                    </th>
                    <th wire:click="sort('arranger')" class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">
                        <div class="flex items-center gap-1">
                            {{ __('Arranger') }}
                            @if ($sortBy === 'arranger')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @endif
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                @forelse ($songTitles as $songTitle)
                    <tr wire:key="song-title-{{ $songTitle->id }}">
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $loop->iteration }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-neutral-900 dark:text-neutral-100">
                            {{ $songTitle->song_title }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $songTitle->composer?->artist_name }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $songTitle->arranger?->artist_name }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400">
                            {{ __('No song titles found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
