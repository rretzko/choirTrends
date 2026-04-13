<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <flux:heading size="xl">{{ __("User's Guide") }}</flux:heading>
                <flux:text class="mt-1">{{ __('Everything you need to know to get the most out of ChoirTrends.') }}</flux:text>
            </div>
            <a href="{{ route('user-guide.pdf') }}" class="inline-flex items-center gap-2">
                <flux:button variant="filled" size="sm" icon="arrow-down-tray">
                    {{ __('Download PDF') }}
                </flux:button>
            </a>
        </div>

        <div class="flex flex-col gap-6 lg:flex-row">
            {{-- Table of Contents --}}
            <nav class="w-full shrink-0 lg:w-64">
                <div class="sticky top-6 space-y-4">
                    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="mb-3 flex items-center gap-2">
                            <flux:icon name="book-open" class="size-4 text-teal-600 dark:text-teal-400" />
                            <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Contents') }}</span>
                        </div>
                        <ul class="space-y-0.5">
                            @foreach ($sections as $section)
                                <li wire:key="toc-{{ $section->id }}">
                                    <button
                                        wire:click="setSection('{{ $section->slug }}')"
                                        class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm transition-all
                                            {{ $activeSlug === $section->slug
                                                ? 'bg-teal-50 font-medium text-teal-700 shadow-sm dark:bg-teal-900/30 dark:text-teal-300'
                                                : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200' }}"
                                    >
                                        <span class="flex size-5 shrink-0 items-center justify-center rounded text-xs font-medium {{ $activeSlug === $section->slug ? 'bg-teal-600 text-white dark:bg-teal-500' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                            {{ $loop->iteration }}
                                        </span>
                                        {{ $section->title }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </nav>

            {{-- Content Area --}}
            <div class="min-w-0 flex-1" x-data x-on:scroll-to-section.window="$el.scrollIntoView({ behavior: 'smooth', block: 'start' })">
                @if ($activeSection)
                    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                        {{-- Section Header --}}
                        <div class="border-b border-zinc-200 px-6 py-5 dark:border-zinc-700 sm:px-8">
                            @php
                                $currentIndex = $sections->search(fn ($s) => $s->slug === $activeSlug);
                            @endphp
                            <div class="flex items-center gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-teal-600 text-sm font-bold text-white dark:bg-teal-500">
                                    {{ $currentIndex + 1 }}
                                </span>
                                <flux:heading size="lg">{{ $activeSection->title }}</flux:heading>
                            </div>
                        </div>

                        {{-- Section Body --}}
                        <div class="px-6 py-6 sm:px-8 sm:py-8">
                            <div class="guide-content prose max-w-none dark:prose-invert
                                prose-headings:text-zinc-900 dark:prose-headings:text-zinc-100
                                prose-h3:mt-8 prose-h3:mb-3 prose-h3:text-lg prose-h3:font-semibold
                                prose-h4:mt-6 prose-h4:mb-2 prose-h4:text-base prose-h4:font-semibold
                                prose-p:text-zinc-600 prose-p:leading-relaxed dark:prose-p:text-zinc-400
                                prose-li:text-zinc-600 dark:prose-li:text-zinc-400
                                prose-a:text-teal-600 prose-a:no-underline hover:prose-a:underline dark:prose-a:text-teal-400
                                prose-strong:text-zinc-800 dark:prose-strong:text-zinc-200
                                prose-ol:space-y-2 prose-ul:space-y-1">
                                {!! $activeSection->body !!}
                            </div>
                        </div>

                        {{-- Section Navigation --}}
                        <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700 sm:px-8">
                            <div class="flex items-center justify-between">
                                @php
                                    $prevSection = $currentIndex > 0 ? $sections[$currentIndex - 1] : null;
                                    $nextSection = $currentIndex < $sections->count() - 1 ? $sections[$currentIndex + 1] : null;
                                @endphp

                                <div>
                                    @if ($prevSection)
                                        <button wire:click="setSection('{{ $prevSection->slug }}')" class="group inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-zinc-500 transition-all hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                                            <flux:icon name="chevron-left" class="size-4 transition-transform group-hover:-translate-x-0.5" />
                                            <span>
                                                <span class="block text-xs text-zinc-400 dark:text-zinc-500">Previous</span>
                                                {{ $prevSection->title }}
                                            </span>
                                        </button>
                                    @endif
                                </div>

                                <div>
                                    @if ($nextSection)
                                        <button wire:click="setSection('{{ $nextSection->slug }}')" class="group inline-flex items-center gap-2 rounded-lg px-3 py-2 text-right text-sm text-zinc-500 transition-all hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                                            <span>
                                                <span class="block text-xs text-zinc-400 dark:text-zinc-500">Next</span>
                                                {{ $nextSection->title }}
                                            </span>
                                            <flux:icon name="chevron-right" class="size-4 transition-transform group-hover:translate-x-0.5" />
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:text>{{ __('No guide content available yet. Check back soon!') }}</flux:text>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            /* Tip/callout boxes */
            .guide-content .guide-tip {
                border-radius: 0.5rem;
                padding: 1rem 1.25rem;
                margin: 1.25rem 0;
                display: flex;
                gap: 0.75rem;
                align-items: flex-start;
            }
            .guide-content .guide-tip-icon {
                flex-shrink: 0;
                width: 1.5rem;
                height: 1.5rem;
                margin-top: 0.125rem;
            }
            .guide-content .guide-tip-success {
                background-color: rgb(240, 253, 244);
                border: 1px solid rgb(187, 247, 208);
                color: rgb(21, 128, 61);
            }
            .guide-content .guide-tip-info {
                background-color: rgb(239, 246, 255);
                border: 1px solid rgb(191, 219, 254);
                color: rgb(29, 78, 216);
            }
            .guide-content .guide-tip-warning {
                background-color: rgb(255, 251, 235);
                border: 1px solid rgb(253, 230, 138);
                color: rgb(161, 98, 7);
            }
            .guide-content .guide-tip p {
                margin: 0 !important;
                color: inherit !important;
            }

            /* Dark mode tips */
            .dark .guide-content .guide-tip-success {
                background-color: rgba(21, 128, 61, 0.1);
                border-color: rgba(34, 197, 94, 0.3);
                color: rgb(134, 239, 172);
            }
            .dark .guide-content .guide-tip-info {
                background-color: rgba(29, 78, 216, 0.1);
                border-color: rgba(59, 130, 246, 0.3);
                color: rgb(147, 197, 253);
            }
            .dark .guide-content .guide-tip-warning {
                background-color: rgba(161, 98, 7, 0.1);
                border-color: rgba(245, 158, 11, 0.3);
                color: rgb(252, 211, 77);
            }

            /* Step cards */
            .guide-content .guide-step {
                border-radius: 0.5rem;
                border: 1px solid rgb(228, 228, 231);
                padding: 1.25rem;
                margin: 1rem 0;
                display: flex;
                gap: 1rem;
                align-items: flex-start;
            }
            .dark .guide-content .guide-step {
                border-color: rgb(63, 63, 70);
            }
            .guide-content .guide-step-number {
                flex-shrink: 0;
                width: 2rem;
                height: 2rem;
                border-radius: 9999px;
                background-color: rgb(13, 148, 136);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                font-size: 0.875rem;
            }
            .guide-content .guide-step p {
                margin: 0 !important;
            }
            .guide-content .guide-step h4 {
                margin: 0 0 0.25rem 0 !important;
            }

            /* Feature item cards (icon + text) */
            .guide-content .guide-feature {
                display: flex;
                gap: 0.75rem;
                align-items: flex-start;
                padding: 0.75rem 1rem;
                border-radius: 0.5rem;
                margin: 0.5rem 0;
                background-color: rgb(250, 250, 250);
                border: 1px solid rgb(244, 244, 245);
            }
            .dark .guide-content .guide-feature {
                background-color: rgba(39, 39, 42, 0.5);
                border-color: rgb(63, 63, 70);
            }
            .guide-content .guide-feature p {
                margin: 0 !important;
            }
            .guide-content .guide-feature-icon {
                flex-shrink: 0;
                font-size: 1.25rem;
                margin-top: 0.125rem;
            }

            /* Screenshot placeholders */
            .guide-content .guide-screenshot {
                text-align: center;
                padding: 2rem;
                margin: 1.5rem 0;
                background-color: rgb(250, 250, 250);
                border: 2px dashed rgb(212, 212, 216);
                border-radius: 0.75rem;
                color: rgb(161, 161, 170);
                font-size: 0.875rem;
                font-style: italic;
            }
            .dark .guide-content .guide-screenshot {
                background-color: rgba(39, 39, 42, 0.5);
                border-color: rgb(63, 63, 70);
                color: rgb(113, 113, 122);
            }

            /* Sidebar reference list */
            .guide-content .guide-sidebar-ref {
                display: flex;
                gap: 0.75rem;
                align-items: flex-start;
                padding: 0.625rem 0;
                border-bottom: 1px solid rgb(244, 244, 245);
            }
            .dark .guide-content .guide-sidebar-ref {
                border-bottom-color: rgb(39, 39, 42);
            }
            .guide-content .guide-sidebar-ref:last-child {
                border-bottom: none;
            }
            .guide-content .guide-sidebar-ref p {
                margin: 0 !important;
            }
        </style>
    @endpush
</div>
