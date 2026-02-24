<div>
    @if (! $isDismissed && ! $isComplete)
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-6 dark:border-blue-800 dark:bg-blue-950">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                        <flux:icon name="clipboard-document-list" class="size-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <flux:heading size="lg">{{ __('Get Started') }}</flux:heading>
                </div>
                <flux:button wire:click="dismiss" variant="ghost" size="sm">
                    {{ __('Dismiss') }}
                </flux:button>
            </div>

            <div class="mt-4 space-y-3">
                <div class="flex items-center gap-3">
                    @if ($hasSchool)
                        <flux:icon name="check-circle" variant="mini" class="size-5 shrink-0 text-green-500" />
                        <flux:text class="text-sm line-through">{{ __('Add a school to your profile') }}</flux:text>
                    @else
                        <flux:icon name="circle-stack" variant="mini" class="size-5 shrink-0 text-neutral-400 dark:text-neutral-500" />
                        <a href="{{ route('profile.edit') }}" wire:navigate class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                            {{ __('Add a school to your profile') }}
                        </a>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    @if ($hasProgram)
                        <flux:icon name="check-circle" variant="mini" class="size-5 shrink-0 text-green-500" />
                        <flux:text class="text-sm line-through">{{ __('Upload a concert program') }}</flux:text>
                    @else
                        <flux:icon name="circle-stack" variant="mini" class="size-5 shrink-0 text-neutral-400 dark:text-neutral-500" />
                        <a href="{{ route('addProgram') }}" wire:navigate class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                            {{ __('Upload a concert program') }}
                        </a>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    @if ($hasPrivacy)
                        <flux:icon name="check-circle" variant="mini" class="size-5 shrink-0 text-green-500" />
                        <flux:text class="text-sm line-through">{{ __('Set your privacy preferences') }}</flux:text>
                    @else
                        <flux:icon name="circle-stack" variant="mini" class="size-5 shrink-0 text-neutral-400 dark:text-neutral-500" />
                        <a href="{{ route('profile.edit') }}" wire:navigate class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                            {{ __('Set your privacy preferences') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
