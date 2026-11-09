@php
    $name = $artist->artist_name;
    $role = match (true) {
        $profile['isComposer'] && $profile['isArranger'] => __('Choral composer & arranger'),
        $profile['isArranger'] => __('Choral arranger'),
        default => __('Choral composer'),
    };

    $topTitles = $profile['composedPieces']->merge($profile['arrangedPieces'])->unique('id')->take(3)->pluck('title');

    $metaDescription = $name.'\'s choral music has been performed at '.$profile['schoolCount'].' schools in '
        .$profile['performanceCount'].' concert performances'
        .($topTitles->isNotEmpty() ? '. Most performed: '.$topTitles->join(', ', ' and ') : '')
        .'.';

    $works = $profile['composedPieces']->merge($profile['arrangedPieces'])->unique('id')->values();

    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => [
            array_filter([
                '@type' => 'Person',
                'name' => $name,
                'url' => $canonicalUrl,
                'description' => $role,
            ]),
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trends', 'item' => route('trends')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Composers & Arrangers', 'item' => route('composers.index')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $name, 'item' => $canonicalUrl],
                ],
            ],
            ...$works->map(fn (\App\Services\TrendingPiece $piece): array => array_filter([
                '@type' => 'MusicComposition',
                'name' => $piece->title,
                'url' => $piece->url,
                'composer' => $piece->composer ? array_filter(['@type' => 'Person', 'name' => $piece->composer, 'url' => $piece->composerUrl]) : null,
            ]))->all(),
        ],
    ];
@endphp

<x-layouts.catalog :title="$name.' — '.__('Choral Music Performance Trends')" :description="$metaDescription" :canonical="$canonicalUrl">
    <x-slot:head>
        <x-public.json-ld :data="$structuredData" />
    </x-slot:head>

    <div class="flex flex-col gap-10">
        <section class="flex flex-col gap-4">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('trends')">{{ __('Trends') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('composers.index')">{{ __('Composers & Arrangers') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $name }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div class="flex flex-col gap-1">
                <flux:heading level="1" size="xl" class="text-3xl! lg:text-4xl!">{{ $name }}</flux:heading>
                <flux:text class="text-base">{{ $role }}</flux:text>
            </div>

            <x-public.stat-grid :stats="[
                ['value' => $profile['schoolCount'], 'label' => __('Schools')],
                ['value' => $profile['pieceCount'], 'label' => __('Pieces programmed')],
                ['value' => $profile['performanceCount'], 'label' => __('Performances')],
                ['value' => count($profile['performancesBySchoolYear']), 'label' => __('School years')],
            ]" />
        </section>

        @foreach ([
            ['id' => 'composed', 'heading' => __('Most-Performed Works'), 'pieces' => $profile['composedPieces'], 'showComposer' => false],
            ['id' => 'arranged', 'heading' => __('Most-Performed Arrangements'), 'pieces' => $profile['arrangedPieces'], 'showComposer' => true],
        ] as $section)
            @if ($section['pieces']->isNotEmpty())
                <section class="flex flex-col gap-4" aria-labelledby="{{ $section['id'] }}" wire:key="section-{{ $section['id'] }}">
                    <flux:heading level="2" size="lg" id="{{ $section['id'] }}">{{ $section['heading'] }}</flux:heading>
                    <x-public.piece-table :pieces="$section['pieces']" :show-composer="$section['showComposer']" :key-prefix="$section['id']" />
                </section>
            @endif
        @endforeach

        @if ($profile['unlistedPieceCount'] > 0)
            <flux:callout icon="information-circle">
                <flux:callout.text>
                    {{ trans_choice(
                        '{1} :count more piece by :name appears in uploaded programs but has not yet been performed by :min schools, so it is not listed individually.|[2,*] :count more pieces by :name appear in uploaded programs but have not yet been performed by :min schools each, so they are not listed individually.',
                        $profile['unlistedPieceCount'],
                        ['name' => $name, 'min' => config('trends.minimum_schools')],
                    ) }}
                </flux:callout.text>
            </flux:callout>
        @endif

        <div class="grid gap-10 lg:grid-cols-2">
            @if ($profile['performancesBySchoolYear'] !== [])
                <section class="flex flex-col gap-4" aria-labelledby="by-year">
                    <flux:heading level="2" size="lg" id="by-year">{{ __('Performances by School Year') }}</flux:heading>
                    <x-public.school-year-table :years="$profile['performancesBySchoolYear']" />
                </section>
            @endif

            @if ($profile['relatedArtists']->isNotEmpty())
                <section class="flex flex-col gap-4" aria-labelledby="related">
                    <div class="flex flex-col gap-1">
                        <flux:heading level="2" size="lg" id="related">{{ __('Often Programmed Alongside') }}</flux:heading>
                        <flux:text>{{ __('Composers whose music appears on the same concert programs as :name.', ['name' => $name]) }}</flux:text>
                    </div>
                    <ul class="flex flex-col gap-2">
                        @foreach ($profile['relatedArtists'] as $related)
                            <li wire:key="related-{{ $related->id }}">
                                <flux:link :href="$related->url">{{ $related->name }}</flux:link>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <x-public.data-notice :minimum-schools="config('trends.minimum_schools')" />
    </div>
</x-layouts.catalog>
