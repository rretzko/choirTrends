<x-layouts.app :title="__('Documentation')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:heading size="xl">{{ __('Ensembles Guide') }}</flux:heading>

        <div class="max-w-3xl space-y-8">

            <section>
                <flux:heading size="lg">{{ __('Overview') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('The Ensembles page shows every performing ensemble extracted from submitted concert programs. Ensembles are grouped by school so you can see which groups belong together.') }}
                </flux:text>
            </section>

            <section>
                <flux:heading size="lg">{{ __('My vs. All') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Use the toggle buttons at the top:') }}
                </flux:text>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3">
                        <flux:icon name="user" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('My') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Shows only ensembles from your own programs.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="globe-alt" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('All') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Shows ensembles from all participating directors. Unlocked once you have uploaded programs.') }}</flux:text>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <flux:heading size="lg">{{ __('Ensemble Attributes') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Each ensemble has two attributes you can set for ensembles you own:') }}
                </flux:text>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3">
                        <flux:icon name="adjustments-horizontal" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('Voice Type') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Choose from SATB, Soprano/Alto, Tenor/Bass, or Unknown using the drop-down. This helps categorize the ensemble for trend analysis.') }}</flux:text>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <flux:icon name="check" variant="outline" class="mt-0.5 size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                        <div>
                            <flux:text class="font-semibold">{{ __('A Cappella') }}</flux:text>
                            <flux:text class="text-sm">{{ __('Check this box if the ensemble primarily performs a cappella literature.') }}</flux:text>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <flux:heading size="lg">{{ __('Editing Ensembles') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('For ensembles you own, click the pencil icon in the Actions column to edit the ensemble name. You can also change the voice type and a cappella setting directly in the table without opening the edit page.') }}
                </flux:text>
            </section>

        </div>
    </div>
</x-layouts.app>
