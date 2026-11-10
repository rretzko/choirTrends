@php
    $label = $report['label'];
    $stats = $report['stats'];
    $pageTitle = __(':year Choral Programming Report', ['year' => $label]);
    $topComposers = $report['composers']->take(3)->pluck('name')->join(', ', ' and ');

    $metaDescription = "What school choirs sang in {$label}: ".number_format($stats['programs']).' concert programs from '
        .number_format($stats['schools']).' schools'
        .($topComposers !== '' ? ", led by music from {$topComposers}" : '')
        .'. Most-performed pieces, composers and arrangers.';

    $countedRegions = collect($report['regions'])->whereNotNull('schools');
    $otherRegions = collect($report['regions'])->whereNull('schools')->pluck('name');

    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Dataset',
                'name' => $pageTitle,
                'description' => $metaDescription,
                'url' => $canonicalUrl,
                'temporalCoverage' => $report['startYear'].'-09/'.($report['startYear'] + 1).'-07',
                'creator' => ['@type' => 'Organization', 'name' => config('app.name'), 'url' => config('app.url')],
                'isAccessibleForFree' => true,
                'keywords' => ['choral music', 'choir repertoire', 'concert programs', 'school choirs', $label],
                'variableMeasured' => ['concert programs', 'schools', 'performances per piece', 'schools per composer'],
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Reports', 'item' => route('reports.index')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $label, 'item' => $canonicalUrl],
                ],
            ],
        ],
    ];
@endphp

<x-layouts.catalog :title="$pageTitle" :description="$metaDescription" :canonical="$canonicalUrl">
    <x-slot:head>
        <x-public.json-ld :data="$structuredData" />
    </x-slot:head>

    <div class="flex flex-col gap-12">
        <section class="flex flex-col gap-4">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('reports.index')">{{ __('Reports') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $label }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div class="flex flex-col gap-2">
                <flux:heading level="1" size="xl" class="text-3xl! lg:text-4xl!">{{ $pageTitle }}</flux:heading>
                <flux:text class="max-w-3xl text-base">
                    {{ __('What school choirs actually sang from September :start through July :end, drawn from concert programs directors shared on :app.', ['start' => $report['startYear'], 'end' => $report['startYear'] + 1, 'app' => config('app.name')]) }}
                </flux:text>
                <flux:text size="sm">{{ __('Data as of :date. Totals update as directors add programs from this school year.', ['date' => now()->format('F j, Y')]) }}</flux:text>
            </div>

            <x-public.stat-grid :stats="[
                ['value' => $stats['programs'], 'label' => __('Concert programs')],
                ['value' => $stats['schools'], 'label' => __('Schools')],
                ['value' => $stats['titles'], 'label' => __('Different titles')],
                ['value' => $stats['performances'], 'label' => __('Performances')],
            ]" />
        </section>

        @if ($report['highlights'] !== [])
            <section class="flex flex-col gap-3" aria-labelledby="highlights">
                <flux:heading level="2" size="lg" id="highlights">{{ __('Highlights') }}</flux:heading>
                <ul class="flex max-w-3xl list-disc flex-col gap-2 ps-5 text-zinc-700 dark:text-zinc-300">
                    @foreach ($report['highlights'] as $highlight)
                        <li wire:key="highlight-{{ $loop->index }}">{{ $highlight }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="flex flex-col gap-4" aria-labelledby="pieces">
            <div class="flex flex-col gap-1">
                <flux:heading level="2" size="lg" id="pieces">{{ __('Most-Performed Pieces') }}</flux:heading>
                <flux:text>{{ __('Pieces sung by at least :min different schools during the year, ranked by number of schools.', ['min' => $minimumSchools]) }}</flux:text>
            </div>
            @if ($report['pieces']->isEmpty())
                <flux:text>{{ __('No single piece reached :min schools this year.', ['min' => $minimumSchools]) }}</flux:text>
            @else
                <x-public.piece-table :pieces="$report['pieces']" key-prefix="report-piece" />
            @endif
        </section>

        <div class="grid gap-12 lg:grid-cols-2">
            @foreach ([
                ['id' => 'composers', 'heading' => __('Most-Programmed Composers'), 'artists' => $report['composers']],
                ['id' => 'arrangers', 'heading' => __('Most-Programmed Arrangers'), 'artists' => $report['arrangers']],
            ] as $group)
                @if ($group['artists']->isNotEmpty())
                    <section class="flex flex-col gap-4" aria-labelledby="{{ $group['id'] }}" wire:key="group-{{ $group['id'] }}">
                        <flux:heading level="2" size="lg" id="{{ $group['id'] }}">{{ $group['heading'] }}</flux:heading>
                        <x-public.artist-table :artists="$group['artists']" :key-prefix="$group['id']" />
                    </section>
                @endif
            @endforeach
        </div>

        @if ($report['winterComposers']->isNotEmpty() || $report['springComposers']->isNotEmpty())
            <section class="flex flex-col gap-4" aria-labelledby="seasons">
                <div class="flex flex-col gap-1">
                    <flux:heading level="2" size="lg" id="seasons">{{ __('Winter vs. Spring') }}</flux:heading>
                    <flux:text>{{ __('Top composers in winter concerts (September–January) and spring concerts (February–July).') }}</flux:text>
                </div>
                <div class="grid gap-8 md:grid-cols-2">
                    @foreach ([
                        ['id' => 'winter', 'heading' => __('Winter'), 'artists' => $report['winterComposers']],
                        ['id' => 'spring', 'heading' => __('Spring'), 'artists' => $report['springComposers']],
                    ] as $season)
                        <div class="flex flex-col gap-2" wire:key="season-{{ $season['id'] }}">
                            <flux:heading level="3">{{ $season['heading'] }}</flux:heading>
                            @if ($season['artists']->isEmpty())
                                <flux:text>{{ __('Not enough data.') }}</flux:text>
                            @else
                                <x-public.artist-table :artists="$season['artists']" :key-prefix="$season['id']" :show-pieces="false" />
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="grid gap-12 lg:grid-cols-2">
            <section class="flex flex-col gap-4" aria-labelledby="calendar">
                <div class="flex flex-col gap-1">
                    <flux:heading level="2" size="lg" id="calendar">{{ __('Concert Calendar') }}</flux:heading>
                    <flux:text>{{ __('Concert programs per month.') }}</flux:text>
                </div>
                <x-public.month-bars :months="$report['months']" :label="__('Bar chart of concert programs per month, :year school year', ['year' => $label])" />
            </section>

            @if ($report['regions'] !== [])
                <section class="flex flex-col gap-4" aria-labelledby="where">
                    <flux:heading level="2" size="lg" id="where">{{ __('Where the Programs Came From') }}</flux:heading>
                    <ul class="flex flex-col gap-2">
                        @foreach ($countedRegions as $region)
                            <li class="flex items-baseline justify-between gap-4" wire:key="region-{{ $loop->index }}">
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $region['name'] }}</span>
                                <flux:text>{{ trans_choice(':count school|:count schools', $region['schools']) }}</flux:text>
                            </li>
                        @endforeach
                    </ul>
                    @if ($otherRegions->isNotEmpty())
                        <flux:text>{{ __('Also: :regions.', ['regions' => $otherRegions->join(', ', ' and ')]) }}</flux:text>
                    @endif
                </section>
            @endif
        </div>

        <flux:text>
            <flux:link :href="route('trends')">{{ __('See all-time trends') }} →</flux:link>
        </flux:text>

        <x-public.data-notice :minimum-schools="$minimumSchools" />
    </div>
</x-layouts.catalog>
