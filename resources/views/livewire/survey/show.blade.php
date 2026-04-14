<div class="mx-auto max-w-lg space-y-6">
    @if ($submitted)
        <div class="text-center space-y-4">
            <flux:heading size="xl">{{ __('Thank You!') }}</flux:heading>
            <flux:text>{{ __('Your feedback is invaluable and will help us make ChoirTrends better for choral directors everywhere.') }}</flux:text>
            <flux:button variant="primary" href="{{ route('home') }}">{{ __('Visit ChoirTrends') }}</flux:button>
        </div>
    @elseif ($alreadyResponded)
        <div class="text-center space-y-4">
            <flux:heading size="xl">{{ __('Already Submitted') }}</flux:heading>
            <flux:text>{{ __("You've already shared your feedback — thank you! We truly appreciate it.") }}</flux:text>
            <flux:button variant="primary" href="{{ route('home') }}">{{ __('Visit ChoirTrends') }}</flux:button>
        </div>
    @else
        <flux:heading size="xl">{{ __('Help Us Improve ChoirTrends') }}</flux:heading>
        <flux:text>{{ __("Hi :name, we noticed you haven't uploaded any programs yet. We'd love to understand why so we can make the site better for choral directors like you.", ['name' => $userName]) }}</flux:text>

        <form wire:submit="submit" class="space-y-6">
            <fieldset class="space-y-3">
                <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('What has kept you from uploading a program? (select all that apply)') }}</legend>

                @foreach ($reasons as $reason)
                    @if ($reason !== \App\Enums\SurveyReason::Other)
                        <flux:checkbox
                            wire:model="selectedReasons"
                            value="{{ $reason->value }}"
                            label="{{ $reason->label() }}"
                        />
                    @endif
                @endforeach

                <flux:checkbox
                    wire:model.live="selectedReasons"
                    value="{{ \App\Enums\SurveyReason::Other->value }}"
                    label="{{ \App\Enums\SurveyReason::Other->label() }}"
                />

                @error('selectedReasons')
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </fieldset>

            @if (in_array(\App\Enums\SurveyReason::Other->value, $selectedReasons))
                <flux:field>
                    <flux:label>{{ __('Please describe') }}</flux:label>
                    <flux:input wire:model="otherReason" placeholder="{{ __('Your reason...') }}" />
                    <flux:error name="otherReason" />
                </flux:field>
            @endif

            <flux:field>
                <flux:label>{{ __('Any other comments or suggestions?') }}</flux:label>
                <flux:textarea wire:model="comments" rows="4" placeholder="{{ __('Optional — but we read every response.') }}" />
                <flux:error name="comments" />
            </flux:field>

            <flux:button type="submit" variant="primary">{{ __('Submit Feedback') }}</flux:button>
        </form>
    @endif
</div>
