<div>
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex items-center justify-between">
            <flux:heading size="xl">{{ __('Quick Tips') }}</flux:heading>
            <flux:button variant="primary" href="{{ route('founder.quickTips.create') }}">{{ __('Add New') }}</flux:button>
        </div>

        @if (session('success'))
            <div class="rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                {{ session('success') }}
            </div>
        @endif

        @if ($tips->isEmpty())
            <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text>{{ __('No quick tips yet. Click "Add New" to create your first tip.') }}</flux:text>
            </div>
        @else
            <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-2 text-center">{{ __('#') }}</th>
                            <th class="px-4 py-2">{{ __('Header') }}</th>
                            <th class="px-4 py-2 text-center">{{ __('Status') }}</th>
                            <th class="px-4 py-2 text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                        @foreach ($tips as $tip)
                            <tr wire:key="tip-{{ $tip->id }}" class="text-zinc-700 dark:text-zinc-300">
                                <td class="px-4 py-2 text-center font-mono text-xs">{{ $tip->sort_order }}</td>
                                <td class="px-4 py-2">{{ $tip->header }}</td>
                                <td class="px-4 py-2 text-center">
                                    <flux:badge size="sm" :color="match($tip->status->value) { 'Draft' => 'zinc', 'Pending' => 'amber', 'Sent' => 'green' }">
                                        {{ $tip->status->label() }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <flux:button wire:click="openViewModal({{ $tip->id }})" variant="ghost" size="xs" icon="eye" />
                                        <flux:button href="{{ route('founder.quickTips.edit', $tip) }}" variant="ghost" size="xs" icon="pencil" />
                                        <flux:button wire:click="sendTestEmail({{ $tip->id }})" variant="ghost" size="xs" icon="envelope" />
                                        <flux:button wire:click="confirmDelete({{ $tip->id }})" variant="ghost" size="xs" icon="trash" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- View/Preview Modal --}}
    <flux:modal name="quick-tip-view" class="w-full max-w-2xl">
        @if ($viewingTip)
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Email Preview') }}</flux:heading>

                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="mb-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <strong>{{ __('Subject:') }}</strong> ChoirTrends Quick Tip: {{ strip_tags($viewingTip->header) }} in 90 Seconds
                    </p>
                    <hr class="my-3 border-zinc-200 dark:border-zinc-700">

                    <h2 class="mb-4 text-xl font-bold text-blue-500">{{ strip_tags($viewingTip->header) }}</h2>

                    @if ($viewingTip->introduction)
                        <div class="prose mb-4 max-w-none dark:prose-invert">
                            {!! $viewingTip->introduction !!}
                        </div>
                    @endif

                    <div class="prose mb-4 max-w-none rounded-lg border-l-4 border-blue-500 bg-zinc-50 p-4 dark:bg-zinc-800 dark:prose-invert">
                        {!! $viewingTip->tip !!}
                    </div>

                    <div class="rounded-lg bg-blue-50 p-4 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                        <p>{{ $viewingTip->resolvedCallToAction() }}</p>
                    </div>

                    <hr class="my-4 border-zinc-200 dark:border-zinc-700">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $viewingTip->resolvedFooter() }}</p>
                </div>

                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Close') }}</flux:button>
                </flux:modal.close>
            </div>
        @endif
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal name="quick-tip-delete" class="w-full max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Delete Quick Tip') }}</flux:heading>
            <flux:text>{{ __('Are you sure you want to delete this quick tip? This action cannot be undone.') }}</flux:text>
            <div class="flex items-center gap-4">
                <flux:button variant="danger" wire:click="delete">{{ __('Delete') }}</flux:button>
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
