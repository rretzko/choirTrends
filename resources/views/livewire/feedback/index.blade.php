<div>
    <div class="mb-6 space-y-4">
        <flux:heading size="xl">{{ __('Feedback') }}</flux:heading>

        <div class="flex flex-wrap items-center justify-center gap-4">
            <flux:button href="{{ route('feedback.create') }}" variant="primary" size="sm" icon="plus" wire:navigate>
                {{ __('Submit Feedback') }}
            </flux:button>

            <div class="flex items-center gap-2">
                <select wire:model.live="filterType" class="rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-sm text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100">
                    <option value="">{{ __('All Types') }}</option>
                    <option value="Bug">{{ __('Bug') }}</option>
                    <option value="Enhancement">{{ __('Enhancement') }}</option>
                    <option value="Kudo">{{ __('Kudo') }}</option>
                    <option value="Comment">{{ __('Comment') }}</option>
                </select>

                <select wire:model.live="filterScope" class="rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-sm text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100">
                    <option value="my">{{ __('My Requests') }}</option>
                    <option value="all">{{ __('All Requests') }}</option>
                </select>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
            <thead class="bg-neutral-50 dark:bg-neutral-800">
                <tr>
                    <th wire:click="sort('created_at')" class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">
                        <div class="flex items-center gap-1">
                            {{ __('Date') }}
                            @if ($sortBy === 'created_at')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @else
                                <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                            @endif
                        </div>
                    </th>
                    <th wire:click="sort('type')" class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">
                        <div class="flex items-center gap-1">
                            {{ __('Type') }}
                            @if ($sortBy === 'type')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @else
                                <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                            @endif
                        </div>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('Submitted By') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('Request') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ __('Comments') }}
                    </th>
                    <th wire:click="sort('status')" class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">
                        <div class="flex items-center gap-1">
                            {{ __('Status') }}
                            @if ($sortBy === 'status')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @else
                                <flux:icon name="arrows-up-down" class="size-4 opacity-30" />
                            @endif
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                @forelse ($feedbacks as $feedback)
                    <tr wire:key="feedback-{{ $feedback->id }}" wire:click="showDetails({{ $feedback->id }})" class="cursor-pointer hover:bg-neutral-50 dark:hover:bg-neutral-800">
                        <td class="whitespace-nowrap px-6 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $feedback->created_at->format('M j, Y') }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-2 text-sm">
                            <flux:badge size="sm" :color="match($feedback->type->value) { 'Bug' => 'red', 'Enhancement' => 'blue', 'Kudo' => 'green', default => 'zinc' }">
                                {{ $feedback->type->value }}
                            </flux:badge>
                        </td>
                        <td class="whitespace-nowrap px-6 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $displayNames[$feedback->id] }}
                        </td>
                        <td class="max-w-xs truncate px-6 py-2 text-sm text-neutral-900 dark:text-neutral-100">
                            {{ Str::limit($feedback->body, 60) }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $feedback->comments->count() ?: '—' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-2 text-sm">
                            <flux:badge size="sm" :color="match($feedback->status->value) { 'Open' => 'blue', 'Pending' => 'amber', 'Wip' => 'purple', 'Closed' => 'green', default => 'zinc' }">
                                {{ $feedback->status->value }}
                            </flux:badge>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400">
                            {{ __('No feedback found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal name="feedback-details" class="max-w-2xl">
        @if ($selectedFeedback)
            <div class="space-y-6">
                <flux:heading size="lg">{{ __('Feedback Details') }}</flux:heading>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text class="font-medium text-neutral-500 dark:text-neutral-400">{{ __('Submitted By') }}</flux:text>
                        <flux:text>{{ $selectedFeedback->user->name }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="font-medium text-neutral-500 dark:text-neutral-400">{{ __('Date') }}</flux:text>
                        <flux:text>{{ $selectedFeedback->created_at->format('M j, Y g:i A') }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="font-medium text-neutral-500 dark:text-neutral-400">{{ __('Type') }}</flux:text>
                        <flux:badge size="sm" :color="match($selectedFeedback->type->value) { 'Bug' => 'red', 'Enhancement' => 'blue', 'Kudo' => 'green', default => 'zinc' }">
                            {{ $selectedFeedback->type->value }}
                        </flux:badge>
                    </div>
                    <div>
                        <flux:text class="font-medium text-neutral-500 dark:text-neutral-400">{{ __('Status') }}</flux:text>
                        @if ($isFounder)
                            <select wire:model.live="selectedStatus" wire:change="updateStatus" class="rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-sm text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100">
                                @foreach (['Open', 'Pending', 'Wip', 'Closed'] as $status)
                                    <option value="{{ $status }}">{{ $status }}</option>
                                @endforeach
                            </select>
                        @else
                            <flux:badge size="sm" :color="match($selectedFeedback->status->value) { 'Open' => 'blue', 'Pending' => 'amber', 'Wip' => 'purple', 'Closed' => 'green', default => 'zinc' }">
                                {{ $selectedFeedback->status->value }}
                            </flux:badge>
                        @endif
                    </div>
                    @if ($selectedFeedback->from_page)
                        <div class="col-span-2">
                            <flux:text class="font-medium text-neutral-500 dark:text-neutral-400">{{ __('From Page') }}</flux:text>
                            <flux:text>{{ $selectedFeedback->from_page }}</flux:text>
                        </div>
                    @endif
                </div>

                <div>
                    <flux:text class="mb-2 font-medium text-neutral-500 dark:text-neutral-400">{{ __('Request') }}</flux:text>
                    <div class="rounded-lg bg-neutral-50 p-4 text-sm text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100" style="white-space: pre-wrap;">{{ $selectedFeedback->body }}</div>
                </div>

                @if ($selectedFeedback->file_path)
                    <div>
                        <flux:text class="mb-2 font-medium text-neutral-500 dark:text-neutral-400">{{ __('Attached File') }}</flux:text>
                        @if (Str::endsWith($selectedFeedback->file_path, ['.jpg', '.jpeg', '.png', '.gif', '.webp']))
                            <img src="{{ Storage::disk('public')->url($selectedFeedback->file_path) }}" alt="{{ __('Attached image') }}" class="max-h-64 rounded-lg" />
                        @else
                            <a href="{{ Storage::disk('public')->url($selectedFeedback->file_path) }}" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">
                                {{ __('View attached file') }}
                            </a>
                        @endif
                    </div>
                @endif

                @if ($selectedFeedback->comments->isNotEmpty())
                    <div>
                        <flux:text class="mb-2 font-medium text-neutral-500 dark:text-neutral-400">{{ __('Comments') }}</flux:text>
                        <div class="space-y-3">
                            @foreach ($selectedFeedback->comments as $comment)
                                <div wire:key="comment-{{ $comment->id }}" class="rounded-lg border border-neutral-200 p-3 dark:border-neutral-700">
                                    <div class="mb-1 flex items-center justify-between">
                                        <flux:text class="text-sm font-medium">{{ $comment->user->name }}</flux:text>
                                        <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">{{ $comment->created_at->format('M j, Y g:i A') }}</flux:text>
                                    </div>
                                    <flux:text class="text-sm">{{ $comment->body }}</flux:text>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($isFounder)
                    <div class="border-t border-neutral-200 pt-4 dark:border-neutral-700">
                        <flux:text class="mb-2 font-medium text-neutral-500 dark:text-neutral-400">{{ __('Add Comment') }}</flux:text>
                        <flux:textarea wire:model="newComment" rows="3" placeholder="{{ __('Write a comment...') }}" />
                        <flux:error name="newComment" />
                        <div class="mt-2">
                            <flux:button wire:click="addComment" variant="primary" size="sm">{{ __('Add Comment') }}</flux:button>
                        </div>
                    </div>
                @endif

                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Close') }}</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
