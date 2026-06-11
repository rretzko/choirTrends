<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ $quickTip ? __('Edit Quick Tip') : __('Add Quick Tip') }}</flux:heading>
        <a href="{{ route('founder.quickTips') }}" class="mt-1 inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
            <flux:icon name="arrow-left" class="size-3" />
            {{ __('Return to Quick Tips') }}
        </a>
        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Next Mailing') }}: {{ $nextMailingDate->format('F j, Y') }}
        </p>
    </div>

    <form wire:submit="save" class="mx-auto max-w-2xl space-y-6">
        <div class="flex gap-4">
            <div class="w-24">
                <flux:field>
                    <flux:label>{{ __('Order') }}</flux:label>
                    <flux:input type="number" wire:model="sortOrder" min="1" />
                    <flux:error name="sortOrder" />
                </flux:field>
            </div>
            <div class="flex-1">
                <flux:field>
                    <flux:label>{{ __('Status') }}</flux:label>
                    <select wire:model="status" class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-400 focus:outline-none focus:ring-0 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        @foreach (['Draft', 'Pending', 'Sent'] as $statusOption)
                            <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                        @endforeach
                    </select>
                    <flux:error name="status" />
                </flux:field>
            </div>
        </div>

        <flux:field>
            <flux:label>{{ __('Header') }}</flux:label>
            <flux:input wire:model="header" placeholder="{{ __('Tip header (used in email subject)') }}" />
            <flux:error name="header" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Introduction (optional)') }}</flux:label>
            <div
                wire:ignore
                x-data="{ value: $wire.entangle('introduction') }"
                x-init="
                    const editor = $refs.trixIntroduction;
                    editor.editor.loadHTML(value);
                    editor.addEventListener('trix-change', () => { value = editor.value; });
                "
            >
                <input id="introduction-input" type="hidden" />
                <trix-editor
                    x-ref="trixIntroduction"
                    input="introduction-input"
                    class="trix-content min-h-[100px] rounded-lg border border-zinc-200 bg-white text-sm shadow-sm focus:border-zinc-400 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                ></trix-editor>
            </div>
            <flux:error name="introduction" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Tip') }}</flux:label>
            <div
                wire:ignore
                x-data="{ value: $wire.entangle('tip') }"
                x-init="
                    const editor = $refs.trixTip;
                    editor.editor.loadHTML(value);
                    editor.addEventListener('trix-change', () => { value = editor.value; });
                "
            >
                <input id="tip-input" type="hidden" />
                <trix-editor
                    x-ref="trixTip"
                    input="tip-input"
                    class="trix-content min-h-[200px] rounded-lg border border-zinc-200 bg-white text-sm shadow-sm focus:border-zinc-400 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                ></trix-editor>
            </div>
            <flux:error name="tip" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Footer') }}</flux:label>
            <flux:textarea wire:model="footer" rows="2" />
            <flux:error name="footer" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Call to Action') }}</flux:label>
            <flux:textarea wire:model="callToAction" rows="2" />
            <flux:error name="callToAction" />
        </flux:field>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">{{ $quickTip ? __('Save Changes') : __('Create') }}</flux:button>
            <flux:button href="{{ route('founder.quickTips') }}" variant="ghost">{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</div>

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/trix@2.1.12/dist/trix.css">
    <style>
        trix-toolbar [data-trix-button-group="file-tools"] { display: none !important; }
    </style>
@endpush

@push('scripts')
    <script src="https://unpkg.com/trix@2.1.12/dist/trix.umd.min.js"></script>
    <script>
        document.addEventListener('trix-file-accept', function(e) { e.preventDefault(); });
    </script>
@endpush
