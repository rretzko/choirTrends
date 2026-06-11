<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __("Edit User's Guide") }}</flux:heading>
        <flux:text class="mt-1">{{ __('Manage the sections of the User\'s Guide. Click a section to edit its content.') }}</flux:text>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if ($editingId)
        {{-- Edit Form --}}
        <form wire:submit="saveSection" class="mx-auto max-w-3xl space-y-6">
            <div class="flex gap-4">
                <div class="w-24">
                    <flux:field>
                        <flux:label>{{ __('Order') }}</flux:label>
                        <flux:input type="number" wire:model="editSortOrder" min="1" />
                        <flux:error name="editSortOrder" />
                    </flux:field>
                </div>
                <div class="flex-1">
                    <flux:field>
                        <flux:label>{{ __('Title') }}</flux:label>
                        <flux:input wire:model="editTitle" />
                        <flux:error name="editTitle" />
                    </flux:field>
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 text-sm">
                        <flux:checkbox wire:model="editIsPublished" />
                        {{ __('Published') }}
                    </label>
                </div>
            </div>

            <flux:field>
                <flux:label>{{ __('Body') }}</flux:label>
                <div
                    wire:ignore
                    x-data="{ value: $wire.entangle('editBody') }"
                    x-init="
                        const editor = $refs.trixBody;
                        editor.editor.loadHTML(value);
                        editor.addEventListener('trix-change', () => { value = editor.value; });
                    "
                >
                    <input id="body-input" type="hidden" />
                    <trix-editor
                        x-ref="trixBody"
                        input="body-input"
                        class="trix-content min-h-[400px] rounded-lg border border-zinc-200 bg-white text-sm shadow-sm focus:border-zinc-400 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                    ></trix-editor>
                </div>
                <flux:error name="editBody" />
            </flux:field>

            <div class="flex items-center gap-4">
                <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
                <flux:button wire:click="cancelEdit" variant="ghost">{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    @else
        {{-- Section List --}}
        <div class="mx-auto max-w-3xl space-y-2">
            @foreach ($sections as $section)
                <div wire:key="section-{{ $section->id }}" class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-col gap-0.5">
                        <button wire:click="moveUp({{ $section->id }})" class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 {{ $loop->first ? 'invisible' : '' }}">
                            <flux:icon name="chevron-up" class="size-4" />
                        </button>
                        <button wire:click="moveDown({{ $section->id }})" class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 {{ $loop->last ? 'invisible' : '' }}">
                            <flux:icon name="chevron-down" class="size-4" />
                        </button>
                    </div>

                    <span class="w-8 text-center text-sm font-medium text-zinc-400">{{ $section->sort_order }}</span>

                    <div class="flex-1">
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $section->title }}</span>
                        @unless ($section->is_published)
                            <flux:badge color="amber" size="sm" class="ml-2">{{ __('Draft') }}</flux:badge>
                        @endunless
                    </div>

                    <button wire:click="editSection({{ $section->id }})" class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200">
                        <flux:icon name="pencil" class="size-5" />
                    </button>
                </div>
            @endforeach
        </div>
    @endif
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
