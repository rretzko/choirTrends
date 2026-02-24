<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Issues') }}</flux:heading>
    </div>

    <div class="flex flex-col gap-6 lg:flex-row">
        {{-- Left panel: Feedback list --}}
        <div class="w-full lg:w-1/2">
            <div class="mb-4 flex flex-wrap items-center gap-2">
                <select wire:model.live="filterType" class="rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-sm text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100">
                    <option value="">{{ __('All Types') }}</option>
                    <option value="Bug">{{ __('Bug') }}</option>
                    <option value="Enhancement">{{ __('Enhancement') }}</option>
                    <option value="Kudo">{{ __('Kudo') }}</option>
                    <option value="Comment">{{ __('Comment') }}</option>
                </select>

                <select wire:model.live="filterStatus" class="rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-sm text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100">
                    <option value="">{{ __('All Statuses') }}</option>
                    <option value="Open">{{ __('Open') }}</option>
                    <option value="Pending">{{ __('Pending') }}</option>
                    <option value="Wip">{{ __('Wip') }}</option>
                    <option value="Closed">{{ __('Closed') }}</option>
                </select>
            </div>

            <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                {{-- Desktop table --}}
                <table class="hidden min-w-full divide-y divide-neutral-200 md:table dark:divide-neutral-700">
                    <thead class="bg-neutral-50 dark:bg-neutral-800">
                        <tr>
                            <th wire:click="sort('created_at')" class="cursor-pointer px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">
                                <div class="flex items-center gap-1">
                                    {{ __('Date') }}
                                    @if ($sortBy === 'created_at')
                                        <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                                    @else
                                        <flux:icon name="arrows-up-down" class="size-3 opacity-30" />
                                    @endif
                                </div>
                            </th>
                            <th wire:click="sort('type')" class="cursor-pointer px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">
                                <div class="flex items-center gap-1">
                                    {{ __('Type') }}
                                    @if ($sortBy === 'type')
                                        <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                                    @else
                                        <flux:icon name="arrows-up-down" class="size-3 opacity-30" />
                                    @endif
                                </div>
                            </th>
                            <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                {{ __('Submitter') }}
                            </th>
                            <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                {{ __('Request') }}
                            </th>
                            <th wire:click="sort('status')" class="cursor-pointer px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">
                                <div class="flex items-center gap-1">
                                    {{ __('Status') }}
                                    @if ($sortBy === 'status')
                                        <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                                    @else
                                        <flux:icon name="arrows-up-down" class="size-3 opacity-30" />
                                    @endif
                                </div>
                            </th>
                            <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                {{ __('Effort') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                        @forelse ($feedbacks as $feedback)
                            <tr
                                wire:key="issue-{{ $feedback->id }}"
                                wire:click="selectFeedback({{ $feedback->id }})"
                                class="cursor-pointer hover:bg-neutral-50 dark:hover:bg-neutral-800 {{ $selectedFeedbackId === $feedback->id ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}"
                            >
                                <td class="whitespace-nowrap px-4 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $feedback->created_at->format('M j') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-2 text-sm">
                                    <flux:badge size="sm" :color="match($feedback->type->value) { 'Bug' => 'red', 'Enhancement' => 'blue', 'Kudo' => 'green', default => 'zinc' }">
                                        {{ $feedback->type->value }}
                                    </flux:badge>
                                </td>
                                <td class="whitespace-nowrap px-4 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $feedback->user->name }}
                                </td>
                                <td class="max-w-[200px] truncate px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100">
                                    {{ Str::limit($feedback->body, 40) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-2 text-sm">
                                    <flux:badge size="sm" :color="match($feedback->status->value) { 'Open' => 'blue', 'Pending' => 'amber', 'Wip' => 'purple', 'Closed' => 'green', default => 'zinc' }">
                                        {{ $feedback->status->value }}
                                    </flux:badge>
                                </td>
                                <td class="whitespace-nowrap px-4 py-2 text-sm text-neutral-500 dark:text-neutral-400">
                                    @php
                                        $totalMinutes = $feedback->totalEffortMinutes();
                                        $hours = intdiv($totalMinutes, 60);
                                        $minutes = $totalMinutes % 60;
                                    @endphp
                                    {{ $totalMinutes > 0 ? sprintf('%d:%02d', $hours, $minutes) : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ __('No issues found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Mobile card layout --}}
                <div class="divide-y divide-neutral-200 md:hidden dark:divide-neutral-700">
                    @forelse ($feedbacks as $feedback)
                        <div
                            wire:key="issue-card-{{ $feedback->id }}"
                            wire:click="selectFeedback({{ $feedback->id }})"
                            class="cursor-pointer bg-white p-4 hover:bg-neutral-50 dark:bg-neutral-900 dark:hover:bg-neutral-800 {{ $selectedFeedbackId === $feedback->id ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}"
                        >
                            <div class="flex items-center gap-2">
                                <flux:badge size="sm" :color="match($feedback->type->value) { 'Bug' => 'red', 'Enhancement' => 'blue', 'Kudo' => 'green', default => 'zinc' }">
                                    {{ $feedback->type->value }}
                                </flux:badge>
                                <flux:badge size="sm" :color="match($feedback->status->value) { 'Open' => 'blue', 'Pending' => 'amber', 'Wip' => 'purple', 'Closed' => 'green', default => 'zinc' }">
                                    {{ $feedback->status->value }}
                                </flux:badge>
                            </div>
                            <flux:text class="mt-1 text-sm text-neutral-900 dark:text-neutral-100">{{ Str::limit($feedback->body, 80) }}</flux:text>
                            <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                                {{ $feedback->user->name }} &middot; {{ $feedback->created_at->format('M j, Y') }}
                            </flux:text>
                        </div>
                    @empty
                        <div class="px-4 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400">
                            {{ __('No issues found.') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right panel: Detail --}}
        <div class="w-full lg:w-1/2">
            @if ($selectedFeedback)
                <div class="space-y-6 rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
                    <div class="flex items-start justify-between">
                        <flux:heading size="lg">{{ __('Feedback Details') }}</flux:heading>
                        <flux:button wire:click="closeDetail" variant="ghost" size="xs" icon="x-mark" />
                    </div>

                    {{-- Info grid --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:text class="font-medium text-neutral-500 dark:text-neutral-400">{{ __('Submitter') }}</flux:text>
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
                            <select wire:model.live="selectedStatus" wire:change="updateStatus" class="rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-sm text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100">
                                @foreach (['Open', 'Pending', 'Wip', 'Closed'] as $status)
                                    <option value="{{ $status }}">{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($selectedFeedback->from_page)
                            <div class="col-span-2">
                                <flux:text class="font-medium text-neutral-500 dark:text-neutral-400">{{ __('From Page') }}</flux:text>
                                <flux:text>{{ $selectedFeedback->from_page }}</flux:text>
                            </div>
                        @endif
                    </div>

                    {{-- Body --}}
                    <div>
                        <flux:text class="mb-2 font-medium text-neutral-500 dark:text-neutral-400">{{ __('Request') }}</flux:text>
                        <div class="rounded-lg bg-neutral-50 p-4 text-sm text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100" style="white-space: pre-wrap;">{{ $selectedFeedback->body }}</div>
                    </div>

                    {{-- Attached file --}}
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

                    {{-- Comments section --}}
                    <div class="border-t border-neutral-200 pt-4 dark:border-neutral-700">
                        <flux:text class="mb-2 font-medium text-neutral-500 dark:text-neutral-400">{{ __('Comments') }}</flux:text>
                        @if ($selectedFeedback->comments->isNotEmpty())
                            <div class="mb-3 space-y-3">
                                @foreach ($selectedFeedback->comments as $comment)
                                    <div wire:key="issue-comment-{{ $comment->id }}" class="rounded-lg border border-neutral-200 p-3 dark:border-neutral-700">
                                        <div class="mb-1 flex items-center justify-between">
                                            <flux:text class="text-sm font-medium">{{ $comment->user->name }}</flux:text>
                                            <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">{{ $comment->created_at->format('M j, Y g:i A') }}</flux:text>
                                        </div>
                                        <flux:text class="text-sm">{{ $comment->body }}</flux:text>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <flux:text class="mb-3 text-sm text-neutral-500 dark:text-neutral-400">{{ __('No comments yet.') }}</flux:text>
                        @endif

                        <flux:textarea wire:model="newComment" rows="2" placeholder="{{ __('Write a comment...') }}" />
                        <flux:error name="newComment" />
                        <div class="mt-2">
                            <flux:button wire:click="addComment" variant="primary" size="sm">{{ __('Add Comment') }}</flux:button>
                        </div>
                    </div>

                    {{-- Effort tracking section --}}
                    <div class="border-t border-neutral-200 pt-4 dark:border-neutral-700">
                        <flux:text class="mb-2 font-medium text-neutral-500 dark:text-neutral-400">{{ __('Effort Tracking') }}</flux:text>

                        {{-- Total effort --}}
                        @php
                            $totalMinutes = $selectedFeedback->totalEffortMinutes();
                            $hours = intdiv($totalMinutes, 60);
                            $minutes = $totalMinutes % 60;
                        @endphp
                        <div class="mb-3">
                            <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">
                                {{ __('Total Effort:') }}
                                <span class="font-medium text-neutral-900 dark:text-neutral-100">{{ sprintf('%d:%02d', $hours, $minutes) }}</span>
                            </flux:text>
                        </div>

                        {{-- Timer controls --}}
                        <div class="mb-4 flex items-center gap-2">
                            @if ($runningEffort)
                                <flux:button wire:click="stopTimer" variant="danger" size="sm" icon="stop">{{ __('Stop Timer') }}</flux:button>
                                <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ __('Started') }} {{ $runningEffort->started_at->diffForHumans() }}
                                </flux:text>
                            @else
                                <flux:button wire:click="startTimer" variant="primary" size="sm" icon="play">{{ __('Start Timer') }}</flux:button>
                            @endif
                        </div>

                        {{-- Manual entry --}}
                        <div class="space-y-2">
                            <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Manual Entry') }}</flux:text>
                            <div class="flex flex-wrap items-end gap-2">
                                <div>
                                    <label class="block text-xs text-neutral-500 dark:text-neutral-400">{{ __('Start') }}</label>
                                    <input type="datetime-local" wire:model="manualStartTime" class="rounded-lg border border-neutral-300 bg-white px-2 py-1.5 text-sm text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100" />
                                </div>
                                <div>
                                    <label class="block text-xs text-neutral-500 dark:text-neutral-400">{{ __('Stop') }}</label>
                                    <input type="datetime-local" wire:model="manualStopTime" class="rounded-lg border border-neutral-300 bg-white px-2 py-1.5 text-sm text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100" />
                                </div>
                                <flux:button wire:click="addManualEffort" variant="filled" size="sm">{{ __('Add Entry') }}</flux:button>
                            </div>
                            <flux:error name="manualStartTime" />
                            <flux:error name="manualStopTime" />
                        </div>
                    </div>
                </div>
            @else
                <div class="flex h-64 items-center justify-center rounded-xl border border-dashed border-neutral-300 dark:border-neutral-600">
                    <flux:text class="text-neutral-500 dark:text-neutral-400">{{ __('Select an issue to view details') }}</flux:text>
                </div>
            @endif
        </div>
    </div>
</div>
