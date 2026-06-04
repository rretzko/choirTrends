<div>
    {{-- ── Sticky action bar ── --}}
    <div class="sticky top-0 z-20 border-b border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ $program?->event_name ?? __('Digital Program') }}
                </p>
                @if($program?->event_date)
                    <p class="text-xs text-zinc-400">{{ $program->event_date->format('F j, Y') }}</p>
                @endif
            </div>
            <div class="flex shrink-0 gap-2">
                <flux:button wire:click="save(false)" variant="subtle" size="sm" icon="arrow-down-tray">
                    {{ __('Save Draft') }}
                </flux:button>
                <flux:button wire:click="save(true)" variant="primary" size="sm" icon="globe-alt">
                    {{ __('Save & Publish') }}
                </flux:button>
            </div>
        </div>
    </div>

    {{-- ── Flash message ── --}}
    @if(session('success'))
        <div class="mx-auto max-w-6xl px-4 pt-4">
            <flux:callout variant="success" icon="check-circle">
                <flux:callout.text>{{ session('success') }}</flux:callout.text>
            </flux:callout>
        </div>
    @endif

    {{-- ── Section jump nav ── --}}
    <div class="mx-auto max-w-6xl px-4 pt-4">
        <div class="flex flex-wrap gap-2 text-xs">
            @foreach([['style', __('Style')], ['content', __('Content')], ['songs', __('Songs')], ['roster', __('Roster')]] as [$id, $label])
                <a href="#{{ $id }}"
                    class="rounded-full border border-zinc-200 px-3 py-1 text-zinc-600 transition hover:border-zinc-400 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-100">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- ── Main grid ── --}}
    <div class="mx-auto max-w-6xl gap-8 px-4 py-6 lg:grid lg:grid-cols-3">

        {{-- ── Left: form sections ── --}}
        <div class="space-y-10 lg:col-span-2">

            {{-- ═══════════ STYLE ═══════════ --}}
            <section id="style">
                <flux:heading size="lg" class="mb-1">{{ __('Style') }}</flux:heading>
                <flux:text class="mb-5 text-sm">{{ __('Choose a theme and print layout for this program.') }}</flux:text>

                {{-- Theme picker --}}
                <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach($themes as $key => $theme)
                        <button type="button"
                            wire:click="$set('theme', '{{ $key }}')"
                            class="relative rounded-xl border-2 p-3 text-left transition
                                @if($this->theme === $key) border-blue-500 ring-2 ring-blue-200 dark:ring-blue-900 @else border-zinc-200 hover:border-zinc-400 dark:border-zinc-700 @endif">
                            <div class="mb-2 h-6 w-full rounded"
                                style="background: {{ $theme['swatch'] }}; border: 1px solid rgba(0,0,0,0.08);"></div>
                            <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">{{ $theme['label'] }}</p>
                            <p class="mt-0.5 text-xs text-zinc-400">{{ $theme['description'] }}</p>
                            @if($this->theme === $key)
                                <flux:icon.check-circle class="absolute right-2 top-2 size-4 text-blue-500" />
                            @endif
                        </button>
                    @endforeach
                </div>

                {{-- Print orientation --}}
                <flux:field>
                    <flux:label>{{ __('Print Orientation') }}</flux:label>
                    <div class="flex gap-3">
                        @foreach(['Portrait', 'Landscape'] as $orientation)
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2 transition
                                @if($printOrientation === $orientation) border-blue-500 bg-blue-50 dark:bg-blue-950/30 @else border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 @endif">
                                <input type="radio" wire:model.live="printOrientation"
                                    value="{{ $orientation }}" class="sr-only">
                                <span class="text-sm font-medium">{{ $orientation }}</span>
                            </label>
                        @endforeach
                    </div>
                </flux:field>
            </section>

            {{-- ═══════════ CONTENT ═══════════ --}}
            <section id="content">
                <flux:heading size="lg" class="mb-1">{{ __('Program Content') }}</flux:heading>
                <flux:text class="mb-5 text-sm">{{ __('Optional text sections that appear on the public program.') }}</flux:text>

                <div class="space-y-5">
                    <flux:field>
                        <flux:label>{{ __("Director's Welcome Message") }}</flux:label>
                        <flux:textarea wire:model="welcomeMessage" rows="4"
                            placeholder="{{ __('A personal note from the director…') }}" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Acknowledgments') }}</flux:label>
                        <flux:textarea wire:model="acknowledgments" rows="3"
                            placeholder="{{ __('Thank-you notes, credits…') }}" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Sponsors / Patrons') }}</flux:label>
                        <flux:textarea wire:model="sponsorText" rows="2"
                            placeholder="{{ __('Sponsor recognition text…') }}" />
                    </flux:field>

                    @if($ensembles->isNotEmpty())
                        <flux:field>
                            <flux:label>{{ __('Intermission After') }}</flux:label>
                            <flux:text class="text-xs text-zinc-400">{{ __('Insert an intermission banner after this ensemble.') }}</flux:text>
                            <select wire:model="intermissionAfterEnsemble"
                                class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                <option value="">{{ __('No intermission') }}</option>
                                @foreach($ensembles as $ei => $ensemble)
                                    <option value="{{ $ei + 1 }}">{{ $ensemble->ensemble_name }}</option>
                                @endforeach
                            </select>
                        </flux:field>
                    @endif
                </div>
            </section>

            {{-- ═══════════ SONGS ═══════════ --}}
            <section id="songs">
                <flux:heading size="lg" class="mb-1">{{ __('Song Settings') }}</flux:heading>
                <flux:text class="mb-5 text-sm">{{ __('Choose which songs show lyrics on the public program.') }}</flux:text>

                @if(empty($songSettings))
                    <flux:callout variant="warning" icon="information-circle">
                        <flux:callout.text>{{ __('No songs found. Songs are added via the Edit Program page.') }}</flux:callout.text>
                    </flux:callout>
                @else
                    <div class="space-y-3">
                        @foreach($songSettings as $si => $setting)
                            <div class="flex items-center justify-between rounded-xl border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                                <div>
                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $setting['title'] }}</p>
                                    @if($setting['composer'])
                                        <p class="text-xs text-zinc-400">{{ $setting['composer'] }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3">
                                    @if($setting['hasLyrics'])
                                        <flux:switch
                                            wire:model.live="songSettings.{{ $si }}.showLyrics"
                                            label="{{ __('Show lyrics') }}" />
                                    @else
                                        <span class="text-xs text-zinc-400">{{ __('No lyrics on file') }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($this->anyLyricsEnabled())
                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/30">
                            <div class="flex items-start gap-3">
                                <input type="checkbox" wire:model.live="lyricsCopyrightAcknowledged"
                                    id="lyrics_ack_edit"
                                    class="mt-0.5 size-4 rounded border-zinc-300 text-blue-600">
                                <label for="lyrics_ack_edit" class="text-sm text-amber-900 dark:text-amber-200">
                                    {{ __('I confirm that I have the rights or a valid license to display these lyrics publicly on this program.') }}
                                </label>
                            </div>
                            @error('lyricsCopyrightAcknowledged')
                                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                @endif
            </section>

            {{-- ═══════════ ROSTER ═══════════ --}}
            <section id="roster">
                <flux:heading size="lg" class="mb-1">{{ __('Roster') }}</flux:heading>
                <flux:text class="mb-5 text-sm">{{ __('Manage student names and honor designations per ensemble.') }}</flux:text>

                @if($ensembles->isEmpty() && !isset($rosters['general']))
                    <flux:callout variant="warning" icon="information-circle">
                        <flux:callout.text>{{ __('No ensembles found. Ensembles are created when you add songs via the Edit Program page.') }}</flux:callout.text>
                    </flux:callout>
                @else
                    @if($this->hasAnyStudents())
                        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/30">
                            <div class="flex items-start gap-3">
                                <input type="checkbox" wire:model.live="studentNamesAcknowledged"
                                    id="names_ack_edit"
                                    class="mt-0.5 size-4 rounded border-zinc-300 text-blue-600">
                                <label for="names_ack_edit" class="text-sm text-amber-900 dark:text-amber-200">
                                    {{ __('I acknowledge that the names I enter will be publicly visible. I will not enter any personally identifying information beyond student names.') }}
                                </label>
                            </div>
                            @error('studentNamesAcknowledged')
                                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div class="space-y-6">
                        @foreach($ensembles as $ensemble)
                            @include('livewire.digital-programs.wizard.ensemble-section', [
                                'ensembleKey'  => (string) $ensemble->id,
                                'ensembleName' => $ensemble->ensemble_name,
                            ])
                        @endforeach

                        @if(isset($rosters['general']) || isset($honors['general']))
                            @include('livewire.digital-programs.wizard.ensemble-section', [
                                'ensembleKey'  => 'general',
                                'ensembleName' => __('General / Unassigned'),
                            ])
                        @endif
                    </div>
                @endif
            </section>

        </div>

        {{-- ── Right: sidebar ── --}}
        <div class="hidden lg:block">
            <div class="sticky top-20 space-y-4">

                {{-- Program info --}}
                @if($program)
                    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <flux:heading size="sm" class="mb-3">{{ __('Program') }}</flux:heading>
                        <div class="space-y-1 text-sm">
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $program->event_name }}</p>
                            @if($program->event_date)
                                <p class="text-zinc-500">{{ $program->event_date->format('F j, Y') }}</p>
                            @endif
                            @if($program->director_name)
                                <p class="text-zinc-500">{{ $program->director_name }}</p>
                            @endif
                            @if($program->school)
                                <p class="text-zinc-400 text-xs">{{ $program->school->school_name }}</p>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Theme preview --}}
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:heading size="sm" class="mb-3">{{ __('Current Theme') }}</flux:heading>
                    @if(isset($themes[$this->theme]))
                        <div class="flex items-center gap-3">
                            <div class="size-8 shrink-0 rounded-lg border border-zinc-200"
                                style="background: {{ $themes[$this->theme]['swatch'] }};"></div>
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $themes[$this->theme]['label'] }}</p>
                                <p class="text-xs text-zinc-400">{{ $themes[$this->theme]['description'] }}</p>
                            </div>
                        </div>
                    @endif
                    <p class="mt-3 text-xs text-zinc-400">{{ $printOrientation }} {{ __('orientation') }}</p>
                </div>

                {{-- Public link + QR --}}
                @if($dp)
                    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <flux:heading size="sm" class="mb-2">{{ __('Public Web Address') }}</flux:heading>
                        <p class="break-all font-mono text-xs text-zinc-500 dark:text-zinc-400">/p/{{ $dp->slug }}</p>
                        @if($dp->is_published)
                            <div class="mt-3 flex flex-col gap-2">
                                <flux:button
                                    href="{{ route('program.public', $dp->slug) }}"
                                    target="_blank"
                                    variant="subtle"
                                    size="sm"
                                    icon="arrow-top-right-on-square"
                                    class="w-full">
                                    {{ __('View Live Program') }}
                                </flux:button>
                                <flux:button
                                    href="{{ route('program.qr', $dp->slug) }}"
                                    target="_blank"
                                    variant="subtle"
                                    size="sm"
                                    class="w-full">
                                    {{ __('Large QR Code') }}
                                </flux:button>
                            </div>
                            {{-- QR preview --}}
                            <div class="mt-3 flex justify-center rounded-lg bg-white p-2">
                                <img src="{{ route('program.qr', ['slug' => $dp->slug, 'size' => 120]) }}"
                                    alt="{{ __('QR Code') }}"
                                    width="120" height="120"
                                    class="block">
                            </div>
                        @else
                            <flux:badge color="zinc" size="sm" class="mt-2">{{ __('Draft — not published') }}</flux:badge>
                        @endif
                    </div>
                @endif

            </div>
        </div>

    </div>
</div>
