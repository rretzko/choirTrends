@props(['months', 'label'])

{{-- Single-series bar chart: one hue (teal-600, validated on light and dark surfaces), bars anchored
     to the baseline with rounded tops, a hover/focus value on every bar, the peak month labelled
     directly, and a table view for screen readers and anyone who prefers numbers. --}}
@php
    $max = max(1, collect($months)->max('programs'));
    $peakIndex = collect($months)->search(fn (array $month): bool => $month['programs'] === $max);
@endphp

<figure class="flex flex-col gap-3">
    <div class="flex h-48 items-end gap-1 border-b border-zinc-300 dark:border-zinc-700" role="img" aria-label="{{ $label }}">
        @foreach ($months as $month)
            <div class="group relative flex h-full flex-1 flex-col items-center justify-end" tabindex="0" wire:key="month-{{ $loop->index }}"
                 aria-label="{{ $month['name'] }}: {{ trans_choice(':count program|:count programs', $month['programs']) }}">
                @if ($loop->index === $peakIndex)
                    <span class="mb-1 text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $month['programs'] }}</span>
                @endif
                <div class="w-full max-w-10 rounded-t bg-teal-600 transition-opacity group-hover:opacity-80 group-focus:opacity-80"
                     style="height: {{ $month['programs'] > 0 ? max(2, round($month['programs'] / $max * 100, 1)) : 0 }}%"></div>
                <span class="pointer-events-none absolute bottom-full z-10 mb-1 hidden whitespace-nowrap rounded-md bg-zinc-900 px-2 py-1 text-xs text-white shadow group-hover:block group-focus:block dark:bg-zinc-100 dark:text-zinc-900">
                    {{ $month['name'] }}: {{ trans_choice(':count program|:count programs', $month['programs']) }}
                </span>
            </div>
        @endforeach
    </div>
    <div class="flex gap-1" aria-hidden="true">
        @foreach ($months as $month)
            <span class="flex-1 text-center text-xs text-zinc-500 dark:text-zinc-400" wire:key="month-label-{{ $loop->index }}">{{ $month['label'] }}</span>
        @endforeach
    </div>

    <details class="text-sm">
        <summary class="cursor-pointer text-zinc-600 dark:text-zinc-400">{{ __('View as table') }}</summary>
        <flux:table class="mt-2">
            <flux:table.columns>
                <flux:table.column>{{ __('Month') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Programs') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($months as $month)
                    <flux:table.row wire:key="month-row-{{ $loop->index }}">
                        <flux:table.cell>{{ $month['name'] }}</flux:table.cell>
                        <flux:table.cell align="end">{{ $month['programs'] }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </details>
</figure>
