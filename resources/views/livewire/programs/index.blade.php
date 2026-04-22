<div>
    <div class="mb-6 space-y-4">
        <flux:heading size="xl">{{ __('Programs') }}</flux:heading>

        {{-- First-Time Orientation Callout --}}
        <div x-data="{ dismissed: localStorage.getItem('programsOrientationDismissed') === 'true' }" x-show="! dismissed" x-collapse>
            <div x-show="! dismissed" x-transition>
                <flux:callout icon="light-bulb" color="sky">
                    <flux:callout.heading>{{ __('Navigating your programs') }}</flux:callout.heading>
                    <flux:callout.text>
                        {{ __('Click a program name to view its ensembles and repertoire. Sort any column by clicking its header. Use the school drop-down to filter by one or more schools. The pencil icon on the far right lets you edit programs you own.') }}
                    </flux:callout.text>
                    <x-slot name="controls">
                        <flux:button icon="x-mark" variant="ghost" x-on:click="dismissed = true; localStorage.setItem('programsOrientationDismissed', 'true')" />
                    </x-slot>
                </flux:callout>
            </div>
        </div>

        <div class="flex items-center justify-start space-x-2">
            <flux:button href="{{ route('addProgram') }}" variant="primary" size="sm" icon="plus">
                {{ __('Add Program') }}
            </flux:button>

            {{-- <div class="flex gap-2">
                <flux:button wire:click="$set('filter', 'my')" :variant="$filter === 'my' ? 'primary' : 'ghost'" size="sm" title="{{ __('My programs') }}">
                    {{ __('My Programs') }}
                </flux:button>
                @if ($compliance['canViewAll'])
                    <flux:button wire:click="$set('filter', 'all')" :variant="$filter === 'all' ? 'primary' : 'ghost'" size="sm" title="{{ __('Programs from participating directors') }}">
                        {{ __('All') }}
                    </flux:button>
                @else
                    <flux:tooltip content="{{ __('Upload programs to unlock community data') }}">
                        <div>
                            <flux:button disabled variant="ghost" size="sm">
                                {{ __('All') }}
                            </flux:button>
                        </div>
                    </flux:tooltip>
                @endif
            </div> --}}

            <flux:dropdown>
                <flux:button icon:trailing="chevron-down" size="sm" class="bg-white dark:bg-neutral-800">
                    {{ $schoolFilterLabel }}
                </flux:button>
                <flux:menu keep-open>
                    <flux:menu.item wire:click="clearSchoolFilter" icon="building-library">{{ __('All Schools') }}</flux:menu.item>
                    <flux:menu.separator />
                    <div class="space-y-1 p-2">
                        <flux:checkbox.group wire:model.live="schoolFilter">
                            @foreach ($schools as $school)
                                <flux:checkbox value="{{ $school->id }}" label="{{ $school->school_name }}" />
                            @endforeach
                        </flux:checkbox.group>
                    </div>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    {{-- Mobile card layout --}}
    <div class="space-y-2 md:hidden">
        @forelse ($programs as $program)
            <div wire:key="program-card-{{ $program->id }}" class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                            <flux:button
                                wire:click="showProgramDetails({{ $program->id }})"
                                variant="filled"
                                size="sm"
                                class="!whitespace-normal text-left max-w-full"
                            >
                                {{ $program->event_name }}
                            </flux:button>
                        </div>
                        <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $displayData[$program->id]['school'] }}
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-3 text-sm text-neutral-500 dark:text-neutral-400">
                            <span>{{ $program->event_date->format('M j, Y') }}</span>
                            <span>{{ $displayData[$program->id]['director'] }}</span>
                        </div>
                    </div>
                    @if ($program->user_id === auth()->id())
                        <flux:button href="{{ route('programs.edit', $program) }}" variant="ghost" size="sm" icon="pencil-square" title="{{ __('Edit Program') }}" />
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-neutral-200 px-6 py-12 text-center dark:border-neutral-700">
                <flux:icon name="document-text" class="mx-auto size-10 text-neutral-300 dark:text-neutral-600 mb-3" />
                <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">{{ __('No programs found') }}</p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mb-4">{{ __('Upload your first concert program to start tracking repertoire trends.') }}</p>
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
                <col class="w-[22%]" />
                <col class="w-[30%]" />
                <col class="w-[15%]" />
                <col class="w-[20%]" />
                <col class="w-[13%]" />
            </colgroup>
            <thead class="bg-neutral-50 dark:bg-neutral-800">
                <tr>
                    <th wire:click="sort('school')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">
                        <div class="flex items-center gap-1">
                            {{ __('School') }}
                            @if ($sortBy === 'school')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @else
                                <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                            @endif
                        </div>
                    </th>
                    <th wire:click="sort('event_name')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">
                        <div class="flex items-center gap-1">
                            <div>
                                {{ __('Program Name') }}
                                <div class="text-[10px] normal-case tracking-normal">{{ __('(click for details)') }}</div>
                            </div>
                            @if ($sortBy === 'event_name')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @else
                                <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                            @endif
                        </div>
                    </th>
                    <th wire:click="sort('event_date')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">
                        <div class="flex items-center gap-1">
                            {{ __('Program Date') }}
                            @if ($sortBy === 'event_date')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @else
                                <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                            @endif
                        </div>
                    </th>
                    <th wire:click="sort('director')" class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">
                        <div class="flex items-center gap-1">
                            {{ __('Director') }}
                            @if ($sortBy === 'director')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @else
                                <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                @forelse ($programs as $program)
                    <tr wire:key="program-{{ $program->id }}">
                        <td class="truncate px-4 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $displayData[$program->id]['school'] }}
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
                        <td class="truncate px-4 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $displayData[$program->id]['director'] }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-2 text-right text-sm">
                            @if ($program->user_id === auth()->id())
                                <flux:button href="{{ route('programs.edit', $program) }}" variant="ghost" size="sm" icon="pencil-square" title="{{ __('Edit Program') }}" />
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center">
                            <div class="mx-auto max-w-sm">
                                <flux:icon name="document-text" class="mx-auto size-10 text-neutral-300 dark:text-neutral-600 mb-3" />
                                <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">{{ __('No programs found') }}</p>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400 mb-4">{{ __('Upload your first concert program to start tracking repertoire trends.') }}</p>
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

    <flux:modal name="program-details" class="max-w-2xl">
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
                        <flux:text>{{ $displayData[$selectedProgram->id]['school'] ?? '' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="font-medium text-neutral-500 dark:text-neutral-400">{{ __('Director') }}</flux:text>
                        <flux:text>{{ $displayData[$selectedProgram->id]['director'] ?? '' }}</flux:text>
                    </div>
                </div>

                @if ($songsByEnsemble->isNotEmpty())
                    <div class="space-y-4">
                        @foreach ($songsByEnsemble as $group)
                            @php
                                $ensembleDirector = $group['songs']->pluck('pivot.ensemble_director')->filter()->first();
                                $showDirector = $ensembleDirector && $ensembleDirector !== $selectedProgram->director_name;
                            @endphp
                            <div>
                                <flux:heading size="sm" class="mb-1">
                                    {{ $group['ensemble']?->ensemble_name ?? __('Other') }}
                                </flux:heading>
                                @if ($showDirector)
                                    <p class="mb-2 pl-1 text-xs text-neutral-500 dark:text-neutral-400">{{ $ensembleDirector }}</p>
                                @endif
                                <ul class="space-y-2 pl-4">
                                    @foreach ($group['songs'] as $songTitle)
                                        <li class="text-sm text-neutral-900 dark:text-neutral-100">
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
                                            @if ($songTitle->pivot->notes)
                                                @php
                                                    $notesPlain = trim(strip_tags($songTitle->pivot->notes));
                                                    $needsTruncation = mb_strlen($notesPlain) > 150;
                                                    $preview = $needsTruncation ? mb_substr($notesPlain, 0, 150).'…' : $notesPlain;
                                                @endphp
                                                <div
                                                    x-data="{ open: false }"
                                                    class="mt-1 rounded border-l-2 border-neutral-200 dark:border-neutral-700 pl-2 text-xs text-neutral-600 dark:text-neutral-400"
                                                >
                                                    <div x-show="! open" class="italic">{{ $preview }}</div>
                                                    <div x-show="open" x-cloak class="prose prose-sm max-w-none dark:prose-invert italic">
                                                        {!! $songTitle->pivot->notes !!}
                                                    </div>
                                                    @if ($needsTruncation)
                                                        <button
                                                            type="button"
                                                            x-on:click="open = ! open"
                                                            class="mt-1 text-xs font-medium text-teal-600 hover:text-teal-500 dark:text-teal-400"
                                                        >
                                                            <span x-show="! open">{{ __('Show more') }}</span>
                                                            <span x-show="open" x-cloak>{{ __('Show less') }}</span>
                                                        </button>
                                                    @endif
                                                </div>
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
</div>
