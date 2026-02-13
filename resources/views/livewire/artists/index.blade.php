<div>
    <div class="mb-6 space-y-4">
        <flux:heading size="xl">{{ __('Composers and Arrangers') }}</flux:heading>

        <div class="flex justify-center gap-2">
            <flux:button wire:click="$set('filter', 'my')" :variant="$filter === 'my' ? 'primary' : 'ghost'" size="sm" title="{{ __('Composers and arrangers from my programs') }}">
                {{ __('My') }} ({{ $myCount }})
            </flux:button>
            <flux:button wire:click="$set('filter', 'all')" :variant="$filter === 'all' ? 'primary' : 'ghost'" size="sm" title="{{ __('Composers and arrangers from all submitted programs') }}">
                {{ __('All') }} ({{ $allCount }})
            </flux:button>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <table class="w-full table-fixed divide-y divide-neutral-200 dark:divide-neutral-700">
            <colgroup>
                <col class="w-12" />
                <col />
                <col class="w-1/5" />
                <col class="w-1/5" />
                <col class="w-24" />
            </colgroup>
            <thead class="bg-neutral-50 dark:bg-neutral-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        #
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        <button wire:click="sort('artist_name')" class="flex items-center gap-1 hover:text-neutral-700 dark:hover:text-neutral-200">
                            {{ __('Artist Name') }}
                            @if ($sortColumn === 'artist_name')
                                <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-3" />
                            @else
                                <flux:icon name="arrows-up-down" class="size-3 opacity-30" />
                            @endif
                        </button>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        <button wire:click="sort('artist_first_name')" class="flex items-center gap-1 hover:text-neutral-700 dark:hover:text-neutral-200">
                            {{ __('First Name') }}
                            @if ($sortColumn === 'artist_first_name')
                                <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-3" />
                            @else
                                <flux:icon name="arrows-up-down" class="size-3 opacity-30" />
                            @endif
                        </button>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        <button wire:click="sort('artist_last_name')" class="flex items-center gap-1 hover:text-neutral-700 dark:hover:text-neutral-200">
                            {{ __('Last Name') }}
                            @if ($sortColumn === 'artist_last_name')
                                <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-3" />
                            @else
                                <flux:icon name="arrows-up-down" class="size-3 opacity-30" />
                            @endif
                        </button>
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        <button wire:click="sort('repertoire_count')" class="mx-auto flex items-center gap-1 hover:text-neutral-700 dark:hover:text-neutral-200">
                            {{ __('Rep#') }}
                            @if ($sortColumn === 'repertoire_count')
                                <flux:icon :name="$sortDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-3" />
                            @else
                                <flux:icon name="arrows-up-down" class="size-3 opacity-30" />
                            @endif
                        </button>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                @forelse ($artists as $artist)
                    <tr wire:key="artist-{{ $artist->id }}">
                        <td class="whitespace-nowrap px-6 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $loop->iteration }}
                        </td>
                        <td class="truncate px-6 py-2 text-sm text-neutral-900 dark:text-neutral-100">
                            {{ $artist->artist_name }}
                        </td>
                        <td class="truncate px-6 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $artist->artist_first_name }}
                        </td>
                        <td class="truncate px-6 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $artist->artist_last_name }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-2 text-center text-sm">
                            @if ($artist->repertoire_count > 0)
                                <flux:button wire:click="showRepertoire({{ $artist->id }})" variant="filled" size="sm">
                                    {{ $artist->repertoire_count }}
                                </flux:button>
                            @else
                                <span class="text-neutral-500 dark:text-neutral-400">0</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400">
                            {{ __('No artists found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal name="artist-repertoire" class="max-w-2xl">
        @if ($selectedArtist)
            <div class="space-y-6">
                <flux:heading size="lg">{{ $selectedArtist->artist_name }}</flux:heading>

                <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <table class="w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                        <thead class="bg-neutral-50 dark:bg-neutral-800">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                    {{ __('Song Title') }}
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                    {{ __('Role') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                            @foreach ($repertoire as $song)
                                <tr wire:key="song-{{ $song->id }}">
                                    <td class="px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100">
                                        {{ $song->song_title }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                                        @if ($song->composer_id === $selectedArtist->id && $song->arranger_id === $selectedArtist->id)
                                            {{ __('Composer & Arranger') }}
                                        @elseif ($song->composer_id === $selectedArtist->id)
                                            {{ __('Composer') }}
                                        @else
                                            {{ __('Arranger') }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Close') }}</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
