<div>
    <div class="mx-auto max-w-4xl space-y-8">
        <div>
            <flux:heading size="xl">{{ __('Create Digital Program') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Choose how you would like to build the digital program.') }}</flux:text>
        </div>

        {{-- Path chooser --}}
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    <flux:icon.map class="size-8 text-blue-500" />
                    <flux:heading size="lg">{{ __('Guided') }}</flux:heading>
                </div>
                <flux:text class="grow">
                    {{ __('Step-by-step wizard. Perfect if you are setting up a digital program for the first time or prefer a guided experience.') }}
                </flux:text>
                <flux:button :href="route('digital-programs.create.guided')" variant="primary" wire:navigate class="mt-auto w-full">
                    {{ __('Start Guided Wizard') }}
                </flux:button>
            </div>

            <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    <flux:icon.bolt class="size-8 text-amber-500" />
                    <flux:heading size="lg">{{ __('Power User') }}</flux:heading>
                </div>
                <flux:text class="grow">
                    {{ __('All fields on one page. Best for directors who know exactly what they want and prefer to work quickly without step-by-step prompts.') }}
                </flux:text>
                <flux:button :href="route('digital-programs.create.pro')" variant="primary" wire:navigate class="mt-auto w-full">
                    {{ __('Open Power User Form') }}
                </flux:button>
            </div>
        </div>

        {{-- Link to index for managing all programs --}}
        <div class="flex items-center justify-between rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <div>
                <flux:heading size="sm">{{ __('Manage Digital Programs') }}</flux:heading>
                <flux:text class="text-sm">{{ __('View, publish, unpublish, and delete all digital programs.') }}</flux:text>
            </div>
            <flux:button :href="route('digital-programs.index')" variant="subtle" icon="ticket" wire:navigate>
                {{ __('View All') }}
            </flux:button>
        </div>

        {{-- Recent digital programs (Founder-wide view) --}}
        @if($recentDigitalPrograms->isNotEmpty())
            <div>
                <flux:heading size="lg" class="mb-3">{{ __('Recent Digital Programs (All Users)') }}</flux:heading>
                <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-4 py-2 text-start text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Event') }}</th>
                                <th class="px-4 py-2 text-start text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Director') }}</th>
                                <th class="px-4 py-2 text-start text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Theme') }}</th>
                                <th class="px-4 py-2 text-start text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                                <th class="px-4 py-2 text-start text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Web Address') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($recentDigitalPrograms as $dp)
                                <tr wire:key="dp-{{ $dp->id }}" class="text-sm">
                                    <td class="px-4 py-2 text-zinc-900 dark:text-zinc-100">
                                        {{ $dp->program?->event_name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2 text-zinc-600 dark:text-zinc-400">{{ $dp->user->name }}</td>
                                    <td class="px-4 py-2 text-zinc-600 dark:text-zinc-400">{{ $dp->theme }}</td>
                                    <td class="px-4 py-2">
                                        @if($dp->is_published)
                                            <flux:badge color="green" size="sm">{{ __('Published') }}</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">{{ __('Draft') }}</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2">
                                        @if($dp->is_published)
                                            <a href="{{ route('program.public', $dp->slug) }}" target="_blank"
                                                class="font-mono text-xs text-blue-600 hover:underline dark:text-blue-400">
                                                /p/{{ $dp->slug }}
                                            </a>
                                        @else
                                            <span class="font-mono text-xs text-zinc-400">/p/{{ $dp->slug }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <flux:callout icon="information-circle">
                <flux:callout.text>{{ __('No digital programs have been created yet. Use one of the paths above to create the first one.') }}</flux:callout.text>
            </flux:callout>
        @endif
    </div>
</div>
