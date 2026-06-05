<div class="mx-auto max-w-3xl space-y-8 px-4 py-6">

    {{-- ── Step indicator ── --}}
    <div class="flex items-center justify-between">
        @foreach(['Program', 'Style', 'Content', 'Ensembles', 'Songs', 'Roster', 'Publish'] as $i => $label)
            <div class="flex flex-1 items-center">
                <div class="flex flex-col items-center gap-1">
                    <div class="flex size-8 items-center justify-center rounded-full text-xs font-bold
                        @if($step === $i + 1) bg-blue-600 text-white ring-4 ring-blue-100 dark:ring-blue-900
                        @elseif($step > $i + 1) bg-green-600 text-white
                        @else bg-zinc-200 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400 @endif">
                        @if($step > $i + 1)
                            <flux:icon.check class="size-4" />
                        @else
                            {{ $i + 1 }}
                        @endif
                    </div>
                    <span class="hidden text-xs sm:block
                        @if($step === $i + 1) font-semibold text-zinc-900 dark:text-zinc-100
                        @elseif($step > $i + 1) text-green-700 dark:text-green-400
                        @else text-zinc-400 dark:text-zinc-500 @endif">
                        {{ $label }}
                    </span>
                </div>
                @if($i < 6)
                    <div class="mx-1 h-px flex-1 @if($step > $i + 1) bg-green-400 dark:bg-green-700 @else bg-zinc-300 dark:bg-zinc-600 @endif"></div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ── Navigation buttons (top) ── --}}
    <div class="flex items-center justify-between">
        @if($step > 1 || $editingExistingProgram)
            <flux:button wire:click="previousStep" variant="subtle" icon="arrow-left">{{ __('Back') }}</flux:button>
        @else
            <div></div>
        @endif

        @if($step < 7)
            <flux:button wire:click="nextStep" variant="primary" icon-trailing="arrow-right">{{ __('Next') }}</flux:button>
        @else
            <div class="flex gap-3">
                <flux:button wire:click="saveDraft" variant="subtle">{{ __('Save as Draft') }}</flux:button>
                <flux:button wire:click="publish" variant="primary" icon="check">{{ __('Publish') }}</flux:button>
            </div>
        @endif
    </div>

    {{-- ── Save reminder ── --}}
    @if($step < 7)
        <flux:callout icon="exclamation-circle" color="amber">
            <flux:callout.text>{{ __('Reminder: Changes on this page are saved only when you click the "Next" button.') }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- ── Step content ── --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

        {{-- ── STEP 1: Program ── --}}
        @if($step === 1)
            @if($editingExistingProgram)
                {{-- Step 1b: Edit the selected program's details --}}
                <div class="space-y-6">
                    <div>
                        <flux:heading size="xl">{{ __('Confirm Program Details') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('Review and update the event details before continuing.') }}</flux:text>
                    </div>

                    <flux:field>
                        <flux:label>{{ __('Event Name') }}</flux:label>
                        <flux:input wire:model="newEventName" placeholder="{{ __('e.g. Spring Choral Concert') }}" />
                        <flux:error name="newEventName" />
                    </flux:field>

                    <div class="grid gap-4 sm:grid-cols-2">
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
                    </div>

                    <flux:field>
                        <flux:label>{{ __('School Name') }}</flux:label>
                        <flux:input wire:model="newSchoolName" placeholder="{{ __('e.g. Lincoln High School') }}" />
                        <flux:error name="newSchoolName" />
                    </flux:field>
                </div>
            @else
                {{-- Step 1a: Choose existing or new --}}
                <div class="space-y-6">
                    <div>
                        <flux:heading size="xl">{{ __('Choose a Program') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('Use an existing ChoirTrends program or start a brand new one.') }}</flux:text>
                    </div>

                    {{-- Choice cards --}}
                    <div class="grid gap-4 sm:grid-cols-2">
                        <button type="button" wire:click="$set('startChoice', 'existing')"
                            class="flex flex-col gap-3 rounded-xl border-2 p-5 text-left transition
                                {{ $startChoice === 'existing' ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}">
                            <flux:icon.document-text class="size-8 text-blue-500" />
                            <div>
                                <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Use an Existing Program') }}</div>
                                <flux:text class="mt-1 text-sm">{{ __('Select a program you have already entered in ChoirTrends.') }}</flux:text>
                            </div>
                        </button>

                        <button type="button" wire:click="$set('startChoice', 'new')"
                            class="flex flex-col gap-3 rounded-xl border-2 p-5 text-left transition
                                {{ $startChoice === 'new' ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}">
                            <flux:icon.plus-circle class="size-8 text-emerald-500" />
                            <div>
                                <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Start a New Program') }}</div>
                                <flux:text class="mt-1 text-sm">{{ __('Enter event details now and build ensembles and songs in the next steps.') }}</flux:text>
                            </div>
                        </button>
                    </div>

                    {{-- Existing program selector --}}
                    @if($startChoice === 'existing')
                        <div class="space-y-3">
                            <flux:field>
                                <flux:label>{{ __('Select a program') }}</flux:label>
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

                            @if($userPrograms->isEmpty())
                                <flux:callout icon="information-circle">
                                    <flux:callout.text>{{ __('You have no programs yet. Use "Start a New Program" or add programs via the Add Program page first.') }}</flux:callout.text>
                                </flux:callout>
                            @endif
                        </div>
                    @endif

                    {{-- New program fields --}}
                    @if($startChoice === 'new')
                        <div class="space-y-4">
                            <flux:field>
                                <flux:label>{{ __('Event Name') }}</flux:label>
                                <flux:input wire:model="newEventName" placeholder="{{ __('e.g. Spring Choral Concert') }}" />
                                <flux:error name="newEventName" />
                            </flux:field>

                            <div class="grid gap-4 sm:grid-cols-2">
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
                            </div>

                            <flux:field>
                                <flux:label>{{ __('School Name') }}</flux:label>
                                <flux:input wire:model="newSchoolName" placeholder="{{ __('e.g. Lincoln High School') }}" />
                                <flux:error name="newSchoolName" />
                            </flux:field>
                        </div>
                    @endif

                    <flux:error name="startChoice" />
                </div>
            @endif
        @endif

        {{-- ── STEP 2: Style ── --}}
        @if($step === 2)
            <div class="space-y-6">
                <div>
                    <flux:heading size="xl">{{ __('Choose a Style') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Pick a theme and set print orientation.') }}</flux:text>
                </div>

                {{-- Theme picker --}}
                <flux:field>
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
                    <div class="mt-2 flex gap-4">
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
            </div>
        @endif

        {{-- ── STEP 3: Content ── --}}
        @if($step === 3)
            <div class="space-y-6">
                <div>
                    <flux:heading size="xl">{{ __('Program Content') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('All fields are optional. Add the content you want to appear in the program.') }}</flux:text>
                </div>

                <flux:field>
                    <flux:label>{{ __("Director's Welcome Message") }}</flux:label>
                    <flux:editor wire:model="welcomeMessage"
                        placeholder="{{ __('A brief welcome or note to the audience from the director...') }}" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Acknowledgments') }}</flux:label>
                    <flux:editor wire:model="acknowledgments"
                        placeholder="{{ __('Thank you to parents, staff, accompanists, and volunteers...') }}" />
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
        @endif

        {{-- ── STEP 4: Ensembles ── --}}
        @if($step === 4)
            <div class="space-y-6">
                <div>
                    <flux:heading size="xl">{{ __('Ensembles') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Select or create the ensembles that will perform in this program. Ensembles are optional — you can continue without them.') }}</flux:text>
                </div>

                {{-- School ensembles to pick from --}}
                @if($schoolEnsembles->isNotEmpty())
                    <div class="-mx-6 rounded-xl bg-zinc-50 px-6 py-4 dark:bg-zinc-800/50">
                        <flux:text class="mb-3 text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ __("Your School's Ensembles") }}</flux:text>
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
                    <div class="-mx-6 border-b border-zinc-200 dark:border-zinc-700"></div>
                @endif

                {{-- Current program ensembles --}}
                @if(count($wizardEnsembles) > 0)
                    <div class="space-y-2">
                        <flux:text class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Program Ensembles') }}</flux:text>
                        @foreach($wizardEnsembles as $idx => $ens)
                            <div wire:key="wizard-ens-{{ $idx }}" class="flex items-center justify-between rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800/60">
                                <div class="flex items-center gap-3">
                                    <flux:icon.musical-note class="size-5 text-zinc-400" />
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $ens['name'] }}</div>
                                        @if($ens['id'] === null)
                                            <flux:badge size="sm" color="green" class="mt-0.5">{{ __('New') }}</flux:badge>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    @if($idx > 0)
                                        <flux:button wire:click="moveWizardEnsembleUp({{ $idx }})" variant="subtle" size="sm" icon="chevron-up" title="{{ __('Move up') }}" />
                                    @endif
                                    @if($idx < count($wizardEnsembles) - 1)
                                        <flux:button wire:click="moveWizardEnsembleDown({{ $idx }})" variant="subtle" size="sm" icon="chevron-down" title="{{ __('Move down') }}" />
                                    @endif
                                    <flux:button wire:click="removeWizardEnsemble({{ $idx }})" variant="subtle" size="sm" icon="trash" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Create new ensemble --}}
                <div class="rounded-xl border border-dashed border-zinc-300 p-4 dark:border-zinc-600">
                    @if(! $showNewEnsembleForm)
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

                @if(count($wizardEnsembles) === 0)
                    <flux:callout icon="information-circle" color="blue">
                        <flux:callout.text>{{ __('No ensembles added yet. You can continue without ensembles and add them later.') }}</flux:callout.text>
                    </flux:callout>
                @endif
            </div>
        @endif

        {{-- ── STEP 5: Songs ── --}}
        @if($step === 5)
            <div class="space-y-6">
                <div>
                    <flux:heading size="xl">{{ __('Songs') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Add songs for each ensemble. Toggle "Show lyrics" for any song whose lyrics you want displayed in the program.') }}</flux:text>
                </div>

                @if(count($wizardEnsembles) === 0)
                    <flux:callout icon="information-circle">
                        <flux:callout.text>{{ __('No ensembles were added. Go back to add ensembles, or continue to the next step.') }}</flux:callout.text>
                    </flux:callout>
                @else
                    @foreach($wizardEnsembles as $ensIdx => $ens)
                        <div wire:key="ens-songs-{{ $ensIdx }}" class="space-y-3">

                            {{-- Ensemble header --}}
                            <div class="flex items-center gap-2 border-b border-zinc-200 pb-2 dark:border-zinc-700">
                                <flux:icon.musical-note class="size-5 text-zinc-400" />
                                <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $ens['name'] }}</span>
                                @if($ens['id'] === null)
                                    <flux:badge size="sm" color="green">{{ __('New') }}</flux:badge>
                                @endif
                            </div>

                            {{-- Song rows --}}
                            @if(! empty($ensembleSongs[$ensIdx] ?? []))
                                <div class="divide-y divide-zinc-100 overflow-hidden rounded-xl border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-700">
                                    @foreach($ensembleSongs[$ensIdx] as $songIdx => $song)
                                        <div wire:key="song-{{ $ensIdx }}-{{ $songIdx }}" class="p-3 space-y-2"
                                            x-on:focus-song-title.window="
                                                if ($event.detail.ensembleIndex == {{ $ensIdx }} && $event.detail.songIndex == {{ $songIdx }})
                                                    $nextTick(() => $el.querySelector('input')?.focus())
                                            ">
                                            <div class="grid items-center gap-3 sm:grid-cols-[1fr_1fr_1fr_auto_auto_auto_auto]">
                                                <flux:input wire:model.blur="ensembleSongs.{{ $ensIdx }}.{{ $songIdx }}.title"
                                                    placeholder="{{ __('Song title') }}" />
                                                <flux:input wire:model.blur="ensembleSongs.{{ $ensIdx }}.{{ $songIdx }}.composer"
                                                    placeholder="{{ __('Composer (optional)') }}" />
                                                <flux:input wire:model.blur="ensembleSongs.{{ $ensIdx }}.{{ $songIdx }}.arranger"
                                                    placeholder="{{ __('Arranger (optional)') }}" />
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
                                                <flux:button wire:click="removeSongRow({{ $ensIdx }}, {{ $songIdx }})"
                                                    variant="subtle" size="sm" icon="trash" />
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
                @endif

                {{-- Copyright acknowledgment --}}
                @if($this->anyLyricsEnabled())
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/30">
                        <flux:field>
                            <div class="flex items-start gap-3">
                                <input type="checkbox" wire:model.live="lyricsCopyrightAcknowledged"
                                    id="lyricsCopyright"
                                    class="mt-0.5 size-4 rounded border-zinc-300 text-blue-600 dark:border-zinc-600">
                                <label for="lyricsCopyright" class="text-sm text-zinc-700 dark:text-zinc-300">
                                    {{ __('I confirm that I have the right to publicly display the selected lyrics, or that they are in the public domain.') }}
                                </label>
                            </div>
                            <flux:error name="lyricsCopyrightAcknowledged" />
                        </flux:field>
                    </div>
                @endif
            </div>
        @endif

        {{-- ── STEP 6: Roster & Honors ── --}}
        @if($step === 6)
            <div class="space-y-6">
                <div>
                    <flux:heading size="xl">{{ __('Student Roster & Honors') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Optional. List students per ensemble and define any honor designations (e.g. Section Leader, All-State).') }}</flux:text>
                </div>

                @if(empty($honors) && empty($rosters))
                    <flux:callout icon="information-circle">
                        <flux:callout.text>{{ __('No ensembles found in this program yet. Ensembles are created when you add songs via the Edit Program page.') }}</flux:callout.text>
                    </flux:callout>
                @else
                    {{-- Student names acknowledgment --}}
                    @if($this->hasAnyStudents())
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/30">
                            <flux:field>
                                <div class="flex items-start gap-3">
                                    <input type="checkbox" wire:model.live="studentNamesAcknowledged"
                                        id="studentNamesAck"
                                        class="mt-0.5 size-4 rounded border-zinc-300 text-blue-600 dark:border-zinc-600">
                                    <label for="studentNamesAck" class="text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ __('I acknowledge that the names I enter will be publicly visible on this digital program. I will not enter any personally identifying information beyond student names (no ID numbers, contact details, addresses, or dates of birth).') }}
                                    </label>
                                </div>
                                <flux:error name="studentNamesAcknowledged" />
                            </flux:field>
                        </div>
                    @endif

                    {{-- General (unensembled) section --}}
                    @if(array_key_exists('general', $honors))
                        @include('livewire.digital-programs.wizard.ensemble-section', [
                            'ensembleKey' => 'general',
                            'ensembleName' => __('General (No Ensemble)'),
                            'voiceParts' => $voiceParts,
                            'showCsvTools' => true,
                        ])
                    @endif

                    {{-- Per-ensemble sections --}}
                    @foreach($ensembles as $ensemble)
                        @php $ek = (string) $ensemble->id; @endphp
                        @if(array_key_exists($ek, $honors))
                            @include('livewire.digital-programs.wizard.ensemble-section', [
                                'ensembleKey' => $ek,
                                'ensembleName' => $ensemble->ensemble_name,
                                'voiceParts' => $voiceParts,
                                'showCsvTools' => true,
                            ])
                        @endif
                    @endforeach
                @endif
            </div>
        @endif

        {{-- ── STEP 7: Publish ── --}}
        @if($step === 7)
            <div class="space-y-6">
                <div>
                    <flux:heading size="xl">{{ __('Ready to Publish') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Review your program summary and publish when ready.') }}</flux:text>
                </div>

                {{-- Summary card --}}
                @if($resolvedProgram && $digitalProgram)
                    <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Event') }}</flux:text>
                                <div class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $resolvedProgram->event_name }}</div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $resolvedProgram->event_date->format('F j, Y') }}</div>
                            </div>
                            <div>
                                <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Director') }}</flux:text>
                                <div class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $resolvedProgram->director_name }}</div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $resolvedProgram->school?->school_name }}</div>
                            </div>
                            <div>
                                <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Theme') }}</flux:text>
                                <div class="mt-1 flex items-center gap-2">
                                    <span class="inline-block size-4 rounded-full" style="background-color: {{ \App\Livewire\DigitalPrograms\GuidedWizard::$themes[$theme]['swatch'] ?? '#000' }}"></span>
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ \App\Livewire\DigitalPrograms\GuidedWizard::$themes[$theme]['label'] ?? $theme }}</span>
                                </div>
                            </div>
                            <div>
                                <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Ensembles & Songs') }}</flux:text>
                                <div class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ count($wizardEnsembles) }} {{ __('ensemble(s)') }}
                                    · {{ collect($ensembleSongs)->flatten(1)->filter(fn($s) => !empty(trim($s['title'] ?? '')))->count() }} {{ __('song(s)') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Public web address --}}
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Your unique web address') }}</flux:text>
                        <div class="mt-2 flex items-center gap-3">
                            <code class="flex-1 break-all rounded-lg bg-zinc-100 px-3 py-2 font-mono text-sm text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">
                                {{ url('/p/' . $digitalProgram->slug) }}
                            </code>
                            <flux:button size="sm" variant="subtle" x-on:click="navigator.clipboard.writeText('{{ url('/p/' . $digitalProgram->slug) }}')">
                                <flux:icon.clipboard class="size-4" />
                            </flux:button>
                        </div>
                        <flux:text class="mt-2 text-xs text-zinc-400">{{ __('This address is permanent and works as a QR code link. The public display page will be available after you publish.') }}</flux:text>
                    </div>
                @endif

                {{-- Draft note --}}
                <flux:callout icon="information-circle" color="blue">
                    <flux:callout.text>{{ __('Publishing makes the program publicly accessible. Save as Draft if you are not ready yet.') }}</flux:callout.text>
                </flux:callout>
            </div>
        @endif

    </div>

    {{-- ── Navigation buttons ── --}}
    <div class="flex items-center justify-between">
        @if($step > 1 || $editingExistingProgram)
            <flux:button wire:click="previousStep" variant="subtle" icon="arrow-left">{{ __('Back') }}</flux:button>
        @else
            <div></div>
        @endif

        @if($step < 7)
            <flux:button wire:click="nextStep" variant="primary" icon-trailing="arrow-right">{{ __('Next') }}</flux:button>
        @else
            <div class="flex gap-3">
                <flux:button wire:click="saveDraft" variant="subtle">{{ __('Save as Draft') }}</flux:button>
                <flux:button wire:click="publish" variant="primary" icon="check">{{ __('Publish') }}</flux:button>
            </div>
        @endif
    </div>

</div>
