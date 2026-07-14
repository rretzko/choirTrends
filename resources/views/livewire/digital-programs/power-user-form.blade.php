<div>
    {{-- ── Sticky action bar ── --}}
    <div class="sticky top-0 z-20 border-b border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <div class="flex items-center gap-2">
                <flux:icon.bolt class="size-5 shrink-0 text-amber-500" />
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Power User: Digital Program') }}</span>
            </div>
            @if($programLoaded)
                <div class="flex items-center gap-3">
                    @if($errors->hasAny(['studentNamesAcknowledged', 'lyricsCopyrightAcknowledged']))
                        <a href="#{{ $errors->has('studentNamesAcknowledged') ? 'section-roster' : 'section-songs' }}"
                            class="flex items-center gap-1 text-xs font-medium text-red-600 dark:text-red-400">
                            <flux:icon.exclamation-circle class="size-4 shrink-0" />
                            {{ __('Acknowledgment required ↓') }}
                        </a>
                    @endif
                    <flux:button wire:click="save(false)" variant="subtle" size="sm">{{ __('Save Draft') }}</flux:button>
                    @unless(auth()->user()->isAssistant())
                        <flux:button wire:click="save(true)" variant="primary" size="sm" icon="check">{{ __('Publish') }}</flux:button>
                    @endunless
                </div>
            @endif
        </div>

        {{-- Section jump links (desktop) --}}
        @if($programLoaded)
            <div class="hidden border-t border-zinc-100 dark:border-zinc-800 lg:block">
                <div class="mx-auto flex max-w-6xl gap-6 overflow-x-auto px-4 py-1.5 text-xs font-medium text-zinc-500 dark:text-zinc-400">
                    <a href="#section-program" class="shrink-0 hover:text-zinc-900 dark:hover:text-zinc-100">{{ __('Program') }}</a>
                    <a href="#section-style" class="shrink-0 hover:text-zinc-900 dark:hover:text-zinc-100">{{ __('Style') }}</a>
                    <a href="#section-content" class="shrink-0 hover:text-zinc-900 dark:hover:text-zinc-100">{{ __('Content') }}</a>
                    <a href="#section-songs" class="shrink-0 hover:text-zinc-900 dark:hover:text-zinc-100">{{ __('Songs') }}</a>
                    <a href="#section-roster" class="shrink-0 hover:text-zinc-900 dark:hover:text-zinc-100">{{ __('Roster & Honors') }}</a>
                </div>
            </div>
        @endif
    </div>

    {{-- ── Main layout ── --}}
    <div class="mx-auto max-w-6xl px-4 py-6">
        <div class="flex gap-6">

            {{-- ── Form (left column) ── --}}
            <div class="min-w-0 flex-1 space-y-6">

                {{-- ── SECTION 1: Program ── --}}
                <section id="section-program" class="scroll-mt-28 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-5">
                        <flux:heading size="lg">{{ __('1. Program') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('Choose which program this digital publication is for.') }}</flux:text>
                    </div>

                    {{-- Choice toggle --}}
                    <div class="mb-5 grid gap-3 sm:grid-cols-2">
                        <button type="button" wire:click="$set('startChoice', 'existing')"
                            class="flex items-center gap-3 rounded-xl border-2 p-4 text-left transition
                                {{ $startChoice === 'existing' ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700' }}">
                            <flux:icon.document-text class="size-6 shrink-0 text-blue-500" />
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Existing Program') }}</div>
                                <div class="text-xs text-zinc-500">{{ __('Pick from your ChoirTrends programs') }}</div>
                            </div>
                        </button>

                        <button type="button" wire:click="$set('startChoice', 'new')"
                            class="flex items-center gap-3 rounded-xl border-2 p-4 text-left transition
                                {{ $startChoice === 'new' ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700' }}">
                            <flux:icon.plus-circle class="size-6 shrink-0 text-emerald-500" />
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('New Program') }}</div>
                                <div class="text-xs text-zinc-500">{{ __('Enter event details now') }}</div>
                            </div>
                        </button>
                    </div>

                    {{-- Existing selector --}}
                    @if($startChoice === 'existing')
                        <flux:field class="mb-4">
                            <flux:label>{{ __('Program') }}</flux:label>
                            <select wire:model="selectedProgramId"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                <option value="">{{ __('— Choose a program —') }}</option>
                                @foreach($userPrograms as $prog)
                                    <option value="{{ $prog->id }}">
                                        {{ $prog->event_name }} — {{ $prog->event_date->format('M j, Y') }}
                                        @if($prog->school) ({{ $prog->school->school_name }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            <flux:error name="selectedProgramId" />
                        </flux:field>
                    @endif

                    {{-- New program fields --}}
                    @if($startChoice === 'new')
                        <div class="mb-4 grid gap-4 sm:grid-cols-2">
                            <flux:field class="sm:col-span-2">
                                <flux:label>{{ __('Event Name') }}</flux:label>
                                <flux:input wire:model="newEventName" placeholder="{{ __('e.g. Spring Choral Concert') }}" />
                                <flux:error name="newEventName" />
                            </flux:field>

                            <flux:field>
                                <flux:label>{{ __('Event Date') }}</flux:label>
                                <flux:input wire:model="newEventDate" type="date" />
                                <flux:error name="newEventDate" />
                            </flux:field>

                            <flux:field>
                                <flux:label>{{ __('Director Name') }}</flux:label>
                                <flux:input wire:model="newDirectorName" placeholder="{{ __('e.g. Jane Smith') }}" />
                                <flux:error name="newDirectorName" />
                            </flux:field>

                            <flux:field class="sm:col-span-2">
                                <flux:label>{{ __('School/Org Name') }}</flux:label>
                                <flux:input wire:model="newSchoolName" placeholder="{{ __('e.g. Lincoln High School') }}" />
                                <flux:error name="newSchoolName" />
                            </flux:field>
                        </div>
                    @endif

                    <flux:error name="startChoice" />

                    @if($startChoice && !$programLoaded)
                        <flux:button wire:click="loadProgram" variant="primary">
                            {{ __('Load Program →') }}
                        </flux:button>
                    @elseif($programLoaded && $resolvedProgram)
                        <div class="flex items-center gap-2 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700 dark:bg-green-950/30 dark:text-green-400">
                            <flux:icon.check-circle class="size-4 shrink-0" />
                            {{ __('Program loaded:') }} <strong>{{ $resolvedProgram->event_name }}</strong>
                        </div>
                    @elseif($programLoaded && $startChoice === 'new')
                        <div class="flex items-center gap-2 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700 dark:bg-green-950/30 dark:text-green-400">
                            <flux:icon.check-circle class="size-4 shrink-0" />
                            {{ __('New program details ready. Fill in the sections below and Save when done.') }}
                        </div>
                    @endif
                </section>

                {{-- ── Locked overlay until program loaded ── --}}
                @if(!$programLoaded)
                    <div class="rounded-2xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
                        <flux:icon.lock-closed class="mx-auto size-10 text-zinc-300 dark:text-zinc-600" />
                        <flux:text class="mt-3 text-zinc-400">{{ __('Load a program above to unlock the remaining sections.') }}</flux:text>
                    </div>
                @else

                    {{-- ── SECTION 2: Style ── --}}
                    <section id="section-style" class="scroll-mt-28 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="mb-5">
                            <flux:heading size="lg">{{ __('2. Style') }}</flux:heading>
                            <flux:text class="mt-1">{{ __('Choose a theme and set print orientation.') }}</flux:text>
                        </div>

                        {{-- Theme picker --}}
                        <flux:field class="mb-6">
                            <flux:label>{{ __('Theme') }}</flux:label>
                            <div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                @foreach($themes as $themeKey => $themeData)
                                    <button type="button" wire:click="$set('theme', '{{ $themeKey }}')"
                                        class="overflow-hidden rounded-xl border-2 text-left transition
                                            {{ $theme === $themeKey ? 'border-blue-500 ring-2 ring-blue-200 dark:ring-blue-900' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700' }}">
                                        <div class="h-14 w-full" style="background-color: {{ $themeData['swatch'] }}"></div>
                                        <div class="p-3">
                                            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $themeData['label'] }}</div>
                                            <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $themeData['description'] }}</div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                            <flux:error name="theme" />
                        </flux:field>

                        {{-- Print orientation --}}
                        <flux:field>
                            <flux:label>{{ __('Print Orientation') }}</flux:label>
                            <div class="mt-2 flex flex-wrap gap-4">
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border-2 px-5 py-4 transition
                                    {{ $printOrientation === 'Portrait' ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30' : 'border-zinc-200 dark:border-zinc-700' }}">
                                    <input type="radio" wire:model="printOrientation" value="Portrait" class="sr-only">
                                    <div class="flex h-12 w-9 items-center justify-center rounded border-2 border-zinc-400 bg-white text-xs dark:border-zinc-500 dark:bg-zinc-800">P</div>
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Portrait') }}</div>
                                        <div class="text-xs text-zinc-500">8.5 × 11"</div>
                                    </div>
                                </label>
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border-2 px-5 py-4 transition
                                    {{ $printOrientation === 'Landscape' ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30' : 'border-zinc-200 dark:border-zinc-700' }}">
                                    <input type="radio" wire:model="printOrientation" value="Landscape" class="sr-only">
                                    <div class="flex h-9 w-12 items-center justify-center rounded border-2 border-zinc-400 bg-white text-xs dark:border-zinc-500 dark:bg-zinc-800">L</div>
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('Landscape') }}</div>
                                        <div class="text-xs text-zinc-500">11 × 8.5"</div>
                                    </div>
                                </label>
                            </div>
                        </flux:field>
                    </section>

                    {{-- ── SECTION 3: Content ── --}}
                    <section id="section-content" class="scroll-mt-28 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="mb-5">
                            <flux:heading size="lg">{{ __('3. Program Content') }}</flux:heading>
                            <flux:text class="mt-1">{{ __('All fields optional — add what you want to appear in the program.') }}</flux:text>
                        </div>

                        <div class="space-y-5">
                            <flux:field>
                                <flux:label>{{ __("Director's Welcome Message") }}</flux:label>
                                <flux:editor wire:model="welcomeMessage"
                                    placeholder="{{ __('A welcome or note to the audience from the director...') }}" />
                            </flux:field>

                            <flux:field>
                                <flux:label>{{ __('Acknowledgments') }}</flux:label>
                                <flux:editor wire:model="acknowledgments"
                                    placeholder="{{ __('Thank you to parents, staff, accompanists, volunteers...') }}" />
                            </flux:field>

                            <flux:field>
                                <flux:label>{{ __('Sponsors & Patrons') }}</flux:label>
                                <flux:editor wire:model="sponsorText"
                                    placeholder="{{ __('Recognize sponsors, boosters, or patron donors...') }}" />
                            </flux:field>

                            @if($ensembles->count() > 1)
                                <flux:field>
                                    <flux:label>{{ __('Intermission') }}</flux:label>
                                    <flux:description>{{ __('Show an intermission banner after which ensemble?') }}</flux:description>
                                    <select wire:model="intermissionAfterEnsemble"
                                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                        <option value="">{{ __('No intermission') }}</option>
                                        @foreach($ensembles as $ensemble)
                                            <option value="{{ $ensemble->pivot->ensemble_sort_order ?? $loop->iteration }}">
                                                {{ __('After') }} {{ $ensemble->ensemble_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </flux:field>
                            @endif
                        </div>
                    </section>

                    {{-- ── SECTION 4: Songs ── --}}
                    <section id="section-songs" class="scroll-mt-28 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="mb-5">
                            <flux:heading size="lg">{{ __('4. Songs') }}</flux:heading>
                            <flux:text class="mt-1">{{ __('Add songs per ensemble. Upload a completed CSV for bulk entry, or add ensembles and songs manually below.') }}</flux:text>
                        </div>

                        {{-- CSV tools --}}
                        <div class="mb-4 flex flex-wrap items-center gap-2">
                            <flux:button size="sm" variant="subtle" icon="arrow-down-tray"
                                wire:click="downloadSongsCsvTemplate">
                                {{ __('Download Template') }}
                            </flux:button>

                            <div x-data="{
                                upload() {
                                    const input = document.createElement('input');
                                    input.type = 'file';
                                    input.accept = '.csv,.txt';
                                    input.onchange = e => {
                                        const file = e.target.files[0];
                                        if (!file) return;
                                        const reader = new FileReader();
                                        reader.onload = ev => $wire.processSongsCsv(ev.target.result);
                                        reader.readAsText(file);
                                    };
                                    input.click();
                                }
                            }">
                                <flux:button size="sm" variant="subtle" icon="arrow-up-tray" x-on:click="upload()">
                                    {{ __('Upload CSV') }}
                                </flux:button>
                            </div>
                        </div>

                        {{-- CSV result message --}}
                        @if(!empty($songsCsvResult))
                            <div class="mb-4 rounded-lg px-4 py-2 text-sm
                                {{ $songsCsvResult['type'] === 'success'
                                    ? 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-400'
                                    : 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400' }}">
                                {{ $songsCsvResult['message'] }}
                            </div>
                        @endif

                        {{-- School ensembles picker --}}
                        @if($schoolEnsembles->isNotEmpty())
                            <div class="-mx-6 mb-5 rounded-xl bg-zinc-50 px-6 py-4 dark:bg-zinc-800/50">
                                <flux:text class="mb-3 text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Ensembles at Your School/Org') }}</flux:text>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @foreach($schoolEnsembles as $ens)
                                        @php $alreadyAdded = collect($wizardEnsembles)->contains('id', $ens->id); @endphp
                                        <button type="button"
                                            wire:click="{{ $alreadyAdded ? '' : 'addSelectedEnsemble(' . $ens->id . ')' }}"
                                            class="flex items-center justify-between rounded-xl border-2 px-4 py-3 text-left transition
                                                {{ $alreadyAdded
                                                    ? 'border-blue-400 bg-blue-50 dark:border-blue-700 dark:bg-blue-950/30 cursor-default'
                                                    : 'border-zinc-200 hover:border-blue-300 dark:border-zinc-700 dark:hover:border-blue-600 cursor-pointer' }}">
                                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $ens->ensemble_name }}</span>
                                            @if($alreadyAdded)
                                                <flux:icon.check class="size-4 text-blue-500" />
                                            @else
                                                <flux:icon.plus class="size-4 text-zinc-400" />
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Ensemble + song groups --}}
                        @if(count($wizardEnsembles) > 0)
                            <div class="space-y-6">
                                @foreach($wizardEnsembles as $ensIdx => $ens)
                                    <div wire:key="ens-songs-{{ $ensIdx }}" class="space-y-3">

                                        {{-- Ensemble header --}}
                                        <div class="flex items-center justify-between border-b border-zinc-200 pb-2 dark:border-zinc-700">
                                            <div class="flex items-center gap-2">
                                                <flux:icon.musical-note class="size-5 text-zinc-400" />
                                                <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $ens['name'] }}</span>
                                                @if($ens['id'] === null)
                                                    <flux:badge size="sm" color="green">{{ __('New') }}</flux:badge>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-1">
                                                @if($ensIdx > 0)
                                                    <flux:button wire:click="moveWizardEnsembleUp({{ $ensIdx }})" variant="subtle" size="sm" icon="chevron-up" title="{{ __('Move up') }}" />
                                                @endif
                                                @if($ensIdx < count($wizardEnsembles) - 1)
                                                    <flux:button wire:click="moveWizardEnsembleDown({{ $ensIdx }})" variant="subtle" size="sm" icon="chevron-down" title="{{ __('Move down') }}" />
                                                @endif
                                                <flux:button wire:click="removeWizardEnsemble({{ $ensIdx }})" variant="subtle" size="sm" icon="trash" />
                                            </div>
                                        </div>

                                        {{-- Song rows --}}
                                        @if(!empty($ensembleSongs[$ensIdx] ?? []))
                                            <div class="divide-y divide-zinc-100 overflow-hidden rounded-xl border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-700">
                                                @foreach($ensembleSongs[$ensIdx] as $songIdx => $song)
                                                    <div wire:key="song-{{ $ensIdx }}-{{ $songIdx }}" class="space-y-2 p-3"
                                                        x-on:focus-song-title.window="
                                                            if ($event.detail.ensembleIndex == {{ $ensIdx }} && $event.detail.songIndex == {{ $songIdx }})
                                                                $nextTick(() => $el.querySelector('input')?.focus())
                                                        ">
                                                        <div class="grid items-center gap-2 sm:grid-cols-[1fr_1fr_1fr_auto_auto_auto_auto]">
                                                            <flux:input wire:model.blur="ensembleSongs.{{ $ensIdx }}.{{ $songIdx }}.title"
                                                                placeholder="{{ __('Song title') }}" />
                                                            <flux:input wire:model.blur="ensembleSongs.{{ $ensIdx }}.{{ $songIdx }}.composer"
                                                                placeholder="{{ __('Composer') }}" />
                                                            <flux:input wire:model.blur="ensembleSongs.{{ $ensIdx }}.{{ $songIdx }}.arranger"
                                                                placeholder="{{ __('Arranger') }}" />
                                                            <flux:switch wire:model.live="ensembleSongs.{{ $ensIdx }}.{{ $songIdx }}.showLyrics"
                                                                label="{{ __('Lyrics') }}" />
                                                            @if($songIdx > 0)
                                                                <flux:button wire:click="moveSongRowUp({{ $ensIdx }}, {{ $songIdx }})" variant="subtle" size="sm" icon="chevron-up" title="{{ __('Move up') }}" />
                                                            @else
                                                                <div></div>
                                                            @endif
                                                            @if($songIdx < count($ensembleSongs[$ensIdx]) - 1)
                                                                <flux:button wire:click="moveSongRowDown({{ $ensIdx }}, {{ $songIdx }})" variant="subtle" size="sm" icon="chevron-down" title="{{ __('Move down') }}" />
                                                            @else
                                                                <div></div>
                                                            @endif
                                                            <flux:button wire:click="removeSongRow({{ $ensIdx }}, {{ $songIdx }})" variant="subtle" size="sm" icon="trash" />
                                                        </div>
                                                        <flux:input wire:model.blur="ensembleSongs.{{ $ensIdx }}.{{ $songIdx }}.programNotes"
                                                            placeholder="{{ __('Program notes — soloists, accompanists, guest conductors… (optional)') }}" />
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <flux:button wire:click="addSongRow({{ $ensIdx }})" variant="subtle" size="sm" icon="plus">
                                            {{ __('Add Song') }}
                                        </flux:button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <flux:callout icon="information-circle">
                                <flux:callout.text>{{ __('No songs yet. Upload a CSV, pick a school ensemble above, or create a new ensemble below.') }}</flux:callout.text>
                            </flux:callout>
                        @endif

                        {{-- Create new ensemble --}}
                        <div class="mt-5 rounded-xl border border-dashed border-zinc-300 p-4 dark:border-zinc-600">
                            @if(!$showNewEnsembleForm)
                                <flux:button wire:click="$set('showNewEnsembleForm', true)" variant="subtle" icon="plus">
                                    {{ __('Create New Ensemble') }}
                                </flux:button>
                            @else
                                <div class="space-y-4" x-init="$nextTick(() => $el.querySelector('input')?.focus())">
                                    <flux:heading size="sm">{{ __('New Ensemble') }}</flux:heading>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <flux:field>
                                            <flux:label>{{ __('Ensemble Name') }}</flux:label>
                                            <flux:input wire:model="newEnsembleName" placeholder="{{ __('e.g. Concert Choir') }}" />
                                            <flux:error name="newEnsembleName" />
                                        </flux:field>
                                        <flux:field>
                                            <flux:label>{{ __('Voice Parts') }}</flux:label>
                                            <select wire:model="newEnsembleType"
                                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                                <option value="Satb">{{ __('SATB') }}</option>
                                                <option value="SopranoAlto">{{ __('Soprano / Alto') }}</option>
                                                <option value="TenorBass">{{ __('Tenor / Bass') }}</option>
                                                <option value="Unknown">{{ __('Other') }}</option>
                                            </select>
                                        </flux:field>
                                    </div>
                                    <div class="flex gap-2">
                                        <flux:button wire:click="createWizardEnsemble" variant="primary" size="sm">{{ __('Add Ensemble') }}</flux:button>
                                        <flux:button wire:click="$set('showNewEnsembleForm', false)" variant="subtle" size="sm">{{ __('Cancel') }}</flux:button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Lyrics copyright acknowledgment --}}
                        @if($this->anyLyricsEnabled())
                            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/30">
                                <div class="flex items-start gap-3">
                                    <input type="checkbox" wire:model.live="lyricsCopyrightAcknowledged"
                                        id="lyricsCopyrightPro"
                                        class="mt-0.5 size-4 rounded border-zinc-300 text-blue-600 dark:border-zinc-600">
                                    <label for="lyricsCopyrightPro" class="text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ __('I confirm that I have the right to publicly display the selected lyrics, or that they are in the public domain.') }}
                                    </label>
                                </div>
                                <flux:error name="lyricsCopyrightAcknowledged" />
                            </div>
                        @endif
                    </section>

                    {{-- ── SECTION 5: Roster & Honors ── --}}
                    <section id="section-roster" class="scroll-mt-28 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="mb-5">
                            <flux:heading size="lg">{{ __('5. Roster & Honors') }}</flux:heading>
                            <flux:text class="mt-1">{{ __('Optional. List students per ensemble and define honor designations (¹ Section Leader, ² All-State, etc.).') }}</flux:text>
                        </div>

                        @if(empty($honors) && empty($rosters))
                            <flux:callout icon="information-circle">
                                <flux:callout.text>{{ __('No ensembles yet. Add ensembles in the Songs section above to enable roster management.') }}</flux:callout.text>
                            </flux:callout>
                        @else
                            @if($this->hasAnyStudents())
                                <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/30">
                                    <div class="flex items-start gap-3">
                                        <input type="checkbox" wire:model.live="studentNamesAcknowledged"
                                            id="studentNamesAckPro"
                                            class="mt-0.5 size-4 rounded border-zinc-300 text-blue-600 dark:border-zinc-600">
                                        <label for="studentNamesAckPro" class="text-sm text-zinc-700 dark:text-zinc-300">
                                            {{ __('I acknowledge that the names I enter will be publicly visible on this digital program. I will not enter any personally identifying information beyond student names (no ID numbers, contact details, addresses, or dates of birth).') }}
                                        </label>
                                    </div>
                                    <flux:error name="studentNamesAcknowledged" />
                                </div>
                            @endif

                            <div class="space-y-5">
                                @if(array_key_exists('general', $honors))
                                    @include('livewire.digital-programs.wizard.ensemble-section', [
                                        'ensembleKey'  => 'general',
                                        'ensembleName' => __('General (No Ensemble)'),
                                        'voiceParts'   => $voiceParts,
                                        'showCsvTools' => true,
                                    ])
                                @endif

                                @foreach($wizardEnsembles as $ens)
                                    @if($ens['id'] !== null)
                                        @php $ek = (string) $ens['id']; @endphp
                                        @if(array_key_exists($ek, $honors))
                                            @include('livewire.digital-programs.wizard.ensemble-section', [
                                                'ensembleKey'  => $ek,
                                                'ensembleName' => $ens['name'],
                                                'voiceParts'   => $voiceParts,
                                                'showCsvTools' => true,
                                            ])
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </section>

                    {{-- ── Acknowledgment error summary ── --}}
                    @if($errors->has('studentNamesAcknowledged') || $errors->has('lyricsCopyrightAcknowledged'))
                        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950/30">
                            <div class="flex items-start gap-3">
                                <flux:icon.exclamation-triangle class="mt-0.5 size-5 shrink-0 text-red-600 dark:text-red-400" />
                                <div class="space-y-1 text-sm text-red-700 dark:text-red-300">
                                    <p class="font-semibold">{{ __('Acknowledgment required before publishing:') }}</p>
                                    @if($errors->has('studentNamesAcknowledged'))
                                        <p>→ <a href="#section-roster" class="underline underline-offset-2">{{ __('Roster & Honors') }}</a>: {{ __('check the student names disclosure.') }}</p>
                                    @endif
                                    @if($errors->has('lyricsCopyrightAcknowledged'))
                                        <p>→ <a href="#section-songs" class="underline underline-offset-2">{{ __('Songs') }}</a>: {{ __('check the lyrics copyright confirmation.') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ── Bottom actions ── --}}
                    <div class="flex justify-end gap-3 pb-6">
                        <flux:button wire:click="save(false)" variant="subtle">{{ __('Save as Draft') }}</flux:button>
                        @unless(auth()->user()->isAssistant())
                            <flux:button wire:click="save(true)" variant="primary" icon="check">{{ __('Publish') }}</flux:button>
                        @endunless
                    </div>

                @endif
            </div>

            {{-- ── Preview panel (right, desktop only) ── --}}
            <div class="hidden w-72 shrink-0 lg:block">
                <div class="sticky top-24 space-y-4">

                    {{-- Theme preview --}}
                    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <div class="h-20 w-full transition-all duration-300"
                            style="background-color: {{ \App\Livewire\DigitalPrograms\PowerUserForm::$themes[$theme]['swatch'] ?? '#09090b' }}">
                        </div>
                        <div class="p-4">
                            <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Theme') }}</flux:text>
                            <div class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">
                                {{ \App\Livewire\DigitalPrograms\PowerUserForm::$themes[$theme]['label'] ?? $theme }}
                            </div>
                            <div class="mt-0.5 text-xs text-zinc-500">
                                {{ \App\Livewire\DigitalPrograms\PowerUserForm::$themes[$theme]['description'] ?? '' }}
                            </div>
                        </div>
                    </div>

                    {{-- Program summary --}}
                    @if($resolvedProgram)
                        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Program') }}</flux:text>
                            <div class="mt-2 font-semibold text-zinc-900 dark:text-zinc-100">{{ $resolvedProgram->event_name }}</div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $resolvedProgram->event_date->format('F j, Y') }}</div>
                            <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $resolvedProgram->director_name }}</div>
                            @if($resolvedProgram->school)
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $resolvedProgram->school->school_name }}</div>
                            @endif
                        </div>
                    @elseif($programLoaded && $startChoice === 'new')
                        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('New Program') }}</flux:text>
                            <div class="mt-2 font-semibold text-zinc-900 dark:text-zinc-100">{{ $newEventName ?: '—' }}</div>
                            <div class="text-sm text-zinc-500">{{ $newEventDate ?: '' }}</div>
                            <div class="text-sm text-zinc-500">{{ $newDirectorName ?: '' }}</div>
                        </div>
                    @endif

                    {{-- Song count --}}
                    @php
                        $totalSongs = collect($ensembleSongs)->flatten(1)->filter(fn($s) => !empty(trim($s['title'] ?? '')))->count();
                    @endphp
                    @if($totalSongs > 0)
                        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Songs') }}</flux:text>
                            <div class="mt-2 font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $totalSongs }} {{ __('song(s)') }}
                                @if(count($wizardEnsembles) > 0)
                                    · {{ count($wizardEnsembles) }} {{ __('ensemble(s)') }}
                                @endif
                            </div>
                            @if($this->anyLyricsEnabled())
                                <div class="mt-0.5 text-xs text-zinc-500">
                                    {{ collect($ensembleSongs)->flatten(1)->filter(fn($s) => !empty($s['showLyrics'] ?? false))->count() }} {{ __('with lyrics enabled') }}
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Print info --}}
                    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Print') }}</flux:text>
                        <div class="mt-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $printOrientation }}</div>
                        <div class="text-xs text-zinc-500">{{ $printOrientation === 'Portrait' ? '8.5 × 11"' : '11 × 8.5"' }}</div>
                    </div>

                    {{-- QR code card --}}
                    @if($digitalProgram)
                        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Public Web Address') }}</flux:text>
                            <p class="mt-1 break-all font-mono text-xs text-zinc-500 dark:text-zinc-400">/p/{{ $digitalProgram->slug }}</p>

                            @if($digitalProgram->is_published)
                                <div class="mt-3 flex flex-col gap-2">
                                    <flux:button
                                        href="{{ route('program.public', $digitalProgram->slug) }}"
                                        target="_blank"
                                        variant="subtle"
                                        size="sm"
                                        icon="arrow-top-right-on-square"
                                        class="w-full">
                                        {{ __('View Live Program') }}
                                    </flux:button>
                                    <flux:button
                                        href="{{ route('program.qr', $digitalProgram->slug) }}"
                                        target="_blank"
                                        variant="subtle"
                                        size="sm"
                                        class="w-full">
                                        {{ __('Large QR Code') }}
                                    </flux:button>
                                </div>
                                <div class="mt-3 flex justify-center rounded-lg bg-white p-2">
                                    <img src="{{ route('program.qr', ['slug' => $digitalProgram->slug, 'size' => 120]) }}"
                                        alt="{{ __('QR Code') }}"
                                        width="120" height="120"
                                        class="block">
                                </div>
                            @else
                                <flux:badge color="zinc" size="sm" class="mt-2">{{ __('Draft — not published') }}</flux:badge>
                            @endif
                        </div>
                    @endif

                    {{-- Info note --}}
                    @if(!$digitalProgram)
                        <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-4 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-400">
                            {{ __('Your unique web address and QR code will be available after saving.') }}
                        </div>
                    @endif

                </div>
            </div>

        </div>
    </div>
</div>
