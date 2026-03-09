<div>
    <div class="mx-auto max-w-3xl space-y-6">
        <flux:heading size="xl">{{ __('Quick Tips') }}</flux:heading>

        <flux:text>{{ __('Short feature tips to help you get the most out of ChoirTrends.') }}</flux:text>

        @if ($tips->isEmpty())
            <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text>{{ __('No quick tips available yet. Check back soon!') }}</flux:text>
            </div>
        @else
            <div class="space-y-2">
                @foreach ($tips as $tip)
                    <div wire:key="tip-{{ $tip->id }}" class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <button
                            wire:click="toggleTip({{ $tip->id }})"
                            class="flex w-full items-center justify-between bg-white px-4 py-3 text-left transition-colors hover:bg-zinc-50 dark:bg-zinc-900 dark:hover:bg-zinc-800"
                        >
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $tip->header }}</span>
                            <flux:icon
                                name="{{ $openTipId === $tip->id ? 'chevron-up' : 'chevron-down' }}"
                                class="size-5 text-zinc-400"
                            />
                        </button>

                        @if ($openTipId === $tip->id)
                            <div class="border-t border-zinc-200 bg-white px-4 py-4 dark:border-zinc-700 dark:bg-zinc-900">
                                @if ($tip->introduction)
                                    <div class="prose mb-4 max-w-none dark:prose-invert">
                                        {!! $tip->introduction !!}
                                    </div>
                                @endif

                                <div class="prose max-w-none rounded-lg border-l-4 border-blue-500 bg-zinc-50 p-4 dark:bg-zinc-800 dark:prose-invert">
                                    {!! $tip->tip !!}
                                </div>

                                @if ($tip->resolvedCallToAction())
                                    <div class="mt-4 rounded-lg bg-blue-50 p-3 text-sm text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                        {{ $tip->resolvedCallToAction() }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if ($hasNextTip)
            <div class="text-center">
                <flux:button wire:click="viewNextTip" variant="primary">
                    {{ __('Next Quick Tip') }}
                </flux:button>
            </div>
        @endif
    </div>
</div>
