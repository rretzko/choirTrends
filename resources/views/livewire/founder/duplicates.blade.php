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
</div>
