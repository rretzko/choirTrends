<x-layouts.catalog :title="__('Choral Programming Reports')" :description="__('School-year reports on what school choirs sang: the most-performed pieces, composers and arrangers, drawn from concert programs shared by choral directors.')">
    <div class="flex flex-col gap-8">
        <section class="flex flex-col gap-3">
            <flux:heading level="1" size="xl" class="text-3xl! lg:text-4xl!">{{ __('Choral Programming Reports') }}</flux:heading>
            <flux:text class="max-w-3xl text-base">
                {{ __('A yearly look at what school choirs actually sang, drawn from concert programs directors shared on :app.', ['app' => config('app.name')]) }}
            </flux:text>
        </section>

        @if ($schoolYears->isEmpty())
            <flux:callout icon="document-chart-bar" :heading="__('No reports yet')" :text="__('A report is published once enough schools have shared programs for a school year.')" />
        @else
            <ul class="flex flex-col gap-3">
                @foreach ($schoolYears as $schoolYear)
                    <li wire:key="report-{{ $loop->index }}">
                        <flux:link :href="$schoolYear['url']" class="text-lg">{{ __(':year Choral Programming Report', ['year' => $schoolYear['label']]) }}</flux:link>
                    </li>
                @endforeach
            </ul>
        @endif

        <x-public.data-notice :minimum-schools="config('trends.minimum_schools')" />
    </div>
</x-layouts.catalog>
