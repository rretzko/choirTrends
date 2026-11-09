@php
    /** @var \App\Services\TrendingPiece $piece */
    $piece = $profile['piece'];

    $byline = collect([
        $piece->composer ? __('by :name', ['name' => $piece->composer]) : null,
        $piece->arranger ? __('arr. :name', ['name' => $piece->arranger]) : null,
    ])->filter()->join(', ');

    $pageTitle = $piece->title.($piece->composer ? ' by '.$piece->composer : '').' — '.__('Choral Performance Trends');

    $metaDescription = $piece->title.($byline !== '' ? ' ('.$byline.')' : '')
        .' has been performed at '.$piece->schoolCount.' schools in '.$piece->performanceCount.' concert performances.'
        .($profile['voicings'] !== [] ? ' Voicing: '.implode(', ', $profile['voicings']).'.' : '')
        .($profile['difficulty'] ? ' Difficulty: '.$profile['difficulty'].'.' : '');

    $breadcrumbItems = [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trends', 'item' => route('trends')],
    ];
    if ($piece->composerUrl) {
        $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $piece->composer, 'item' => $piece->composerUrl];
    }
    $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => count($breadcrumbItems) + 1, 'name' => $piece->title, 'item' => $canonicalUrl];

    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => [
            array_filter([
                '@type' => 'MusicComposition',
                'name' => $piece->title,
                'url' => $canonicalUrl,
                'description' => $profile['summary'],
                'composer' => $piece->composer ? array_filter(['@type' => 'Person', 'name' => $piece->composer, 'url' => $piece->composerUrl]) : null,
                'keywords' => $profile['tags'] !== [] ? implode(', ', $profile['tags']) : null,
            ]),
            ['@type' => 'BreadcrumbList', 'itemListElement' => $breadcrumbItems],
        ],
    ];
@endphp

<x-layouts.catalog :title="$pageTitle" :description="$metaDescription" :canonical="$canonicalUrl">
    <x-slot:head>
        <x-public.json-ld :data="$structuredData" />
    </x-slot:head>

    <div class="flex flex-col gap-10">
        <section class="flex flex-col gap-4">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('trends')">{{ __('Trends') }}</flux:breadcrumbs.item>
                @if ($piece->composerUrl)
                    <flux:breadcrumbs.item :href="$piece->composerUrl">{{ $piece->composer }}</flux:breadcrumbs.item>
                @endif
                <flux:breadcrumbs.item>{{ $piece->title }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div class="flex flex-col gap-1">
                <flux:heading level="1" size="xl" class="text-3xl! lg:text-4xl!">{{ $piece->title }}</flux:heading>
                @if ($piece->composer || $piece->arranger)
                    <flux:text class="text-base">
                        @if ($piece->composer)
                            {{ __('by') }} <x-public.name-link :name="$piece->composer" :url="$piece->composerUrl" />
                        @endif
                        @if ($piece->composer && $piece->arranger)
                            ·
                        @endif
                        @if ($piece->arranger)
                            {{ __('arr.') }} <x-public.name-link :name="$piece->arranger" :url="$piece->arrangerUrl" />
                        @endif
                    </flux:text>
                @endif
            </div>

            <x-public.stat-grid :stats="[
                ['value' => $piece->schoolCount, 'label' => __('Schools')],
                ['value' => $piece->performanceCount, 'label' => __('Performances')],
                ['value' => $profile['voicings'] !== [] ? implode(', ', $profile['voicings']) : '—', 'label' => __('Voicing')],
                ['value' => $profile['difficulty'] ?? '—', 'label' => __('Difficulty')],
            ]" />
        </section>

        @if ($profile['summary'] || $profile['tags'] !== [])
            <section class="flex flex-col gap-4" aria-labelledby="about-piece">
                <flux:heading level="2" size="lg" id="about-piece">{{ __('About This Piece') }}</flux:heading>

                @if ($profile['summary'])
                    <div class="flex flex-col gap-1">
                        <flux:text class="max-w-3xl text-base">{{ $profile['summary'] }}</flux:text>
                        <flux:text size="sm" class="italic">{{ __('Summary generated with AI.') }}</flux:text>
                    </div>
                @endif

                @if ($profile['tags'] !== [])
                    <div class="flex flex-wrap gap-2">
                        @foreach ($profile['tags'] as $tag)
                            <flux:badge size="sm" wire:key="tag-{{ $loop->index }}">{{ $tag }}</flux:badge>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        <div class="grid gap-10 lg:grid-cols-2">
            @if ($profile['performancesBySchoolYear'] !== [])
                <section class="flex flex-col gap-4" aria-labelledby="by-year">
                    <flux:heading level="2" size="lg" id="by-year">{{ __('Performances by School Year') }}</flux:heading>
                    <x-public.school-year-table :years="$profile['performancesBySchoolYear']" />
                </section>
            @endif

            @if ($profile['alsoProgrammed']->isNotEmpty())
                <section class="flex flex-col gap-4" aria-labelledby="also-programmed">
                    <div class="flex flex-col gap-1">
                        <flux:heading level="2" size="lg" id="also-programmed">{{ __('Often Programmed With') }}</flux:heading>
                        <flux:text>{{ __('Pieces that appear on the same concert programs.') }}</flux:text>
                    </div>
                    <ul class="flex flex-col gap-2">
                        @foreach ($profile['alsoProgrammed'] as $related)
                            <li wire:key="also-{{ $related->id }}">
                                <flux:link :href="$related->url">{{ $related->title }}</flux:link>
                                @if ($related->composer)
                                    <flux:text inline size="sm">— {{ $related->composer }}</flux:text>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <x-public.data-notice :minimum-schools="config('trends.minimum_schools')" />
    </div>
</x-layouts.catalog>
