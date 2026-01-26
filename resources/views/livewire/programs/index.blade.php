<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Programs') }}</flux:heading>

        <div class="flex items-center gap-4">
            <flux:button href="{{ route('addProgram') }}" variant="primary" size="sm" icon="plus">
                {{ __('Add Program') }}
            </flux:button>

            <div class="flex gap-2">
                <flux:button wire:click="$set('filter', 'my')" :variant="$filter === 'my' ? 'primary' : 'ghost'" size="sm">
                    {{ __('My') }}
                </flux:button>
                <flux:button wire:click="$set('filter', 'all')" :variant="$filter === 'all' ? 'primary' : 'ghost'" size="sm">
                    {{ __('All') }}
                </flux:button>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
            <thead class="bg-neutral-50 dark:bg-neutral-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('Program Name') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('Program Date') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('School') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('Director') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                @forelse ($programs as $program)
                    <tr wire:key="program-{{ $program->id }}">
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-neutral-900 dark:text-neutral-100">
                            <flux:button
                                wire:click="showProgramDetails({{ $program->id }})"
                                variant="filled"
                                size="sm"
                            >
                                {{ $program->event_name }}
                            </flux:button>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $program->event_date->format('M j, Y') }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $displayData[$program->id]['school'] }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $displayData[$program->id]['director'] }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400">
                            {{ __('No programs found.') }}
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
                            <div>
                                <flux:heading size="sm" class="mb-2">
                                    {{ $group['ensemble']?->ensemble_name ?? __('Other') }}
                                </flux:heading>
                                <ul class="space-y-1 pl-4">
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
