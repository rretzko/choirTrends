<x-layouts.app :title="__('Documentation')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:heading size="xl">{{ __('Programs Guide') }}</flux:heading>

        <div class="max-w-3xl space-y-8">

            <section>
                <flux:heading size="lg">{{ __('Overview') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('The Programs page lists every concert program you and other participating directors have submitted. Use it to browse repertoire, review event details, and manage your own entries.') }}
                </flux:text>
            </section>

            <section>
                <flux:heading size="lg">{{ __('Filtering by School/Org') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Click the school/org drop-down above the table to narrow the list:') }}
                </flux:text>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3">
                        <flux:icon name="building-library" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('All Schools/Orgs') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Shows every program from all participating directors.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="check" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Select Schools/Orgs') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Check one or more schools/orgs to see only their programs. The drop-down stays open so you can select multiple at once.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="tag" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Type') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Use the Type drop-down next to it to narrow the list to a specific kind of organization: High School, Middle School, Elementary School, Community Choir, Church Choir, University Choir, Honors Choir, or Other.') }}</flux:text>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <flux:heading size="lg">{{ __('Sorting Columns') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Click any column header to sort the table by that column. Click again to reverse the sort direction. A chevron icon shows the current sort order. The sortable columns are:') }}
                </flux:text>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3">
                        <flux:icon name="academic-cap" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('School/Org') }}</flux:text>
                            <flux:text class="text-sm">{{ __('The school or organization that submitted the program.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="document-text" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Program Name') }}</flux:text>
                            <flux:text class="text-sm">{{ __('The name of the concert event.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="calendar" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Program Date') }}</flux:text>
                            <flux:text class="text-sm">{{ __('When the concert took place.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="user" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Director') }}</flux:text>
                            <flux:text class="text-sm">{{ __('The choral director for the program.') }}</flux:text>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <flux:heading size="lg">{{ __('Viewing Program Details') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Click a program name in the table to open a detail panel. The panel shows:') }}
                </flux:text>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3">
                        <flux:icon name="information-circle" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Event Information') }}</flux:text>
                            <flux:text class="text-sm">{{ __('The program date, school/org, and director.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="user-group" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Ensembles & Repertoire') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Each ensemble is listed with its songs, composers, and arrangers.') }}</flux:text>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <flux:heading size="lg">{{ __('Editing Your Programs') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Programs you submitted show a pencil icon in the Actions column on the far right. Click it to edit the event name, date, school/org, director, ensembles, or songs. You can only edit programs you own.') }}
                </flux:text>
            </section>

        </div>
    </div>
</x-layouts.app>
