<x-layouts.app :title="__('Documentation')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:heading size="xl">{{ __('Schools/Orgs Guide') }}</flux:heading>

        <div class="max-w-3xl space-y-8">

            <section>
                <flux:heading size="lg">{{ __('Overview') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('The Schools/Orgs page lists every school, church choir, community choir, or other organization that has submitted concert programs, along with summary counts for programs, ensembles, composers/arrangers, and songs.') }}
                </flux:text>
            </section>

            <section>
                <flux:heading size="lg">{{ __('My vs. All') }}</flux:heading>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3">
                        <flux:icon name="user" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('My') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Shows only schools/orgs associated with your account.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="globe-alt" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('All') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Shows every participating school or organization. Unlocked once you have uploaded programs.') }}</flux:text>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <flux:heading size="lg">{{ __('Filtering by Type') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Use the Type drop-down to narrow the list to a specific kind of organization: High School, Middle School, Elementary School, Community Choir, Church Choir, University Choir, Honors Choir, or Other.') }}
                </flux:text>
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
                            <flux:text class="font-semibold">{{ __('Name') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Sort alphabetically by name.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="tag" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Type') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Sort by type of school or organization.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="hashtag" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Programs, Ensembles, Composers/Arrangers, Song Titles') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Sort by any of the numeric count columns to see which schools/orgs have the most activity.') }}</flux:text>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>
</x-layouts.app>
