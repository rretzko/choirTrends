<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <flux:heading>{{ __('Schools') }}</flux:heading>
        <flux:subheading>{{ __('Manage the schools associated with your account.') }}</flux:subheading>
    </div>

    @if ($schools->isEmpty())
        <flux:text class="italic">
            {{ __('You haven\'t added any schools yet. Add your first school to get started!') }}
        </flux:text>
    @else
        <div class="space-y-3">
            @foreach ($schools as $school)
                <div wire:key="school-{{ $school->id }}" class="flex items-center justify-between rounded-lg border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <div>
                        <div class="flex items-center gap-2">
                            <flux:text class="font-medium">{{ $school->school_name }}</flux:text>
                            @if ($school->abbreviation)
                                <flux:badge size="sm">{{ $school->abbreviation }}</flux:badge>
                            @endif
                        </div>
                        @if ($school->postal_code || $school->geo_state)
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ collect([$school->geo_state, $school->postal_code])->filter()->implode(' ') }}
                            </flux:text>
                        @endif
                    </div>
                    <div class="flex items-center gap-1">
                        <flux:button variant="ghost" size="sm" wire:click="openEditModal({{ $school->id }})" title="{{ __('Edit') }}">
                            <flux:icon name="pencil-square" class="size-4" />
                        </flux:button>
                        <flux:button variant="ghost" size="sm" wire:click="removeSchool({{ $school->id }})" wire:confirm="{{ __('Remove this school from your profile?') }}" title="{{ __('Remove') }}">
                            <flux:icon name="x-mark" class="size-4" />
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <flux:button variant="primary" wire:click="openAddModal">
        {{ __('Add School') }}
    </flux:button>

    <flux:modal name="school-form" class="md:w-96">
        <form wire:submit="saveSchool" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $isEditing ? __('Edit School') : __('Add School') }}</flux:heading>
                <flux:text class="mt-2">{{ $isEditing ? __('Update the school details.') : __('Add a school to your profile.') }}</flux:text>
            </div>

            <flux:input
                wire:model.live.debounce.500ms="schoolName"
                :label="__('School Name')"
                :description="__('Use full name, please.')"
                required
            />

            <flux:input
                wire:model="abbreviation"
                :label="__('Abbreviation')"
                :description="__('Auto-generated. You may edit it.')"
                maxlength="20"
            />

            <flux:input
                wire:model.live.debounce.500ms="postalCode"
                :label="__('Zip Code')"
                maxlength="20"
            />

            <flux:input
                wire:model="geoState"
                :label="__('State')"
                disabled
            />

            <flux:input
                wire:model="country"
                :label="__('Country')"
                disabled
            />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
