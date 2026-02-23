<div>
    <div class="mb-6 space-y-4">
        <flux:heading size="xl">{{ __('Schools') }}</flux:heading>

        <div class="flex justify-center gap-2">
            <flux:button wire:click="$set('filter', 'my')" :variant="$filter === 'my' ? 'primary' : 'ghost'" size="sm" title="{{ __('My schools') }}">
                {{ __('My') }} ({{ $myCount }})
            </flux:button>
            @if ($compliance['canViewAll'])
                <flux:button wire:click="$set('filter', 'all')" :variant="$filter === 'all' ? 'primary' : 'ghost'" size="sm" title="{{ __('Schools of submitting directors') }}">
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
    </div>

    {{-- Mobile card layout --}}
    <div class="space-y-2 md:hidden">
        @forelse ($schools as $school)
            <div wire:key="school-card-{{ $school->id }}" class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900">
                <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                    {{ $displayNames[$school->id] }}
                </div>
                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-neutral-500 dark:text-neutral-400">
                    <span>{{ __('Programs') }}: {{ $school->programs_count }}</span>
                    <span>{{ __('Ensembles') }}: {{ $school->ensembles_count }}</span>
                    <span>{{ __('Artists') }}: {{ $artistsCounts[$school->id] ?? 0 }}</span>
                    <span>{{ __('Songs') }}: {{ $songsCounts[$school->id] ?? 0 }}</span>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-neutral-200 px-6 py-12 text-center text-sm text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">
                {{ __('No schools found.') }}
            </div>
        @endforelse
    </div>

    {{-- Desktop table layout --}}
    <div class="hidden overflow-hidden rounded-xl border border-neutral-200 md:block dark:border-neutral-700">
        <table class="min-w-full table-fixed divide-y divide-neutral-200 dark:divide-neutral-700">
            <colgroup>
                <col />
                <col class="w-24" />
                <col class="w-24" />
                <col class="w-36" />
                <col class="w-24" />
            </colgroup>
            <thead class="bg-neutral-50 dark:bg-neutral-800">
                <tr>
                    <th wire:click="sort('school_name')" class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        <div class="flex items-center gap-1">
                            {{ __('School Name') }}
                            @if ($sortBy === 'school_name')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @else
                                <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                            @endif
                        </div>
                    </th>
                    <th wire:click="sort('programs_count')" class="cursor-pointer px-3 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        <div class="flex items-center justify-center gap-1">
                            {{ __('Programs') }}
                            @if ($sortBy === 'programs_count')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @else
                                <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                            @endif
                        </div>
                    </th>
                    <th wire:click="sort('ensembles_count')" class="cursor-pointer px-3 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        <div class="flex items-center justify-center gap-1">
                            {{ __('Ensembles') }}
                            @if ($sortBy === 'ensembles_count')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @else
                                <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                            @endif
                        </div>
                    </th>
                    <th wire:click="sort('artists_count')" class="cursor-pointer px-3 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        <div class="flex items-center justify-center gap-1">
                            {{ __('Composers/Arrangers') }}
                            @if ($sortBy === 'artists_count')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @else
                                <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                            @endif
                        </div>
                    </th>
                    <th wire:click="sort('songs_count')" class="cursor-pointer px-3 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        <div class="flex items-center justify-center gap-1">
                            {{ __('Song Titles') }}
                            @if ($sortBy === 'songs_count')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @else
                                <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                            @endif
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                @forelse ($schools as $school)
                    <tr wire:key="school-{{ $school->id }}">
                        <td class="whitespace-nowrap px-6 py-2 text-sm text-neutral-900 dark:text-neutral-100">
                            {{ $displayNames[$school->id] }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-2 text-center text-sm text-neutral-900 dark:text-neutral-100">
                            {{ $school->programs_count }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-2 text-center text-sm text-neutral-900 dark:text-neutral-100">
                            {{ $school->ensembles_count }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-2 text-center text-sm text-neutral-900 dark:text-neutral-100">
                            {{ $artistsCounts[$school->id] ?? 0 }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-2 text-center text-sm text-neutral-900 dark:text-neutral-100">
                            {{ $songsCounts[$school->id] ?? 0 }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400">
                            {{ __('No schools found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
