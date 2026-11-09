@php
    $metaDescription = 'The most-performed choral pieces, composers and arrangers in school concert programs, based on '
        .number_format($stats['program_count']).' programs from '.number_format($stats['school_count']).' schools. Updated as directors share programs.';

    $itemList = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Most-performed choral pieces',
        'numberOfItems' => $pieces->count(),
        'itemListElement' => $pieces->values()->map(fn (\App\Services\TrendingPiece $piece, int $index): array => [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'url' => $piece->url,
            'item' => array_filter([
                '@type' => 'MusicComposition',
                'name' => $piece->title,
                'url' => $piece->url,
                'composer' => $piece->composer ? array_filter(['@type' => 'Person', 'name' => $piece->composer, 'url' => $piece->composerUrl]) : null,
            ]),
        ])->all(),
    ];
@endphp

<x-layouts.catalog :title="__('Trending Choral Repertoire')" :description="$metaDescription">
    <x-slot:head>
        <x-public.json-ld :data="$itemList" />
    </x-slot:head>

    <div class="flex flex-col gap-12">
        <section class="flex flex-col gap-4">
            <flux:heading level="1" size="xl" class="text-3xl! lg:text-4xl!">{{ __('Trending Choral Repertoire') }}</flux:heading>
            <flux:text class="max-w-3xl text-base">
                {{ __('The pieces, composers and arrangers choral directors are programming most, drawn from concert programs shared on :app.', ['app' => config('app.name')]) }}
            </flux:text>

            <x-public.stat-grid :stats="[
                ['value' => $stats['program_count'], 'label' => __('Programs')],
                ['value' => $stats['school_count'], 'label' => __('Schools')],
                ['value' => $stats['piece_count'], 'label' => __('Pieces')],
                ['value' => $stats['performance_count'], 'label' => __('Performances')],
            ]" />
        </section>

        <section class="flex flex-col gap-4" aria-labelledby="top-pieces">
            <div class="flex flex-col gap-1">
                <flux:heading level="2" size="lg" id="top-pieces">{{ __('Most-Performed Pieces') }}</flux:heading>
                <flux:text>{{ __('Ranked by the number of different schools that have performed each piece.') }}</flux:text>
            </div>

            @if ($pieces->isEmpty())
                <flux:callout icon="musical-note" :heading="__('Not enough data yet')" :text="__('Pieces appear here once enough schools have performed them. Check back soon.')" />
            @else
                <x-public.piece-table :pieces="$pieces" />
            @endif
        </section>

        <div class="grid gap-12 lg:grid-cols-2">
            @foreach ([
                ['id' => 'top-composers', 'heading' => __('Most-Performed Composers'), 'artists' => $composers],
                ['id' => 'top-arrangers', 'heading' => __('Most-Performed Arrangers'), 'artists' => $arrangers],
            ] as $group)
                <section class="flex flex-col gap-4" aria-labelledby="{{ $group['id'] }}" wire:key="{{ $group['id'] }}">
                    <flux:heading level="2" size="lg" id="{{ $group['id'] }}">{{ $group['heading'] }}</flux:heading>

                    @if ($group['artists']->isEmpty())
                        <flux:text>{{ __('Not enough data yet.') }}</flux:text>
                    @else
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column class="w-10">#</flux:table.column>
                                <flux:table.column>{{ __('Name') }}</flux:table.column>
                                <flux:table.column align="end">{{ __('Pieces') }}</flux:table.column>
                                <flux:table.column align="end">{{ __('Schools') }}</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach ($group['artists'] as $artist)
                                    <flux:table.row wire:key="{{ $group['id'] }}-{{ $artist->id }}">
                                        <flux:table.cell>{{ $loop->iteration }}</flux:table.cell>
                                        <flux:table.cell variant="strong" class="whitespace-normal">
                                            <flux:link :href="$artist->url">{{ $artist->name }}</flux:link>
                                        </flux:table.cell>
                                        <flux:table.cell align="end">{{ $artist->pieceCount }}</flux:table.cell>
                                        <flux:table.cell align="end">{{ $artist->schoolCount }}</flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    @endif
                </section>
            @endforeach
        </div>

        <flux:text>
            <flux:link :href="route('composers.index')">{{ __('Browse all composers and arrangers') }} →</flux:link>
        </flux:text>

        <x-public.data-notice :minimum-schools="$minimumSchools" />
    </div>
</x-layouts.catalog>
