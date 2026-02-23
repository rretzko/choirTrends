<div>
    <div class="mb-6 space-y-4">
        <flux:heading size="xl">{{ __('Ensembles') }}</flux:heading>

        <div class="flex justify-center gap-2">
            <flux:button wire:click="$set('filter', 'my')" :variant="$filter === 'my' ? 'primary' : 'ghost'" size="sm" title="{{ __('Ensembles from my programs') }}">
                {{ __('My') }} ({{ $myCount }})
            </flux:button>
            @if ($compliance['canViewAll'])
                <flux:button wire:click="$set('filter', 'all')" :variant="$filter === 'all' ? 'primary' : 'ghost'" size="sm" title="{{ __('Ensembles from the submitted programs') }}">
                    {{ __('All') }} ({{ $allCount }})
                </flux:button>
            @else
                <flux:tooltip content="{{ __('Upload programs to unlock community data') }}">
                    <div>
                        <flux:button disabled variant="ghost" size="sm">
                            {{ __('All') }} ({{ $allCount }})
                        </flux:button>
                    </div>
                </flux:tooltip>
            @endif
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
            <thead class="bg-neutral-50 dark:bg-neutral-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('School') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('Ensemble Name') }}
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('Type') }}
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('A Cappella') }}
                    </th>
                    <th class="w-16 px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody>
                @php $prevSchool = null; $band = false; @endphp
                @forelse ($ensembles as $ensemble)
                    @php
                        $currentSchool = $schoolDisplayNames[$ensemble->id];
                        if ($currentSchool !== $prevSchool) { $band = ! $band; $prevSchool = $currentSchool; }
                    @endphp
                    <tr wire:key="ensemble-{{ $ensemble->id }}" class="border-t {{ $band ? 'border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900' : 'border-neutral-200/80 bg-neutral-50/60 dark:border-neutral-700/50 dark:bg-neutral-800/40' }}">
                        <td class="whitespace-nowrap px-6 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $schoolDisplayNames[$ensemble->id] }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-2 text-sm text-neutral-900 dark:text-neutral-100">
                            {{ $displayNames[$ensemble->id] }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-2 text-center text-sm text-neutral-500 dark:text-neutral-400">
                            @if ($ownedEnsembleIds[$ensemble->id])
                                <select
                                    wire:change="updateType({{ $ensemble->id }}, $event.target.value)"
                                    class="rounded-md border border-zinc-200 bg-white px-2 py-1 text-center text-sm focus:border-zinc-400 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                                >
                                    @foreach (\App\Enums\EnsembleType::cases() as $ensembleType)
                                        <option value="{{ $ensembleType->value }}" @selected($ensemble->type === $ensembleType)>{{ $ensembleType->label() }}</option>
                                    @endforeach
                                </select>
                            @else
                                {{ $ensemble->type->label() }}
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-2 text-center text-sm text-neutral-500 dark:text-neutral-400">
                            @if ($ownedEnsembleIds[$ensemble->id])
                                <flux:checkbox
                                    wire:click="updateACappella({{ $ensemble->id }}, {{ $ensemble->a_cappella ? 'false' : 'true' }})"
                                    :checked="$ensemble->a_cappella"
                                />
                            @elseif ($ensemble->a_cappella)
                                <flux:icon name="check" class="mx-auto size-4 text-green-600 dark:text-green-400" />
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-2 text-right text-sm">
                            @if ($ownedEnsembleIds[$ensemble->id])
                                <flux:button href="{{ route('ensembles.edit', $ensemble) }}" variant="ghost" size="sm" icon="pencil-square" title="{{ __('Edit Ensemble') }}" />
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400">
                            {{ __('No ensembles found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
