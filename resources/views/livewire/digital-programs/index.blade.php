<div class="space-y-6">

    {{-- ── Header ── --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Digital Programs') }}</flux:heading>

        <flux:dropdown>
            <flux:button variant="primary" icon="plus" icon-trailing="chevron-down" size="sm">
                {{ __('Create Digital Program') }}
            </flux:button>
            <flux:menu>
                <flux:menu.item :href="route('digital-programs.create.guided')" icon="map" wire:navigate>
                    {{ __('Guided Wizard') }}
                </flux:menu.item>
                <flux:menu.item :href="route('digital-programs.create.pro')" icon="bolt" wire:navigate>
                    {{ __('Power User Form') }}
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </div>

    {{-- ── Flash message ── --}}
    @if(session('success'))
        <flux:callout icon="check-circle" color="green">
            <flux:callout.text>{{ session('success') }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- ── Empty state ── --}}
    @if($digitalPrograms->isEmpty())
        <div class="rounded-2xl border border-dashed border-zinc-300 px-6 py-16 text-center dark:border-zinc-700">
            <flux:icon.ticket class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg" class="mt-4">{{ __('No digital programs yet') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Create your first digital concert program to share with your audience.') }}</flux:text>
            <div class="mt-6 flex justify-center gap-3">
                <flux:button :href="route('digital-programs.create.guided')" variant="primary" icon="map" wire:navigate>
                    {{ __('Guided Wizard') }}
                </flux:button>
                <flux:button :href="route('digital-programs.create.pro')" variant="subtle" icon="bolt" wire:navigate>
                    {{ __('Power User Form') }}
                </flux:button>
            </div>
        </div>
    @else

        {{-- ── Programs list ── --}}
        <div class="space-y-4">
            @foreach($digitalPrograms as $dp)
                <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
                    wire:key="dp-{{ $dp->id }}">

                    {{-- ── Card top: theme stripe + program info ── --}}
                    <div class="flex items-stretch">

                        {{-- Theme colour stripe --}}
                        @php
                            $swatches = [
                                'WinterConcert' => '#1e293b',
                                'SpringFestival' => '#f0fdf4',
                                'Graduation'    => '#172554',
                                'Holiday'       => '#450a0a',
                                'Formal'        => '#09090b',
                                'Minimalist'    => '#fafaf9',
                            ];
                            $swatch = $swatches[$dp->theme] ?? '#09090b';
                        @endphp
                        <div class="w-2 shrink-0" style="background-color: {{ $swatch }};"></div>

                        {{-- Program details --}}
                        <div class="flex flex-1 flex-col gap-4 p-4 sm:flex-row sm:items-center">

                            {{-- Left: event info --}}
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="truncate font-semibold text-zinc-900 dark:text-zinc-100">
                                        {{ $dp->program?->event_name ?? __('(No Program)') }}
                                    </h3>
                                    @if($dp->is_published)
                                        <flux:badge color="green" size="sm">{{ __('Published') }}</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">{{ __('Draft') }}</flux:badge>
                                    @endif
                                </div>

                                <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    @if($dp->program?->event_date)
                                        <span>{{ $dp->program->event_date->format('M j, Y') }}</span>
                                    @endif
                                    @if($dp->program?->school)
                                        <span>{{ $dp->program->school->school_name }}</span>
                                    @endif
                                    <span>{{ $dp->theme }}</span>
                                </div>

                                {{-- Counts row --}}
                                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-zinc-400 dark:text-zinc-500">
                                    @if(($dp->program->song_titles_count ?? 0) > 0)
                                        <span>
                                            <flux:icon.musical-note class="inline size-3" />
                                            {{ $dp->program->song_titles_count }} {{ __('song(s)') }}
                                        </span>
                                    @endif
                                    @if($dp->rosters_count > 0)
                                        <span>
                                            <flux:icon.users class="inline size-3" />
                                            {{ $dp->rosters_count }} {{ __('student(s)') }}
                                        </span>
                                    @endif
                                    <span class="text-zinc-300 dark:text-zinc-600">
                                        {{ __('Created') }} {{ $dp->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                {{-- Public URL --}}
                                <div class="mt-2 flex items-center gap-2">
                                    <a href="{{ route('program.public', $dp->slug) }}"
                                        target="_blank"
                                        class="font-mono text-xs text-blue-600 hover:underline dark:text-blue-400">
                                        /p/{{ $dp->slug }}
                                    </a>
                                    <button
                                        x-data
                                        x-on:click="
                                            navigator.clipboard.writeText('{{ url('/p/' . $dp->slug) }}');
                                            $el.textContent = '{{ __('Copied!') }}';
                                            setTimeout(() => $el.textContent = '{{ __('Copy') }}', 1500);
                                        "
                                        class="rounded px-1.5 py-0.5 text-xs text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                                        {{ __('Copy') }}
                                    </button>
                                </div>
                            </div>

                            {{-- Right: actions --}}
                            <div class="flex shrink-0 flex-wrap items-center gap-2">

                                {{-- View public page --}}
                                @if($dp->is_published)
                                    <flux:button
                                        href="{{ route('program.public', $dp->slug) }}"
                                        target="_blank"
                                        variant="subtle"
                                        size="sm"
                                        icon="arrow-top-right-on-square">
                                        {{ __('View') }}
                                    </flux:button>
                                @endif

                                {{-- Toggle publish --}}
                                @if($dp->is_published)
                                    <flux:button
                                        wire:click="togglePublish({{ $dp->id }})"
                                        wire:confirm="{{ __('Unpublish this program? The public link will return a 404 until republished.') }}"
                                        variant="ghost"
                                        size="sm"
                                        icon="eye-slash">
                                        {{ __('Unpublish') }}
                                    </flux:button>
                                @else
                                    <flux:button
                                        wire:click="togglePublish({{ $dp->id }})"
                                        variant="primary"
                                        size="sm"
                                        icon="globe-alt">
                                        {{ __('Publish') }}
                                    </flux:button>
                                @endif

                                {{-- Delete --}}
                                <flux:button
                                    wire:click="confirmDelete({{ $dp->id }})"
                                    variant="ghost"
                                    size="sm"
                                    icon="trash"
                                    class="text-red-500 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950/30">
                                </flux:button>

                            </div>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>

    @endif

    {{-- ── Delete confirmation modal ── --}}
    <flux:modal wire:model="showDeleteModal" class="max-w-sm">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Delete Digital Program?') }}</flux:heading>
                <flux:text class="mt-2">{{ __('This will permanently delete the digital program, its roster, and its honor designations. The public link will stop working immediately. This cannot be undone.') }}</flux:text>
            </div>
            <div class="flex justify-end gap-3">
                <flux:button wire:click="cancelDelete" variant="subtle">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="delete" variant="danger">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>

</div>
