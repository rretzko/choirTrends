<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <div class="flex justify-center">
            <img src="{{ asset('favicon.svg') }}" alt="{{ config('app.name') }}" class="size-16" />
        </div>

        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6"
              onsubmit="this.querySelector('#register-viewport-field').value = window.innerWidth + 'x' + window.innerHeight">
            @csrf
            <x-honeypot />

            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <!-- How did you find us? -->
            <fieldset class="flex flex-col gap-2">
                <legend class="text-sm font-medium text-zinc-800 dark:text-white mb-1">{{ __('How did you find us?') }}</legend>

                @foreach (\App\Enums\ReferralSource::cases() as $source)
                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer">
                        <input
                            type="radio"
                            name="referral_source"
                            value="{{ $source->value }}"
                            class="accent-zinc-800 dark:accent-white"
                            @checked(old('referral_source') === $source->value)
                        />
                        {{ __($source->label()) }}
                    </label>
                @endforeach

                @error('referral_source')
                    <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror

                <div id="referral-detail-wrapper" class="hidden mt-1">
                    <flux:input
                        name="referral_detail"
                        :value="old('referral_detail')"
                        type="text"
                        maxlength="255"
                        id="referral-detail-input"
                    />
                </div>
            </fieldset>

            <input type="hidden" name="viewport" id="register-viewport-field">

            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('A verification email will be sent to the address above. If it has not appeared within five minutes, please check your junk or spam folder.') }}
            </flux:text>

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const radios = document.querySelectorAll('input[name="referral_source"]');
            const wrapper = document.getElementById('referral-detail-wrapper');
            const input = document.getElementById('referral-detail-input');

            const placeholders = {
                'WebSearch': 'Which search engine or search term?',
                'Referral': 'Name or email of person who referred you',
                'Other': 'Please tell us how you found us',
            };

            function updateDetail() {
                const selected = document.querySelector('input[name="referral_source"]:checked');
                if (selected && placeholders[selected.value]) {
                    wrapper.classList.remove('hidden');
                    input.placeholder = placeholders[selected.value];
                } else {
                    wrapper.classList.add('hidden');
                    input.value = '';
                }
            }

            radios.forEach(radio => radio.addEventListener('change', updateDetail));
            updateDetail();
        });
    </script>
</x-layouts.auth>
