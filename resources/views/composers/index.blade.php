@php
    $metaDescription = 'Choral composers and arrangers whose music has been performed at '.$minimumSchools
        .' or more schools, with how often their pieces appear in school concert programs.';

    $groupedArtists = $artists->groupBy(function (\App\Services\TrendingArtist $artist): string {
        $initial = mb_strtoupper(mb_substr(\Illuminate\Support\Str::ascii($artist->name), 0, 1));

        return ctype_alpha($initial) ? $initial : '#';
    })->sortKeys();

    $breadcrumbs = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trends', 'item' => route('trends')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Composers & Arrangers', 'item' => route('composers.index')],
        ],
    ];
@endphp

<x-layouts.catalog :title="__('Choral Composers & Arrangers')" :description="$metaDescription">
    <x-slot:head>
        <x-public.json-ld :data="$breadcrumbs" />
    </x-slot:head>

    <div class="flex flex-col gap-10">
        <section class="flex flex-col gap-4">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('trends')">{{ __('Trends') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Composers & Arrangers') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <flux:heading level="1" size="xl" class="text-3xl! lg:text-4xl!">{{ __('Choral Composers & Arrangers') }}</flux:heading>
            <flux:text class="max-w-3xl text-base">
                {{ __(':count composers and arrangers whose music appears in concert programs from at least :min different schools.', ['count' => $artists->count(), 'min' => $minimumSchools]) }}
            </flux:text>
        </section>

        @if ($artists->isEmpty())
            <flux:callout icon="musical-note" :heading="__('Not enough data yet')" :text="__('Composers appear here once enough schools have performed their music. Check back soon.')" />
        @else
            <div class="flex flex-col gap-8">
                @foreach ($groupedArtists as $initial => $group)
                    <section class="flex flex-col gap-3" aria-label="{{ $initial }}" wire:key="initial-{{ $initial }}">
                        <flux:heading level="2" size="lg">{{ $initial }}</flux:heading>
                        <ul class="grid gap-x-8 gap-y-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($group as $artist)
                                <li class="flex items-baseline justify-between gap-3" wire:key="artist-{{ $artist->id }}">
                                    <flux:link :href="$artist->url">{{ $artist->name }}</flux:link>
                                    <flux:text size="sm" class="shrink-0">{{ trans_choice(':count school|:count schools', $artist->schoolCount) }}</flux:text>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        @endif

        <x-public.data-notice :minimum-schools="$minimumSchools" />
    </div>
</x-layouts.catalog>
