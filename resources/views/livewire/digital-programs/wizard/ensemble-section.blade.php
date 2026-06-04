<div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
    <flux:heading size="lg" class="mb-5">{{ $ensembleName }}</flux:heading>

    {{-- ── Honors / designations ── --}}
    <div class="mb-5">
        <div class="mb-3 flex items-center justify-between">
            <div>
                <flux:heading size="sm">{{ __('Honor Designations') }}</flux:heading>
                <flux:text class="text-xs text-zinc-400">{{ __('Superscripts printed next to student names (¹ Section Leader, ² All-State, etc.)') }}</flux:text>
            </div>
            <flux:button size="sm" variant="subtle" wire:click="addHonor('{{ $ensembleKey }}')" icon="plus">
                {{ __('Add Honor') }}
            </flux:button>
        </div>

        @forelse($honors[$ensembleKey] ?? [] as $hi => $honor)
            <div class="mb-2 flex items-center gap-2" wire:key="honor-{{ $ensembleKey }}-{{ $hi }}">
                <div class="flex size-7 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-bold dark:bg-zinc-700">
                    {{ $hi + 1 }}
                </div>
                <flux:input
                    wire:model="honors.{{ $ensembleKey }}.{{ $hi }}.label"
                    placeholder="{{ __('e.g. Section Leader') }}"
                    class="flex-1" />
                <flux:button size="sm" variant="subtle" wire:click="removeHonor('{{ $ensembleKey }}', {{ $hi }})" icon="x-mark" />
            </div>
        @empty
            <flux:text class="text-sm text-zinc-400 dark:text-zinc-500">
                {{ __('No honor designations. Click "Add Honor" to create one.') }}
            </flux:text>
        @endforelse
    </div>

    <flux:separator class="my-4" />

    {{-- ── Student roster ── --}}
    <div>
        <div class="mb-3 flex items-center justify-between">
            <div>
                <flux:heading size="sm">{{ __('Students') }}</flux:heading>
                @if(!empty($honors[$ensembleKey]))
                    <flux:text class="text-xs text-zinc-400">{{ __('Check the numbered boxes to assign honor designations.') }}</flux:text>
                @endif
            </div>
            <flux:button size="sm" variant="subtle" wire:click="addStudent('{{ $ensembleKey }}')" icon="plus">
                {{ __('Add Student') }}
            </flux:button>
        </div>

        @forelse($rosters[$ensembleKey] ?? [] as $si => $student)
            <div class="mb-2 flex flex-wrap items-center gap-2" wire:key="student-{{ $ensembleKey }}-{{ $si }}">
                {{-- Name --}}
                <flux:input
                    wire:model="rosters.{{ $ensembleKey }}.{{ $si }}.student_name"
                    placeholder="{{ __('Student name') }}"
                    class="min-w-40 flex-1" />

                {{-- Voice part --}}
                <select wire:model="rosters.{{ $ensembleKey }}.{{ $si }}.voice_part"
                    class="rounded-lg border border-zinc-300 bg-white px-2 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                    <option value="">{{ __('Part') }}</option>
                    @foreach($voiceParts as $part)
                        <option value="{{ $part }}">{{ $part }}</option>
                    @endforeach
                </select>

                {{-- Honor checkboxes --}}
                @foreach($honors[$ensembleKey] ?? [] as $hi => $honor)
                    @if(!empty(trim($honor['label'] ?? '')))
                        <label class="flex size-8 shrink-0 cursor-pointer select-none items-center justify-center rounded-lg border-2 text-xs font-bold transition
                            {{ in_array($hi, array_map('intval', $student['honorIndexes'] ?? []))
                                ? 'border-blue-500 bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'
                                : 'border-zinc-300 text-zinc-500 hover:border-zinc-400 dark:border-zinc-600 dark:text-zinc-400' }}"
                            title="{{ trim($honor['label']) }}">
                            <input type="checkbox"
                                wire:model="rosters.{{ $ensembleKey }}.{{ $si }}.honorIndexes"
                                value="{{ $hi }}"
                                class="sr-only">
                            {{ $hi + 1 }}
                        </label>
                    @endif
                @endforeach

                {{-- Remove student --}}
                <flux:button size="sm" variant="subtle" wire:click="removeStudent('{{ $ensembleKey }}', {{ $si }})" icon="x-mark" />
            </div>
        @empty
            <flux:text class="text-sm text-zinc-400 dark:text-zinc-500">
                {{ __('No students added. Click "Add Student" to begin.') }}
            </flux:text>
        @endforelse
    </div>
</div>
