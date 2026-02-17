<div>
    <div class="mx-auto max-w-5xl space-y-6">
        <flux:heading size="xl">{{ __('Duplicates') }}</flux:heading>

        <flux:text>{{ __('Select two records to merge. The duplicate will be deleted and its references reassigned to the keeper.') }}</flux:text>

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

        <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                {{-- Record to keep --}}
                <div class="space-y-2">
                    <flux:heading size="sm">{{ __('Record to Keep') }}</flux:heading>
                    <select
                        wire:model.live="keeperId"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    >
                        <option value="">{{ __('Select a record...') }}</option>
                        @foreach ($records as $record)
                            <option value="{{ $record->id }}" wire:key="keeper-{{ $record->id }}">
                                @if ($activeTab === 'schools')
                                    {{ $record->school_name }} — {{ $record->postal_code ?: __('No postal code') }}, {{ $record->geo_state ?: __('No state') }}
                                @elseif ($activeTab === 'artists')
                                    {{ $record->artist_name }}
                                @else
                                    {{ $record->song_title }} — {{ $record->composer?->artist_name ?? __('No composer') }}
                                @endif
                                (ID: {{ $record->id }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Record to merge (delete) --}}
                <div class="space-y-2">
                    <flux:heading size="sm">{{ __('Record to Merge (Delete)') }}</flux:heading>
                    <select
                        wire:model.live="duplicateId"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    >
                        <option value="">{{ __('Select a record...') }}</option>
                        @foreach ($records as $record)
                            @if ($record->id !== $keeperId)
                                <option value="{{ $record->id }}" wire:key="duplicate-{{ $record->id }}">
                                    @if ($activeTab === 'schools')
                                        {{ $record->school_name }} — {{ $record->postal_code ?: __('No postal code') }}, {{ $record->geo_state ?: __('No state') }}
                                    @elseif ($activeTab === 'artists')
                                        {{ $record->artist_name }}
                                    @else
                                        {{ $record->song_title }} — {{ $record->composer?->artist_name ?? __('No composer') }}
                                    @endif
                                    (ID: {{ $record->id }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <flux:button
                    wire:click="manualMerge"
                    variant="primary"
                    size="sm"
                    :disabled="!$keeperId || !$duplicateId"
                    wire:confirm="{{ __('Are you sure you want to merge these records? The duplicate will be deleted and this cannot be undone.') }}"
                >
                    {{ __('Merge Selected') }}
                </flux:button>

                @if (!$keeperId || !$duplicateId)
                    <flux:text size="sm" class="text-zinc-400">
                        {{ __('Select both records before merging.') }}
                    </flux:text>
                @endif
            </div>
        </div>
    </div>

        {{-- Artists reference table --}}
        @if ($activeTab === 'artists')
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-2 font-medium text-zinc-500 dark:text-zinc-400">{{ __('ID') }}</th>
                            <th class="px-4 py-2 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Artist Name') }}</th>
                            <th class="px-4 py-2 font-medium text-zinc-500 dark:text-zinc-400">{{ __('First Name') }}</th>
                            <th class="px-4 py-2 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Last Name') }}</th>
                            <th class="px-4 py-2 font-medium text-zinc-500 dark:text-zinc-400">{{ __('Edit') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($records as $record)
                            <tr wire:key="artist-row-{{ $record->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-2 text-zinc-500 dark:text-zinc-400">{{ $record->id }}</td>
                                <td class="px-4 py-2 text-zinc-900 dark:text-zinc-100">{{ $record->artist_name }}</td>
                                <td class="px-4 py-2 text-zinc-700 dark:text-zinc-300">{{ $record->artist_first_name }}</td>
                                <td class="px-4 py-2 text-zinc-700 dark:text-zinc-300">{{ $record->artist_last_name }}</td>
                                <td class="px-4 py-2">
                                    <flux:button wire:click="editArtist({{ $record->id }})" variant="ghost" size="sm">
                                        <flux:icon name="pencil-square" class="size-4" />
                                    </flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <flux:modal name="edit-artist" class="md:w-96">
                <form wire:submit="updateArtist" class="space-y-6">
                    <div>
                        <flux:heading size="lg">{{ __('Edit Artist') }}</flux:heading>
                        <flux:text class="mt-2">{{ __('Update the artist details.') }}</flux:text>
                    </div>

                    <flux:input wire:model="editArtistName" label="{{ __('Artist Name') }}" required />
                    <flux:input wire:model="editArtistFirstName" label="{{ __('First Name') }}" />
                    <flux:input wire:model="editArtistLastName" label="{{ __('Last Name') }}" />

                    <div class="flex gap-2">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
                    </div>
                </form>
            </flux:modal>
        @endif
    </div>
</div>
