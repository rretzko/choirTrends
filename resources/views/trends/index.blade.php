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
            'item' => array_filter([
                '@type' => 'MusicComposition',
                'name' => $piece->title,
                'composer' => $piece->composer ? ['@type' => 'Person', 'name' => $piece->composer] : null,
                'musicArrangement' => $piece->arranger ? ['@type' => 'MusicComposition', 'name' => $piece->title.' (arr. '.$piece->arranger.')'] : null,
            ]),
        ])->all(),
    ];
@endphp

<x-layouts.catalog :title="__('Trending Choral Repertoire')" :description="$metaDescription">
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode($itemList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </x-slot:head>

    <div class="flex flex-col gap-12">
        <section class="flex flex-col gap-4">
            <flux:heading level="1" size="xl" class="text-3xl! lg:text-4xl!">{{ __('Trending Choral Repertoire') }}</flux:heading>
            <flux:text class="max-w-3xl text-base">
                {{ __('The pieces, composers and arrangers choral directors are programming most, drawn from concert programs shared on :app.', ['app' => config('app.name')]) }}
            </flux:text>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ([
                    ['value' => $stats['program_count'], 'label' => __('Programs')],
                    ['value' => $stats['school_count'], 'label' => __('Schools')],
                    ['value' => $stats['piece_count'], 'label' => __('Pieces')],
                    ['value' => $stats['performance_count'], 'label' => __('Performances')],
                ] as $stat)
                    <flux:card class="text-center" wire:key="stat-{{ $loop->index }}">
                        <div class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ number_format($stat['value']) }}</div>
                        <flux:text class="mt-1">{{ $stat['label'] }}</flux:text>
                    </flux:card>
                @endforeach
            </div>
        </section>

        <section class="flex flex-col gap-4" aria-labelledby="top-pieces">
            <div class="flex flex-col gap-1">
                <flux:heading level="2" size="lg" id="top-pieces">{{ __('Most-Performed Pieces') }}</flux:heading>
                <flux:text>{{ __('Ranked by the number of different schools that have performed each piece.') }}</flux:text>
            </div>

            @if ($pieces->isEmpty())
                <flux:callout icon="musical-note" :heading="__('Not enough data yet')" :text="__('Pieces appear here once enough schools have performed them. Check back soon.')" />
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-10">#</flux:table.column>
                        <flux:table.column>{{ __('Piece') }}</flux:table.column>
                        <flux:table.column>{{ __('Composer') }}</flux:table.column>
                        <flux:table.column class="max-sm:hidden">{{ __('Arranger') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Schools') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($pieces as $piece)
                            <flux:table.row wire:key="piece-{{ $loop->index }}">
                                <flux:table.cell>{{ $loop->iteration }}</flux:table.cell>
                                <flux:table.cell variant="strong" class="whitespace-normal">{{ $piece->title }}</flux:table.cell>
                                <flux:table.cell class="whitespace-normal">{{ $piece->composer ?? '—' }}</flux:table.cell>
                                <flux:table.cell class="max-sm:hidden whitespace-normal">{{ $piece->arranger ?? '—' }}</flux:table.cell>
                                <flux:table.cell align="end">{{ $piece->schoolCount }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
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
                                    <flux:table.row wire:key="{{ $group['id'] }}-{{ $loop->index }}">
                                        <flux:table.cell>{{ $loop->iteration }}</flux:table.cell>
                                        <flux:table.cell variant="strong" class="whitespace-normal">{{ $artist->name }}</flux:table.cell>
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

        <section class="flex flex-col gap-2" aria-labelledby="about-data">
            <flux:heading level="2" size="lg" id="about-data">{{ __('About this data') }}</flux:heading>
            <flux:text class="max-w-3xl">
                {{ __('Rankings come from concert programs uploaded by choral directors. To protect privacy, a piece, composer or arranger is listed only after at least :count different schools have performed it, and no schools, directors or students are ever named.', ['count' => $minimumSchools]) }}
            </flux:text>
        </section>

        @guest
            <flux:card class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <flux:heading size="lg">{{ __('Add your concerts to the trends') }}</flux:heading>
                    <flux:text>{{ __('Upload a program in minutes and explore the full repertoire database. Free for choral directors.') }}</flux:text>
                </div>
                <flux:button variant="primary" :href="route('register')">{{ __('Get Started') }}</flux:button>
            </flux:card>
        @endguest
    </div>
</x-layouts.catalog>
