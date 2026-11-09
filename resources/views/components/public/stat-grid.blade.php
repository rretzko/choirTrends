@props(['stats'])

{{-- $stats: list of ['value' => int|string, 'label' => string] --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    @foreach ($stats as $stat)
        <flux:card class="text-center" wire:key="stat-{{ $loop->index }}">
            <div class="text-3xl font-semibold text-zinc-900 dark:text-white">
                {{ is_int($stat['value']) ? number_format($stat['value']) : $stat['value'] }}
            </div>
            <flux:text class="mt-1">{{ $stat['label'] }}</flux:text>
        </flux:card>
    @endforeach
</div>
