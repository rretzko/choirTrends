<x-layouts.app :title="__('Documentation')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:heading size="xl">{{ __('Documentation') }}</flux:heading>

        <div class="max-w-3xl space-y-8">

            <section>
                <flux:heading size="lg">{{ __('Welcome to ChoirTrends') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Submit your past Choral concert programs to discover trends in repertoire, composers, and arrangers across the choral community.') }}
                </flux:text>
            </section>

            <section>
                <flux:heading size="lg">{{ __('Sidebar Links') }}</flux:heading>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3">
                        <flux:icon name="home" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Dashboard') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Your home page with a summary of your activity and the setup checklist.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="document-text" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Programs') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Click the drop-down to browse all submitted concert programs. Click on an individual school name to see their contribution. Filter the program selections by one, many or all schools.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="arrow-up-tray" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Add Program') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Upload a photo (jpg, jpeg, png) or PDF of your concert program. ChoirTrends extracts the details automatically. Note: Your student rosters are NOT uploaded.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="musical-note" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Composers/Arrangers') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Explore the composers and arrangers found across all programs. Click the numbered buttons under "Song Titles" to see a list of the songs attributed to the composer/arranger.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="user-group" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Ensembles') }}</flux:text>
                            <flux:text class="text-sm">{{ __('View the performing ensembles from submitted programs, including the type of ensemble and if the ensemble primarily performs a cappella literature.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="academic-cap" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Schools') }}</flux:text>
                            <flux:text class="text-sm">{{ __('See the list of schools that have uploaded programs.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="queue-list" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Song Titles') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Discover the most popular repertoire across all programs.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="bug-ant" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Feedback') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Hit a bug, have an enhancement suggestion, want to give us a pat on the back, or just make a comment?  Click the Feedback link to contact us directly!') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="moon" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Dark Mode') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Change the appearance of the site to your personal preference; light or dark.') }}</flux:text>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <flux:heading size="lg">{{ __('Page Conventions') }}</flux:heading>
                <div class="mt-4 space-y-3">
                    <div>
                        <flux:text class="font-semibold">{{ __('Sortable Columns') }}</flux:text>
                        <flux:text class="text-sm">{{ __('Click column headers to sort. Chevron icons show the current sort direction.') }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="font-semibold">{{ __('Filter Toggles') }}</flux:text>
                        <flux:text class="text-sm">{{ __('Use the My/All buttons to switch between your data and all community data. Search bars let you filter by keyword. School drop-down let you select one, many, or all schools. For Composers/Arrangers and Song Titles, search by names and titles.') }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="font-semibold">{{ __('Edit Icons') }}</flux:text>
                        <flux:text class="text-sm">{{ __('Look for the pencil icon (far right) on items you own to make edits.') }}</flux:text>
                    </div>
                </div>
            </section>

        </div>
    </div>
</x-layouts.app>
