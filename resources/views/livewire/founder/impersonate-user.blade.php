<div>
    <div class="mx-auto max-w-md space-y-6">
        <flux:heading size="xl">{{ __('Impersonate User') }}</flux:heading>

        <flux:text>{{ __('Select a user to view the application from their perspective.') }}</flux:text>

        <form wire:submit.prevent="impersonate" class="space-y-6">
            <flux:field>
                <flux:label>{{ __('User') }}</flux:label>
                <select
                    wire:model="userId"
                    autofocus
                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-100"
                >
                    <option value="">{{ __('Select a user...') }}</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
                <flux:error name="userId" />
            </flux:field>

            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                {{ __('Impersonate') }}
            </flux:button>
        </form>
    </div>
</div>
