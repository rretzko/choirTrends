@props(['pieces', 'ranked' => true, 'showComposer' => true, 'keyPrefix' => 'piece'])

<flux:table>
    <flux:table.columns>
        @if ($ranked)
            <flux:table.column class="w-10">#</flux:table.column>
        @endif
        <flux:table.column>{{ __('Piece') }}</flux:table.column>
        @if ($showComposer)
            <flux:table.column>{{ __('Composer') }}</flux:table.column>
        @endif
        <flux:table.column class="max-sm:hidden">{{ __('Arranger') }}</flux:table.column>
        <flux:table.column align="end">{{ __('Schools') }}</flux:table.column>
    </flux:table.columns>
    <flux:table.rows>
        @foreach ($pieces as $piece)
            <flux:table.row wire:key="{{ $keyPrefix }}-{{ $piece->id }}">
                @if ($ranked)
                    <flux:table.cell>{{ $loop->iteration }}</flux:table.cell>
                @endif
                <flux:table.cell variant="strong" class="whitespace-normal">
                    <flux:link :href="$piece->url">{{ $piece->title }}</flux:link>
                </flux:table.cell>
                @if ($showComposer)
                    <flux:table.cell class="whitespace-normal">
                        <x-public.name-link :name="$piece->composer" :url="$piece->composerUrl" />
                    </flux:table.cell>
                @endif
                <flux:table.cell class="max-sm:hidden whitespace-normal">
                    <x-public.name-link :name="$piece->arranger" :url="$piece->arrangerUrl" />
                </flux:table.cell>
                <flux:table.cell align="end">{{ $piece->schoolCount }}</flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>
