@props(['artists', 'keyPrefix' => 'artist', 'showPieces' => true])

<flux:table>
    <flux:table.columns>
        <flux:table.column class="w-10">#</flux:table.column>
        <flux:table.column>{{ __('Name') }}</flux:table.column>
        @if ($showPieces)
            <flux:table.column align="end">{{ __('Pieces') }}</flux:table.column>
        @endif
        <flux:table.column align="end">{{ __('Schools') }}</flux:table.column>
    </flux:table.columns>
    <flux:table.rows>
        @foreach ($artists as $artist)
            <flux:table.row wire:key="{{ $keyPrefix }}-{{ $artist->id }}">
                <flux:table.cell>{{ $loop->iteration }}</flux:table.cell>
                <flux:table.cell variant="strong" class="whitespace-normal">
                    <flux:link :href="$artist->url">{{ $artist->name }}</flux:link>
                </flux:table.cell>
                @if ($showPieces)
                    <flux:table.cell align="end">{{ $artist->pieceCount }}</flux:table.cell>
                @endif
                <flux:table.cell align="end">{{ $artist->schoolCount }}</flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>
