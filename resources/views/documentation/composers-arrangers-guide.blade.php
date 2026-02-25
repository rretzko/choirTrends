<x-layouts.app :title="__('Documentation')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:heading size="xl">{{ __('Composers & Arrangers Guide') }}</flux:heading>

        <div class="max-w-3xl space-y-8">

            <section>
                <flux:heading size="lg">{{ __('Overview') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('The Composers & Arrangers page lists every composer and arranger found across all submitted concert programs. Use it to discover who is being performed most often and explore their repertoire.') }}
                </flux:text>
            </section>

            <section>
                <flux:heading size="lg">{{ __('My vs. All') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Use the toggle buttons at the top of the page:') }}
                </flux:text>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3">
                        <flux:icon name="user" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('My') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Shows only composers and arrangers from your own submitted programs.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="globe-alt" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('All') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Shows composers and arrangers from all participating directors. This is unlocked once you have uploaded programs.') }}</flux:text>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <flux:heading size="lg">{{ __('Viewing Repertoire') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('The "Song Titles" column shows how many songs are attributed to each artist. Click the numbered button to open a panel listing every song title and whether the artist is credited as composer, arranger, or both.') }}
                </flux:text>
            </section>

            <section>
                <flux:heading size="lg">{{ __('Searching & Sorting') }}</flux:heading>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3">
                        <flux:icon name="magnifying-glass" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Search') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Type in the search bar to filter artists by name.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="arrows-up-down" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Sort') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Click the "Song Titles" or "Artist Name" column headers to sort. Click again to reverse the direction.') }}</flux:text>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>
</x-layouts.app>
