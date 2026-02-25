<x-layouts.app :title="__('Documentation')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:heading size="xl">{{ __('Schools Guide') }}</flux:heading>

        <div class="max-w-3xl space-y-8">

            <section>
                <flux:heading size="lg">{{ __('Overview') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('The Schools page lists every school that has submitted concert programs, along with summary counts for programs, ensembles, composers/arrangers, and songs.') }}
                </flux:text>
            </section>

            <section>
                <flux:heading size="lg">{{ __('My vs. All') }}</flux:heading>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3">
                        <flux:icon name="user" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('My') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Shows only schools associated with your account.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="globe-alt" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('All') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Shows every participating school. Unlocked once you have uploaded programs.') }}</flux:text>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <flux:heading size="lg">{{ __('Sorting Columns') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Click any column header to sort. The sortable columns are:') }}
                </flux:text>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3">
                        <flux:icon name="academic-cap" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('School Name') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Sort alphabetically by school name.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="hashtag" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Programs, Ensembles, Composers/Arrangers, Song Titles') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Sort by any of the numeric count columns to see which schools have the most activity.') }}</flux:text>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>
</x-layouts.app>
