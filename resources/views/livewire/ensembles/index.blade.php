<div>
    <div class="mb-6 space-y-4">
        <flux:heading size="xl">{{ __('Ensembles') }}</flux:heading>

        <div class="flex justify-center gap-2">
            <flux:button wire:click="$set('filter', 'my')" :variant="$filter === 'my' ? 'primary' : 'ghost'" size="sm" title="{{ __('Ensembles from my programs') }}">
                {{ __('My') }} ({{ $myCount }})
            </flux:button>
            <flux:button wire:click="$set('filter', 'all')" :variant="$filter === 'all' ? 'primary' : 'ghost'" size="sm" title="{{ __('Ensembles from the submitted programs') }}">
                {{ __('All') }} ({{ $allCount }})
            </flux:button>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
            <thead class="bg-neutral-50 dark:bg-neutral-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('Ensemble Name') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('School') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                @forelse ($ensembles as $ensemble)
                    <tr wire:key="ensemble-{{ $ensemble->id }}">
                        <td class="whitespace-nowrap px-6 py-2 text-sm text-neutral-900 dark:text-neutral-100">
                            {{ $displayNames[$ensemble->id] }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $schoolDisplayNames[$ensemble->id] }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400">
                            {{ __('No ensembles found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
