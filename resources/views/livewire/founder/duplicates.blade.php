<div>
    <div class="mx-auto max-w-5xl space-y-6">
        <flux:heading size="xl">{{ __('Duplicates') }}</flux:heading>

        <flux:text>{{ __('Find and merge duplicate records across schools, composers/arrangers, and songs.') }}</flux:text>

        {{-- Tab buttons --}}
        <div class="flex gap-2">
            <flux:button
                wire:click="switchTab('schools')"
                :variant="$activeTab === 'schools' ? 'primary' : 'ghost'"
                size="sm"
            >
                {{ __('Schools') }}
            </flux:button>
            <flux:button
                wire:click="switchTab('artists')"
                :variant="$activeTab === 'artists' ? 'primary' : 'ghost'"
                size="sm"
            >
                {{ __('Composers/Arrangers') }}
            </flux:button>
            <flux:button
                wire:click="switchTab('song-titles')"
                :variant="$activeTab === 'song-titles' ? 'primary' : 'ghost'"
                size="sm"
            >
                {{ __('Songs') }}
            </flux:button>
        </div>

        @if ($successMessage)
            <flux:callout variant="success">
                {{ $successMessage }}
            </flux:callout>
        @endif

        @if ($duplicateGroups->isEmpty())
            <flux:callout>
                {{ __('No duplicates found.') }}
            </flux:callout>
        @else
            <div class="space-y-6">
                @foreach ($duplicateGroups as $groupKey => $group)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700" wire:key="group-{{ $groupKey }}">
                        <div class="border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800">
                            <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                                {{ $group->first()->{$activeTab === 'song-titles' ? 'song_title' : ($activeTab === 'artists' ? 'artist_name' : 'school_name')} }}
                                <flux:badge size="sm" class="ml-2">{{ $group->count() }} {{ __('records') }}</flux:badge>
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-200 text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                        <th class="px-4 py-2">{{ __('Keep') }}</th>
                                        <th class="px-4 py-2">{{ __('ID') }}</th>
                                        @if ($activeTab === 'schools')
                                            <th class="px-4 py-2">{{ __('School Name') }}</th>
                                            <th class="px-4 py-2">{{ __('Abbreviation') }}</th>
                                            <th class="px-4 py-2">{{ __('Postal Code') }}</th>
                                            <th class="px-4 py-2">{{ __('State') }}</th>
                                            <th class="px-4 py-2">{{ __('Country') }}</th>
                                        @elseif ($activeTab === 'artists')
                                            <th class="px-4 py-2">{{ __('Artist Name') }}</th>
                                            <th class="px-4 py-2">{{ __('First Name') }}</th>
                                            <th class="px-4 py-2">{{ __('Last Name') }}</th>
                                        @else
                                            <th class="px-4 py-2">{{ __('Song Title') }}</th>
                                            <th class="px-4 py-2">{{ __('Composer') }}</th>
                                            <th class="px-4 py-2">{{ __('Arranger') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($group as $record)
                                        <tr class="border-b border-zinc-100 dark:border-zinc-700/50" wire:key="record-{{ $record->id }}">
                                            <td class="px-4 py-2">
                                                <input
                                                    type="radio"
                                                    name="keeper_{{ $groupKey }}"
                                                    value="{{ $record->id }}"
                                                    wire:model="selectedKeepers.{{ $groupKey }}"
                                                    class="text-blue-600"
                                                />
                                            </td>
                                            <td class="px-4 py-2 text-zinc-500 dark:text-zinc-400">{{ $record->id }}</td>
                                            @if ($activeTab === 'schools')
                                                <td class="px-4 py-2">{{ $record->school_name }}</td>
                                                <td class="px-4 py-2">{{ $record->abbreviation }}</td>
                                                <td class="px-4 py-2">{{ $record->postal_code }}</td>
                                                <td class="px-4 py-2">{{ $record->geo_state }}</td>
                                                <td class="px-4 py-2">{{ $record->country }}</td>
                                            @elseif ($activeTab === 'artists')
                                                <td class="px-4 py-2">{{ $record->artist_name }}</td>
                                                <td class="px-4 py-2">{{ $record->artist_first_name }}</td>
                                                <td class="px-4 py-2">{{ $record->artist_last_name }}</td>
                                            @else
                                                <td class="px-4 py-2">{{ $record->song_title }}</td>
                                                <td class="px-4 py-2">{{ $record->composer?->artist_name ?? '—' }}</td>
                                                <td class="px-4 py-2">{{ $record->arranger?->artist_name ?? '—' }}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center gap-3 px-4 py-3">
                            @php
                                $keeperId = $selectedKeepers[$groupKey] ?? null;
                                $duplicateIds = $keeperId
                                    ? $group->pluck('id')->reject(fn ($id) => $id == $keeperId)->implode(',')
                                    : '';
                            @endphp

                            @if ($activeTab === 'schools')
                                <flux:button
                                    wire:click="mergeSchools({{ $keeperId ?? 0 }}, '{{ $duplicateIds }}')"
                                    variant="primary"
                                    size="sm"
                                    :disabled="!$keeperId"
                                    wire:confirm="{{ __('Are you sure you want to merge these schools? This cannot be undone.') }}"
                                >
                                    {{ __('Merge Selected') }}
                                </flux:button>
                            @elseif ($activeTab === 'artists')
                                <flux:button
                                    wire:click="mergeArtists({{ $keeperId ?? 0 }}, '{{ $duplicateIds }}')"
                                    variant="primary"
                                    size="sm"
                                    :disabled="!$keeperId"
                                    wire:confirm="{{ __('Are you sure you want to merge these artists? This cannot be undone.') }}"
                                >
                                    {{ __('Merge Selected') }}
                                </flux:button>
                            @else
                                <flux:button
                                    wire:click="mergeSongTitles({{ $keeperId ?? 0 }}, '{{ $duplicateIds }}')"
                                    variant="primary"
                                    size="sm"
                                    :disabled="!$keeperId"
                                    wire:confirm="{{ __('Are you sure you want to merge these song titles? This cannot be undone.') }}"
                                >
                                    {{ __('Merge Selected') }}
                                </flux:button>
                            @endif

                            @if (!$keeperId)
                                <flux:text size="sm" class="text-zinc-400">
                                    {{ __('Select a record to keep before merging.') }}
                                </flux:text>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
