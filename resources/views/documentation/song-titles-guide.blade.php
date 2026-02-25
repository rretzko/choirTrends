<x-layouts.app :title="__('Documentation')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:heading size="xl">{{ __('Song Titles Guide') }}</flux:heading>

        <div class="max-w-3xl space-y-8">

            <section>
                <flux:heading size="lg">{{ __('Overview') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('The Song Titles page shows every piece of repertoire extracted from submitted programs. Use it to discover what the choral community is performing and find sample recordings.') }}
                </flux:text>
            </section>

            <section>
                <flux:heading size="lg">{{ __('My vs. All') }}</flux:heading>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3">
                        <flux:icon name="user" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('My') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Shows only songs from your own programs.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="globe-alt" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('All') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Shows songs from all participating directors. Unlocked once you have uploaded programs.') }}</flux:text>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <flux:heading size="lg">{{ __('Understanding the Table') }}</flux:heading>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3">
                        <flux:icon name="queue-list" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Song Title') }}</flux:text>
                            <flux:text class="text-sm">{{ __('The name of the piece as it appears on the concert program.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="musical-note" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Composer & Arranger') }}</flux:text>
                            <flux:text class="text-sm">{{ __('The credited composer and arranger, if available.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="chart-bar" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Programmed') }}</flux:text>
                            <flux:text class="text-sm">{{ __('The number of times this piece has appeared across all submitted programs. Higher numbers indicate more popular repertoire.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="play" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Samples') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Click the YouTube icon to search for a sample performance of the song on YouTube. Results open in a new tab.') }}</flux:text>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <flux:heading size="lg">{{ __('Searching & Sorting') }}</flux:heading>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3">
                        <flux:icon name="magnifying-glass" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Search') }}</flux:text>
                            <flux:text class="text-sm">{{ __('The search bar filters by song title, composer name, or arranger name.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="arrows-up-down" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Sort') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Click any column header (Song Title, Composer, Arranger, or Programmed) to sort. Click again to reverse.') }}</flux:text>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>
</x-layouts.app>
