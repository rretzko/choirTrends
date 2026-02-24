<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Program') }}</flux:heading>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Program-level fields --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <flux:field>
                <flux:label>{{ __('Event Name') }}</flux:label>
                <flux:input wire:model="eventName" />
                <flux:error name="eventName" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Event Date') }}</flux:label>
                <flux:input wire:model="eventDate" type="date" />
                <flux:error name="eventDate" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Director Name') }}</flux:label>
                <flux:input wire:model="directorName" />
                <flux:error name="directorName" />
            </flux:field>

            <flux:field>
                <flux:label class="!flex w-full justify-between">
                    <span>{{ __('School Name') }}</span>
                    @unless ($schoolEditable)
                        <span class="mr-2 inline-flex items-center gap-1 text-amber-600 dark:text-amber-400"><flux:icon name="lock-closed" class="size-3" /> {{ __('Shared') }}</span>
                    @endunless
                </flux:label>
                <flux:input wire:model="schoolName" :disabled="! $schoolEditable" />
                <flux:error name="schoolName" />
            </flux:field>
        </div>

        {{-- Ensembles --}}
        @foreach ($ensembles as $eIndex => $ensemble)
            <div wire:key="ensemble-{{ $eIndex }}" class="rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div class="grow">
                        <flux:field>
                            <flux:label class="!flex w-full justify-between">
                                <span>{{ __('Ensemble Name') }}</span>
                                @unless ($ensemble['editable'])
                                    <span class="mr-2 inline-flex items-center gap-1 text-amber-600 dark:text-amber-400"><flux:icon name="lock-closed" class="size-3" /> {{ __('Shared') }}</span>
                                @endunless
                            </flux:label>
                            <flux:input wire:model="ensembles.{{ $eIndex }}.name" :disabled="! $ensemble['editable']" />
                        </flux:field>
                    </div>

                    <flux:button wire:click="removeEnsemble({{ $eIndex }})" type="button" variant="danger" size="sm" icon="trash">
                        {{ __('Remove') }}
                    </flux:button>
                </div>

                {{-- Songs --}}
                @foreach ($ensemble['songs'] as $sIndex => $song)
                    <div wire:key="song-{{ $eIndex }}-{{ $sIndex }}" class="mb-4 ml-4 flex items-start gap-2 rounded border border-neutral-100 p-3 dark:border-neutral-800">
                        <flux:field class="w-16 shrink-0">
                            <flux:label>{{ __('Order') }}</flux:label>
                            <flux:input wire:model="ensembles.{{ $eIndex }}.songs.{{ $sIndex }}.sortOrder" type="number" min="1" />
                        </flux:field>

                        <div class="grid grow grid-cols-1 gap-4 sm:grid-cols-3">
                            <flux:field>
                                <flux:label class="!flex w-full justify-between">
                                    <span>{{ __('Song Title') }}</span>
                                    @unless ($song['titleEditable'])
                                        <span class="mr-2 inline-flex items-center gap-1 text-amber-600 dark:text-amber-400"><flux:icon name="lock-closed" class="size-3" /> {{ __('Shared') }}</span>
                                    @endunless
                                </flux:label>
                                <flux:input wire:model="ensembles.{{ $eIndex }}.songs.{{ $sIndex }}.title" :disabled="! $song['titleEditable']" />
                                <flux:error name="ensembles.{{ $eIndex }}.songs.{{ $sIndex }}.title" />
                            </flux:field>

                            <flux:field>
                                <flux:label class="!flex w-full justify-between">
                                    <span>{{ __('Composer') }}</span>
                                    @unless ($song['composerEditable'])
                                        <span class="mr-2 inline-flex items-center gap-1 text-amber-600 dark:text-amber-400"><flux:icon name="lock-closed" class="size-3" /> {{ __('Shared') }}</span>
                                    @endunless
                                </flux:label>
                                <flux:input wire:model="ensembles.{{ $eIndex }}.songs.{{ $sIndex }}.composer" :disabled="! $song['composerEditable']" />
                            </flux:field>

                            <flux:field>
                                <flux:label class="!flex w-full justify-between">
                                    <span>{{ __('Arranger') }}</span>
                                    @unless ($song['arrangerEditable'])
                                        <span class="mr-2 inline-flex items-center gap-1 text-amber-600 dark:text-amber-400"><flux:icon name="lock-closed" class="size-3" /> {{ __('Shared') }}</span>
                                    @endunless
                                </flux:label>
                                <flux:input wire:model="ensembles.{{ $eIndex }}.songs.{{ $sIndex }}.arranger" :disabled="! $song['arrangerEditable']" />
                            </flux:field>
                        </div>

                        <flux:button wire:click="removeSong({{ $eIndex }}, {{ $sIndex }})" type="button" variant="ghost" size="sm" icon="trash" title="{{ __('Remove Song') }}" class="mt-6 shrink-0" />
                    </div>
                @endforeach

                <flux:button wire:click="addSong({{ $eIndex }})" type="button" variant="ghost" size="sm" icon="plus">
                    {{ __('Add Song') }}
                </flux:button>
            </div>
        @endforeach

        <div>
            <flux:button wire:click="addEnsemble" type="button" variant="ghost" size="sm" icon="plus">
                {{ __('Add Ensemble') }}
            </flux:button>
        </div>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
            <flux:button href="{{ route('programs.index') }}" variant="ghost">{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</div>
