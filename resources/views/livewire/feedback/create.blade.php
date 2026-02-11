<div>
    <div class="mb-6 space-y-4">
        <flux:heading size="xl">{{ __('Submit Feedback') }}</flux:heading>
    </div>

    <form wire:submit="submit" class="mx-auto max-w-2xl space-y-6">
        <flux:field>
            <flux:label>{{ __('Reported By') }}</flux:label>
            <flux:input value="{{ auth()->user()->name }}" disabled />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('From Page') }}</flux:label>
            <flux:input wire:model="fromPage" placeholder="{{ __('Page web address (optional)') }}" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Request Type') }}</flux:label>
            <div class="flex gap-2">
                @foreach (['Bug', 'Enhancement', 'Kudo', 'Comment'] as $feedbackType)
                    <flux:button
                        wire:click="setType('{{ $feedbackType }}')"
                        :variant="$type === $feedbackType ? 'primary' : 'ghost'"
                        size="sm"
                        type="button"
                    >
                        {{ __($feedbackType) }}
                    </flux:button>
                @endforeach
            </div>
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Request') }}</flux:label>
            <flux:textarea wire:model="body" rows="5" placeholder="{{ __('Describe your feedback...') }}" required />
            <flux:error name="body" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Upload File or Image') }}</flux:label>
            <flux:input type="file" wire:model="file" />
            <flux:error name="file" />
            @if ($file)
                <flux:text class="mt-1 text-sm text-neutral-500">{{ $file->getClientOriginalName() }}</flux:text>
            @endif
        </flux:field>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">{{ __('Submit Feedback') }}</flux:button>
            <flux:button href="{{ route('feedback.index') }}" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</div>
