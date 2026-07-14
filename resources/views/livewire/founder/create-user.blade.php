<div>
    <form wire:submit.prevent="save" class="space-y-6">
        <flux:heading size="lg">{{ __('Create New User') }}</flux:heading>

        <flux:field>
            <flux:label>{{ __('Name') }}</flux:label>
            <flux:input wire:model="name" type="text" required />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Email') }}</flux:label>
            <flux:input wire:model="email" type="email" required />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('School/Org Name') }}</flux:label>
            <flux:input wire:model="schoolName" type="text" />
            <flux:text class="text-xs">{{ __('Leave blank if not yet known') }}</flux:text>
            <flux:error name="schoolName" />
        </flux:field>

        <div class="flex justify-end gap-3">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                {{ __('Create User') }}
            </flux:button>
        </div>
    </form>
</div>
