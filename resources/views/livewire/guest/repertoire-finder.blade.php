<div class="rounded-3xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-8 lg:p-10 shadow-sm">
    <div class="mx-auto mb-8 max-w-2xl text-center">
        <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-teal-200 bg-teal-50 px-3 py-1 text-xs font-medium text-teal-700 dark:border-teal-800 dark:bg-teal-950/50 dark:text-teal-300">
            <flux:icon name="sparkles" class="size-3.5" />
            {{ __('AI Repertoire Finder') }}
        </div>
        <flux:heading size="xl">{{ __('Ask about repertoire in plain language') }}</flux:heading>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">
            {{ __('Describe what you\'re looking for — voicing, difficulty balance, theme, or occasion — and we\'ll search real concert programs and the web for matches.') }}
        </p>
    </div>

    <div class="mx-auto max-w-2xl space-y-4">
        @if ($remainingQueries > 0 || $aiSearching || $aiResult)
            <form wire:submit="askAi" class="space-y-3">
                <flux:textarea
                    wire:model="query"
                    rows="3"
                    :disabled="$aiSearching"
                    placeholder="{{ __('e.g. High school SATB pieces that are easy for the men and challenging for the women') }}"
                />
                @error('query')
                    <flux:text size="sm" class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror

                {{-- Cloudflare Turnstile widget --}}
                <div wire:ignore>
                    <div
                        class="cf-turnstile"
                        data-sitekey="{{ config('services.turnstile.site_key') }}"
                        data-callback="onChoirTrendsTurnstileSuccess"
                        data-expired-callback="onChoirTrendsTurnstileExpired"
                        data-theme="auto"
                    ></div>
                </div>
                @error('turnstileToken')
                    <flux:text size="sm" class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror

                <div class="flex items-center justify-between gap-3">
                    <flux:button type="submit" variant="primary" :disabled="$aiSearching">
                        {{ $aiSearching ? __('Searching…') : __('Search') }}
                    </flux:button>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                        @if ($remainingQueries === 1)
                            {{ __('1 free search left') }}
                        @else
                            {{ __(':count free searches left', ['count' => $remainingQueries]) }}
                        @endif
                    </span>
                </div>
            </form>
        @else
            <div class="rounded-2xl border border-teal-200 bg-teal-50 p-6 text-center dark:border-teal-800 dark:bg-teal-950/50">
                <flux:heading size="lg">{{ __("You've used your free searches") }}</flux:heading>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Create a free account to keep searching — plus upload your own programs and see what similar choirs are performing.') }}
                </p>
                <flux:button href="{{ route('register') }}" variant="primary" class="mt-4">
                    {{ __('Create Your Account') }}
                </flux:button>
            </div>
        @endif

        @if ($aiSearching)
            <div wire:poll.2s="checkAiSearchStatus" class="flex items-center gap-3 rounded-xl border border-zinc-200 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                <flux:icon name="arrow-path" class="size-4 animate-spin" />
                {{ __('Searching the catalog and the web — this can take up to one minute…') }}
            </div>
        @endif

        @if ($aiError)
            <flux:callout icon="exclamation-triangle" color="red">
                <flux:callout.heading>{{ __('Search failed') }}</flux:callout.heading>
                <flux:callout.text>{{ $aiError }}</flux:callout.text>
            </flux:callout>
        @endif

        @if ($aiResult)
            <x-repertoire.results :result="$aiResult" />

            <div class="flex justify-center">
                <flux:button type="button" variant="ghost" wire:click="resetAiSearch">
                    {{ __('New search') }}
                </flux:button>
            </div>
        @endif
    </div>
</div>

<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<script>
    function onChoirTrendsTurnstileSuccess(token) {
        Livewire.dispatch('turnstile-verified', { token: token });
    }

    function onChoirTrendsTurnstileExpired() {
        Livewire.dispatch('turnstile-expired');
    }

    document.addEventListener('livewire:init', () => {
        Livewire.on('reset-turnstile', () => {
            if (window.turnstile) {
                window.turnstile.reset();
            }
        });
    });
</script>
