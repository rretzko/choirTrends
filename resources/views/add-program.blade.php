<x-layouts.app :title="__('Add Program')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <flux:heading size="xl">{{ __('Add Program') }}</flux:heading>

        {{-- Section 1: Upload Concert Program --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-900 p-6">
            <flux:heading size="lg" class="mb-4">{{ __('Upload Concert Program') }}</flux:heading>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-6">
                {{ __('Upload a concert program file (PDF, image, or text) or provide URLs to concert program pages.') }}
            </p>

            <form action="{{ route('addProgram') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                {{-- File Upload Option --}}
                <div>
                    <flux:field>
                        <flux:label>{{ __('Upload File') }}</flux:label>
                        <flux:input
                            type="file"
                            name="program_file"
                            accept=".pdf,.txt,.png,.jpg,.jpeg,.gif,.webp"
                        />
                        <flux:text class="text-xs">
                            {{ __('Accepted formats: PDF, TXT, PNG, JPG, JPEG, GIF, WEBP') }}
                        </flux:text>
                    </flux:field>
                </div>

                <flux:separator />

                {{-- OR divider --}}
                <div class="flex items-center gap-4">
                    <div class="flex-1 border-t border-zinc-200 dark:border-zinc-700"></div>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('OR') }}</span>
                    <div class="flex-1 border-t border-zinc-200 dark:border-zinc-700"></div>
                </div>

                {{-- URI Input Option --}}
                <div>
                    <flux:field>
                        <flux:label>{{ __('Program URLs') }}</flux:label>
                        <flux:textarea
                            name="program_uris"
                            rows="4"
                            placeholder="https://example.com/program1&#10;https://example.com/program2"
                        />
                        <flux:text class="text-xs">
                            {{ __('Enter one URL per line for concert program pages') }}
                        </flux:text>
                    </flux:field>
                </div>

                {{-- Submit Button --}}
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" href="{{ route('dashboard') }}">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Process Program') }}
                    </flux:button>
                </div>
            </form>
        </div>

        {{-- Section 2: Confirmation Dialog (Placeholder) --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-900 p-6">
            <flux:heading size="lg" class="mb-4">{{ __('Confirm Program Contents') }}</flux:heading>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-6">
                {{ __('Review and confirm the information extracted from the concert program.') }}
            </p>

            {{-- Dialog Area - To be implemented --}}
            <div class="min-h-[300px] rounded-lg border border-dashed border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800/50 flex items-center justify-center">
                <div class="text-center text-zinc-500 dark:text-zinc-400">
                    <svg class="mx-auto h-12 w-12 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <p class="text-sm font-medium">{{ __('Confirmation dialog will appear here') }}</p>
                    <p class="text-xs mt-1">{{ __('After processing, you\'ll review the extracted information') }}</p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
