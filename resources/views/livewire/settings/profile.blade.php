<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail)
                    @if (auth()->user()->hasVerifiedEmail())
                        <flux:badge color="green" size="sm" class="mt-2">{{ __('Verified') }}</flux:badge>
                    @else
                        <div>
                            <flux:badge color="yellow" size="sm" class="mt-2">{{ __('Unverified') }}</flux:badge>

                            <flux:text class="mt-2">
                                {{ __('Your email address is unverified.') }}

                                <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                    {{ __('Click here to re-send the verification email.') }}
                                </flux:link>
                            </flux:text>

                            <flux:text class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('If the verification email has not appeared within five minutes, please check your junk or spam folder.') }}
                            </flux:text>

                            @if (session('status') === 'verification-link-sent')
                                <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                    {{ __('A new verification link has been sent to your email address.') }}
                                </flux:text>
                            @endif
                        </div>
                    @endif
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <flux:separator class="my-6" />

        <livewire:settings.profile-schools />

        <flux:separator class="my-6" />

        <div>
            <flux:heading size="lg">{{ __('Privacy') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Control what information is visible to other users.') }}</flux:text>
        </div>

        <form wire:submit="updatePrivacySettings" class="my-6 w-full space-y-6">
            <div class="space-y-4 pl-4">
                <flux:checkbox wire:model="privacyName" :label="__('Do not display my name to other users.')" />
                <flux:checkbox wire:model="privacySchool" :label="__('Do not display my school/org to other users.')" />
                <flux:checkbox wire:model="privacyEnsembleName" :label="__('Do not display my ensemble names to other users.')" />
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <flux:button variant="ghost" type="button" wire:click="resetPrivacySettings">
                    {{ __("I'm happy to share my information with other Choir Directors.") }}
                </flux:button>

                <x-action-message class="me-3" on="privacy-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
