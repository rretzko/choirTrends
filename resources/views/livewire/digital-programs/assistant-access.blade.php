<div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex items-center gap-4">
        <div class="flex size-12 items-center justify-center rounded-lg bg-teal-100 dark:bg-teal-900">
            <flux:icon name="user-group" class="size-6 text-teal-600 dark:text-teal-400" />
        </div>
        <div>
            <flux:heading size="lg">{{ __('Assistant Access') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Create a login a student can use to enter program data. Assistants can save drafts but cannot publish, unpublish, or delete programs.') }}
            </flux:text>
        </div>
    </div>

    <div class="mt-4">
        @if($assistant)
            <div class="flex flex-col gap-3">
                <div x-data="{ copied: false }" class="flex items-center gap-2">
                    <flux:text class="text-base">
                        <span class="font-medium">{{ __('Login email') }}:</span>
                        <span class="font-mono font-bold text-amber-600 dark:text-amber-400">{{ $assistant->email }}</span>
                    </flux:text>
                    <flux:button
                        variant="subtle"
                        size="sm"
                        icon="clipboard-document"
                        title="{{ __('Copy login email') }}"
                        x-on:click="navigator.clipboard.writeText('{{ $assistant->email }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    />
                    <flux:text x-show="copied" x-cloak class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Copied!') }}</flux:text>
                </div>

                @if($generatedPassword)
                    <flux:callout icon="key" color="amber" x-data="{ copied: false }">
                        <flux:callout.heading>{{ __('Save this password now') }}</flux:callout.heading>
                        <flux:callout.text>
                            {{ __("This password won't be shown again.") }}
                        </flux:callout.text>
                        <div class="mt-3 flex items-center gap-2">
                            <input
                                type="text"
                                readonly
                                value="{{ $generatedPassword }}"
                                class="flex-1 rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 font-mono text-sm text-zinc-700 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-300"
                            >
                            <flux:button
                                variant="subtle"
                                size="sm"
                                icon="clipboard-document"
                                title="{{ __('Copy password') }}"
                                x-on:click="navigator.clipboard.writeText('{{ $generatedPassword }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            />
                            <flux:text x-show="copied" x-cloak class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Copied!') }}</flux:text>
                        </div>
                    </flux:callout>
                @endif

                <div class="flex flex-wrap gap-2">
                    <flux:button
                        wire:click="resetPassword"
                        wire:confirm="{{ __('Generate a new password for this assistant? The old password will stop working immediately.') }}"
                        variant="subtle"
                        size="sm"
                        icon="arrow-path">
                        {{ __('Reset Password') }}
                    </flux:button>
                    <flux:button
                        wire:click="removeAssistant"
                        wire:confirm="{{ __('Remove this assistant login? It will no longer be able to sign in.') }}"
                        variant="ghost"
                        size="sm"
                        icon="trash"
                        class="text-red-500 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950/30">
                        {{ __('Remove Access') }}
                    </flux:button>
                </div>
            </div>
        @else
            <flux:button
                wire:click="createAssistant"
                variant="primary"
                size="sm"
                icon="plus">
                {{ __('Create Assistant Login') }}
            </flux:button>
        @endif
    </div>
</div>
