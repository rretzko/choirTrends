<div>
    <div class="mb-6 space-y-4">
        <flux:heading size="xl">{{ __('Feedback') }}</flux:heading>

        <div class="flex items-center justify-center gap-2">
            <flux:button wire:click="$set('tab', 'report')" :variant="$tab === 'report' ? 'primary' : 'ghost'" size="sm">
                {{ __('Report') }}
            </flux:button>
            <flux:button wire:click="$set('tab', 'history')" :variant="$tab === 'history' ? 'primary' : 'ghost'" size="sm">
                {{ __('History') }}
            </flux:button>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    {{-- Report Tab --}}
    @if ($tab === 'report')
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

            @if (auth()->user()->isFounder())
                <flux:field>
                    <div class="flex items-center gap-2">
                        <flux:checkbox wire:model="isPrivate" />
                        <flux:label>{{ __('Private (visible only on Issues page)') }}</flux:label>
                    </div>
                </flux:field>
            @endif

            <div class="flex items-center gap-4">
                <flux:button type="submit" variant="primary">{{ __('Submit Feedback') }}</flux:button>
            </div>
        </form>
    @endif

    {{-- History Tab --}}
    @if ($tab === 'history')
        <div class="mb-4 flex flex-wrap items-center justify-center gap-4">
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

        <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            {{-- Desktop table --}}
            <table class="hidden min-w-full divide-y divide-neutral-200 md:table dark:divide-neutral-700">
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
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                    @forelse ($feedbacks as $feedback)
                        @if ($editingFeedbackId === $feedback->id)
                            {{-- Inline edit row --}}
                            <tr wire:key="feedback-edit-{{ $feedback->id }}">
                                <td colspan="7" class="px-6 py-4">
                                    <div class="space-y-4">
                                        <div class="flex items-center gap-2">
                                            <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Type:') }}</flux:text>
                                            @foreach (['Bug', 'Enhancement', 'Kudo', 'Comment'] as $feedbackType)
                                                <flux:button
                                                    wire:click="$set('editType', '{{ $feedbackType }}')"
                                                    :variant="$editType === $feedbackType ? 'primary' : 'ghost'"
                                                    size="xs"
                                                    type="button"
                                                >
                                                    {{ __($feedbackType) }}
                                                </flux:button>
                                            @endforeach
                                        </div>

                                        <div>
                                            <flux:textarea wire:model="editBody" rows="3" />
                                            <flux:error name="editBody" />
                                        </div>

                                        <div>
                                            <flux:input type="file" wire:model="editFile" />
                                            <flux:error name="editFile" />
                                            @if ($feedback->file_path && ! $editFile)
                                                <flux:text class="mt-1 text-xs text-neutral-500">{{ __('Current file attached. Upload a new file to replace it.') }}</flux:text>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <flux:button wire:click="saveEdit" variant="primary" size="sm">{{ __('Save') }}</flux:button>
                                            <flux:button wire:click="cancelEditing" variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @else
                            <tr wire:key="feedback-{{ $feedback->id }}">
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
                                <td class="whitespace-nowrap px-6 py-2 text-sm">
                                    @if ($feedback->user_id === auth()->id())
                                        <div class="flex items-center gap-1">
                                            <flux:button wire:click="startEditing({{ $feedback->id }})" variant="ghost" size="xs" icon="pencil" />
                                            <flux:button wire:click="startCommenting({{ $feedback->id }})" variant="ghost" size="xs" icon="chat-bubble-left" />
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endif

                        {{-- Inline comments panel --}}
                        @if ($commentingFeedbackId === $feedback->id)
                            <tr wire:key="feedback-comments-{{ $feedback->id }}">
                                <td colspan="7" class="bg-neutral-50 px-6 py-4 dark:bg-neutral-800">
                                    <div class="space-y-3">
                                        @if ($feedback->comments->isNotEmpty())
                                            @foreach ($feedback->comments as $comment)
                                                <div wire:key="comment-{{ $comment->id }}" class="rounded-lg border border-neutral-200 p-3 dark:border-neutral-700">
                                                    <div class="mb-1 flex items-center justify-between">
                                                        <flux:text class="text-sm font-medium">{{ $comment->user->name }}</flux:text>
                                                        <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">{{ $comment->created_at->format('M j, Y g:i A') }}</flux:text>
                                                    </div>
                                                    <flux:text class="text-sm">{{ $comment->body }}</flux:text>
                                                </div>
                                            @endforeach
                                        @else
                                            <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('No comments yet.') }}</flux:text>
                                        @endif

                                        <div class="flex items-start gap-2">
                                            <div class="flex-1">
                                                <flux:textarea wire:model="userComment" rows="2" placeholder="{{ __('Write a comment...') }}" />
                                                <flux:error name="userComment" />
                                            </div>
                                            <div class="flex items-center gap-1 pt-1">
                                                <flux:button wire:click="submitUserComment" variant="primary" size="sm">{{ __('Send') }}</flux:button>
                                                <flux:button wire:click="cancelCommenting" variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400">
                                {{ __('No feedback found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Mobile card layout --}}
            <div class="divide-y divide-neutral-200 md:hidden dark:divide-neutral-700">
                @forelse ($feedbacks as $feedback)
                    <div wire:key="feedback-card-{{ $feedback->id }}" class="bg-white p-4 dark:bg-neutral-900">
                        @if ($editingFeedbackId === $feedback->id)
                            <div class="space-y-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Type:') }}</flux:text>
                                    @foreach (['Bug', 'Enhancement', 'Kudo', 'Comment'] as $feedbackType)
                                        <flux:button
                                            wire:click="$set('editType', '{{ $feedbackType }}')"
                                            :variant="$editType === $feedbackType ? 'primary' : 'ghost'"
                                            size="xs"
                                            type="button"
                                        >
                                            {{ __($feedbackType) }}
                                        </flux:button>
                                    @endforeach
                                </div>
                                <flux:textarea wire:model="editBody" rows="3" />
                                <flux:error name="editBody" />
                                <flux:input type="file" wire:model="editFile" />
                                <flux:error name="editFile" />
                                <div class="flex items-center gap-2">
                                    <flux:button wire:click="saveEdit" variant="primary" size="sm">{{ __('Save') }}</flux:button>
                                    <flux:button wire:click="cancelEditing" variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                                </div>
                            </div>
                        @else
                            <div class="flex items-start justify-between gap-2">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <flux:badge size="sm" :color="match($feedback->type->value) { 'Bug' => 'red', 'Enhancement' => 'blue', 'Kudo' => 'green', default => 'zinc' }">
                                            {{ $feedback->type->value }}
                                        </flux:badge>
                                        <flux:badge size="sm" :color="match($feedback->status->value) { 'Open' => 'blue', 'Pending' => 'amber', 'Wip' => 'purple', 'Closed' => 'green', default => 'zinc' }">
                                            {{ $feedback->status->value }}
                                        </flux:badge>
                                    </div>
                                    <flux:text class="text-sm text-neutral-900 dark:text-neutral-100">{{ Str::limit($feedback->body, 80) }}</flux:text>
                                    <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ $displayNames[$feedback->id] }} &middot; {{ $feedback->created_at->format('M j, Y') }}
                                        @if ($feedback->comments->count())
                                            &middot; {{ $feedback->comments->count() }} {{ __('comments') }}
                                        @endif
                                    </flux:text>
                                </div>
                                @if ($feedback->user_id === auth()->id())
                                    <div class="flex items-center gap-1">
                                        <flux:button wire:click="startEditing({{ $feedback->id }})" variant="ghost" size="xs" icon="pencil" />
                                        <flux:button wire:click="startCommenting({{ $feedback->id }})" variant="ghost" size="xs" icon="chat-bubble-left" />
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Mobile inline comments --}}
                        @if ($commentingFeedbackId === $feedback->id)
                            <div class="mt-3 space-y-3 border-t border-neutral-200 pt-3 dark:border-neutral-700">
                                @if ($feedback->comments->isNotEmpty())
                                    @foreach ($feedback->comments as $comment)
                                        <div wire:key="comment-mobile-{{ $comment->id }}" class="rounded-lg border border-neutral-200 p-3 dark:border-neutral-700">
                                            <div class="mb-1 flex items-center justify-between">
                                                <flux:text class="text-sm font-medium">{{ $comment->user->name }}</flux:text>
                                                <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">{{ $comment->created_at->format('M j') }}</flux:text>
                                            </div>
                                            <flux:text class="text-sm">{{ $comment->body }}</flux:text>
                                        </div>
                                    @endforeach
                                @else
                                    <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('No comments yet.') }}</flux:text>
                                @endif
                                <div class="space-y-2">
                                    <flux:textarea wire:model="userComment" rows="2" placeholder="{{ __('Write a comment...') }}" />
                                    <flux:error name="userComment" />
                                    <div class="flex items-center gap-2">
                                        <flux:button wire:click="submitUserComment" variant="primary" size="sm">{{ __('Send') }}</flux:button>
                                        <flux:button wire:click="cancelCommenting" variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400">
                        {{ __('No feedback found.') }}
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</div>
