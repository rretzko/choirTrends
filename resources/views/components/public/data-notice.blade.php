@props(['minimumSchools'])

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
