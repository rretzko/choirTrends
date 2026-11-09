@props(['years'])

<flux:table>
    <flux:table.columns>
        <flux:table.column>{{ __('School year') }}</flux:table.column>
        <flux:table.column align="end">{{ __('Performances') }}</flux:table.column>
    </flux:table.columns>
    <flux:table.rows>
        @foreach ($years as $schoolYear => $count)
            <flux:table.row wire:key="year-{{ $schoolYear }}">
                <flux:table.cell>{{ $schoolYear }}</flux:table.cell>
                <flux:table.cell align="end">{{ $count }}</flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>
