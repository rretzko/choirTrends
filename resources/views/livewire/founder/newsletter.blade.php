<div>
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex items-center justify-between">
            <flux:heading size="xl">{{ __('Newsletter') }}</flux:heading>
            <flux:text class="text-sm">
                {{ __(':count verified recipients', ['count' => $recipientCount]) }}
            </flux:text>
        </div>

        @if (session('success'))
            <div class="rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">
                {{ session('error') }}
            </div>
        @endif

        {{-- Past Newsletters --}}
        @if ($newsletters->isNotEmpty())
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="lg">{{ __('Past Newsletters') }}</flux:heading>
                    <flux:button variant="primary" size="sm" icon="plus" wire:click="createNew">
                        {{ __('New Draft') }}
                    </flux:button>
                </div>
                <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-2">{{ __('Subject') }}</th>
                                <th class="px-4 py-2 text-center">{{ __('Status') }}</th>
                                <th class="px-4 py-2 text-center">{{ __('Recipients') }}</th>
                                <th class="px-4 py-2 text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                            @foreach ($newsletters as $nl)
                                <tr wire:key="nl-{{ $nl->id }}" class="text-zinc-700 dark:text-zinc-300 {{ $newsletterId === $nl->id ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                    <td class="px-4 py-2">{{ Str::limit($nl->subject, 50) }}</td>
                                    <td class="px-4 py-2 text-center">
                                        @if ($nl->sent_at)
                                            <flux:badge size="sm" color="green">{{ __('Sent :date', ['date' => $nl->sent_at->format('M j, Y')]) }}</flux:badge>
                                        @else
                                            <flux:badge size="sm" color="amber">{{ __('Draft') }}</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-center font-mono text-xs">{{ $nl->recipient_count ?: '—' }}</td>
                                    <td class="px-4 py-2 text-center">
                                        <flux:button wire:click="loadExisting({{ $nl->id }})" variant="ghost" size="xs" icon="{{ $nl->sent_at ? 'eye' : 'pencil' }}" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Editor --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">
                    @if ($newsletterId)
                        {{ __('Editing Newsletter #:id', ['id' => $newsletterId]) }}
                    @else
                        {{ __('New Newsletter') }}
                    @endif
                </flux:heading>
                @if ($newsletterId && $newsletters->firstWhere('id', $newsletterId)?->sent_at)
                    <flux:badge color="green">{{ __('Sent') }}</flux:badge>
                @endif
            </div>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Subject Line') }}</flux:label>
                    <flux:input wire:model="subject" type="text" />
                    <flux:error name="subject" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Headline') }}</flux:label>
                    <flux:input wire:model="headline" type="text" />
                    <flux:error name="headline" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Introduction') }}</flux:label>
                    <flux:textarea wire:model="intro" rows="3" />
                    <flux:error name="intro" />
                </flux:field>
            </div>
        </div>

        {{-- Sections --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">{{ __('Content Sections') }}</flux:heading>
                <flux:button variant="primary" size="sm" icon="plus" wire:click="openAddSection">
                    {{ __('Add Section') }}
                </flux:button>
            </div>

            @if (empty($sections))
                <div class="rounded-lg border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-600">
                    <flux:icon name="document-plus" class="mx-auto mb-3 size-10 text-zinc-400" />
                    <flux:text>{{ __('No sections yet. Add at least one content section.') }}</flux:text>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($sections as $index => $section)
                        <div wire:key="section-{{ $index }}" class="flex items-start gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                            <div class="flex-1">
                                <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $section['title'] }}</p>
                                <div class="mt-1 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ strip_tags($section['body']) }}
                                </div>
                            </div>
                            <div class="flex shrink-0 gap-1">
                                <flux:button wire:click="editSection({{ $index }})" variant="ghost" size="xs" icon="pencil" />
                                <flux:button wire:click="removeSection({{ $index }})" variant="ghost" size="xs" icon="trash" />
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <flux:error name="sections" />
        </div>

        {{-- Call to Action & Closing --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Call to Action & Closing') }}</flux:heading>

            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('Button Text') }}</flux:label>
                        <flux:input wire:model="ctaText" type="text" />
                        <flux:error name="ctaText" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Button Web Address') }}</flux:label>
                        <flux:input wire:model="ctaUrl" type="url" />
                        <flux:error name="ctaUrl" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>{{ __('Closing Message') }}</flux:label>
                    <flux:textarea wire:model="closing" rows="3" />
                    <flux:error name="closing" />
                </flux:field>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <flux:button wire:click="save" variant="ghost" icon="arrow-down-tray">
                {{ __('Save Draft') }}
            </flux:button>

            <flux:button wire:click="sendPreview" variant="ghost" icon="eye">
                {{ __('Save & Preview Email') }}
            </flux:button>

            @if (! ($newsletterId && $newsletters->firstWhere('id', $newsletterId)?->sent_at))
                <flux:button wire:click="$dispatch('open-modal', { name: 'confirm-send' })" variant="primary" icon="paper-airplane">
                    {{ __('Send to All') }}
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Section Form Modal --}}
    <flux:modal name="section-form" class="w-full max-w-2xl">
        <div class="space-y-4">
            <flux:heading size="lg">
                {{ $editingSectionIndex !== null ? __('Edit Section') : __('Add Section') }}
            </flux:heading>

            <flux:field>
                <flux:label>{{ __('Section Title') }}</flux:label>
                <flux:input wire:model="newSectionTitle" type="text" />
                <flux:error name="newSectionTitle" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Section Body (HTML supported)') }}</flux:label>
                <flux:textarea wire:model="newSectionBody" rows="8" />
                <flux:error name="newSectionBody" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button wire:click="addSection" variant="primary">
                    {{ $editingSectionIndex !== null ? __('Update') : __('Add') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Send Confirmation Modal --}}
    <flux:modal name="confirm-send" class="w-full max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Send Newsletter') }}</flux:heading>
            <flux:text>
                {{ __('This will send the newsletter to all :count verified users. This action cannot be undone.', ['count' => $recipientCount]) }}
            </flux:text>
            <flux:text class="font-semibold">
                {{ __('Have you previewed the email first?') }}
            </flux:text>
            <div class="flex items-center gap-4">
                <flux:button variant="primary" wire:click="sendToAll">{{ __('Send Now') }}</flux:button>
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
