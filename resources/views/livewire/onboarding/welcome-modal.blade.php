<div>
    @if ($showWelcomeModal)
        <flux:modal wire:model.self="showWelcomeModal" :dismissible="false" class="md:w-96">
            <div class="space-y-6">
                <div class="text-center">
                    <flux:heading size="lg">{{ __('Welcome to choirTrends!') }}</flux:heading>
                    <flux:text class="mt-2">
                        {{ __('Track your choral programs, discover trends, and connect with a choral director community.') }}
                    </flux:text>
                </div>

                <div class="space-y-4">
                    <flux:text class="font-medium">{{ __("Here's how to get started:") }}</flux:text>

                    <div class="flex items-start gap-3">
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900">
                            <flux:icon name="academic-cap" variant="mini" class="size-4 text-amber-600 dark:text-amber-400" />
                        </div>
                        <flux:text class="text-sm">{{ __('Add your school to your profile') }}</flux:text>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                            <flux:icon name="arrow-up-tray" variant="mini" class="size-4 text-green-600 dark:text-green-400" />
                        </div>
                        <flux:text class="text-sm">{{ __('Upload a concert program') }}</flux:text>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                            <flux:icon name="shield-check" variant="mini" class="size-4 text-blue-600 dark:text-blue-400" />
                        </div>
                        <flux:text class="text-sm">{{ __('Set your privacy preferences') }}</flux:text>
                    </div>
                </div>

                <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">
                    {{ __('Complete these steps to unlock community trends and insights.') }}
                </flux:text>

                <flux:button wire:click="dismiss" variant="primary" class="w-full">
                    {{ __("Let's go!") }}
                </flux:button>
            </div>
        </flux:modal>
    @endif
</div>
