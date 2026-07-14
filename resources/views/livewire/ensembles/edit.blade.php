<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Ensemble') }}</flux:heading>
        <a href="{{ route('ensembles.index') }}" class="mt-1 inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
            <flux:icon name="arrow-left" class="size-3" />
            {{ __('Return to Ensembles table') }}
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="max-w-md space-y-6">
            <flux:field>
                <flux:label>{{ __('School/Org') }}</flux:label>
                <flux:input :value="$schoolName" disabled />
            </flux:field>

            <flux:field>
                <flux:label class="!flex w-full justify-between">
                    <span>{{ __('Ensemble Name') }}</span>
                    @unless ($editable)
                        <span class="mr-2 inline-flex items-center gap-1 text-amber-600 dark:text-amber-400"><flux:icon name="lock-closed" class="size-3" /> {{ __('Shared') }}</span>
                    @endunless
                </flux:label>
                <flux:input wire:model="ensembleName" :disabled="! $editable" />
                <flux:error name="ensembleName" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Voice Type') }}</flux:label>
                <select wire:model="type" class="block w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-400 focus:outline-none focus:ring-0 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                    @foreach (\App\Enums\EnsembleType::cases() as $ensembleType)
                        <option value="{{ $ensembleType->value }}">{{ $ensembleType->label() }}</option>
                    @endforeach
                </select>
                <flux:error name="type" />
            </flux:field>

            <flux:field>
                <flux:checkbox wire:model="aCappella" label="{{ __('A Cappella') }}" />
            </flux:field>
        </div>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
            <flux:button href="{{ route('ensembles.index') }}" variant="ghost">{{ __('Cancel') }}</flux:button>
        </div>
    </form>

    @if ($editable)
        <div class="mt-10 space-y-6">
            <div class="relative mb-5">
                <flux:heading>{{ __('Remove Ensemble') }}</flux:heading>
                <flux:subheading>{{ __('Permanently remove this ensemble.') }}</flux:subheading>
            </div>

            <flux:modal.trigger name="confirm-ensemble-removal">
                <flux:button variant="danger">
                    {{ __('Remove Ensemble') }}
                </flux:button>
            </flux:modal.trigger>

            <flux:modal name="confirm-ensemble-removal" focusable class="max-w-lg">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">{{ __('Are you sure you want to remove this ensemble?') }}</flux:heading>

                        <flux:subheading>
                            {{ __('Any song titles linked to this ensemble will lose their ensemble assignment. Programs and song titles themselves will not be deleted.') }}
                        </flux:subheading>
                    </div>

                    <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                        <flux:modal.close>
                            <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                        </flux:modal.close>

                        <flux:button variant="danger" wire:click="remove">{{ __('Remove Ensemble') }}</flux:button>
                    </div>
                </div>
            </flux:modal>
        </div>
    @endif
</div>
