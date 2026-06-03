<div>
    <div class="mx-auto max-w-md space-y-6">
        <flux:heading size="xl">{{ __('Change User Password') }}</flux:heading>

        <flux:text>{{ __('Select a user to reset their password to the lowercase version of their email address.') }}</flux:text>

        @if (session('success'))
            <div class="rounded-lg bg-green-50 p-3 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit.prevent="save" class="space-y-6">
            <flux:field>
                <flux:label>{{ __('User') }}</flux:label>
                <select
                    wire:model="userId"
                    autofocus
                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-100"
                >
                    <option value="">{{ __('Select a user...') }}</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->alpha_name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
                <flux:error name="userId" />
            </flux:field>

            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                {{ __('Reset Password to Email') }}
            </flux:button>
        </form>
    </div>
</div>
