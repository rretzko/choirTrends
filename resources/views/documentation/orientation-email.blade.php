<x-layouts.app :title="__('Orientation Email')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:heading size="xl">{{ __('Orientation Email') }}</flux:heading>

        <div class="max-w-3xl space-y-8">

            <section>
                <flux:heading size="lg" class="!text-blue-500">{{ __('Welcome to ChoirTrends, :name!', ['name' => auth()->user()->name]) }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Submit your past concert programs to discover trends in repertoire, composers, and arrangers across the choral community.') }}
                </flux:text>
            </section>

            <section>
                <flux:heading size="lg" class="!text-blue-500">{{ __('Sidebar Links') }}</flux:heading>
                <div class="mt-4 space-y-3 rounded-lg border-l-4 border-blue-500 bg-zinc-50 p-4 dark:bg-zinc-800">
                    <flux:text>
                        <strong>{{ __('Dashboard') }}</strong> &mdash; {{ __('Your home page with a summary of your activity and the setup checklist.') }}
                    </flux:text>
                    <flux:text>
                        <strong>{{ __('Programs') }}</strong> &mdash; {{ __('Browse all submitted concert programs. Filter the program selections by one, many or all schools.') }}
                    </flux:text>
                    <flux:text>
                        <strong>{{ __('Add Program') }}</strong> &mdash; {{ __('Upload a photo or PDF of a concert program. Our AI extracts the details automatically.  Note: Your student rosters are NOT uploaded.') }}
                    </flux:text>
                    <flux:text>
                        <strong>{{ __('Composers/Arrangers') }}</strong> &mdash; {{ __('Explore the composers and arrangers found across all programs. Click the numbered buttons under the "Song Titles" column to see a list of the songs attributed to the composer/arranger.') }}
                    </flux:text>
                    <flux:text>
                        <strong>{{ __('Ensembles') }}</strong> &mdash; {{ __('View the performing ensembles from submitted programs, including the type of ensemble and if the ensemble primarily performs a cappella literature.') }}
                    </flux:text>
                    <flux:text>
                        <strong>{{ __('Schools') }}</strong> &mdash; {{ __('See the schools associated with programs and directors, including the number of programs, ensembles, composers/arrangers and song titles attributed to the school.') }}
                    </flux:text>
                    <flux:text>
                        <strong>{{ __('Song Titles') }}</strong> &mdash; {{ __('Discover the most popular song titles across all programs and how many times the songs have been performed.') }}
                    </flux:text>
                    <flux:text>
                        <strong>{{ __('Feedback') }}</strong> &mdash; {{ __('Hit a bug, have an enhancement suggestion, want to give us a pat on the back, or just make a comment?  Click the Feedback link to contact us directly!') }}
                    </flux:text>
                    <flux:text>
                        <strong>{{ __('Dark Mode') }}</strong> &mdash; {{ __('Change the appearance of the site to your personal preference; light or dark.') }}
                    </flux:text>
                </div>
            </section>

            <section>
                <flux:heading size="lg" class="!text-blue-500">{{ __('Page Conventions') }}</flux:heading>
                <div class="mt-4 space-y-3 rounded-lg border-l-4 border-blue-500 bg-zinc-50 p-4 dark:bg-zinc-800">
                    <flux:text>
                        <strong>{{ __('Sortable Columns') }}</strong> &mdash; {{ __('Click column headers to sort. Chevron icons show the current sort direction.') }}
                    </flux:text>
                    <flux:text>
                        <strong>{{ __('Filter Toggles') }}</strong> &mdash; {{ __('Use the My/All buttons to switch between your data and all community data. Search bars let you filter by keyword. School drop-down let you select one, many, or all schools.') }}
                    </flux:text>
                    <flux:text>
                        <strong>{{ __('Edit Icons') }}</strong> &mdash; {{ __('Look for the pencil icon on items you own to make edits.') }}
                    </flux:text>
                </div>
            </section>

            <section class="border-t border-zinc-200 pt-6 dark:border-zinc-700">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('You can revisit this guide anytime from Documentation > Site Guide in the sidebar.') }}
                </flux:text>
                <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Thank you for using ChoirTrends!') }}
                </flux:text>
            </section>

        </div>
    </div>
</x-layouts.app>
