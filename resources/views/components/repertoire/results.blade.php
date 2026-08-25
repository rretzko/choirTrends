@props(['result'])

<div class="space-y-4">
    @if (! empty($result->response['results'] ?? []))
        <div class="flex items-center gap-2 text-xs text-neutral-400 dark:text-neutral-500">
            <flux:icon name="sparkles" class="size-3.5" />
            {{ __('AI-estimated difficulty and matches — not a substitute for reviewing the score yourself.') }}
        </div>
    @endif

    @if (! empty($result->response['query_interpretation']['interpretation_notes'] ?? null))
        <p class="text-sm text-neutral-500 dark:text-neutral-400">
            {{ $result->response['query_interpretation']['interpretation_notes'] }}
        </p>
    @endif

    @forelse ($result->response['results'] ?? [] as $song)
        <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700" wire:key="ai-result-{{ $loop->index }}">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ $song['song_title'] }}</div>
                    <div class="text-sm text-neutral-500 dark:text-neutral-400">
                        {{ $song['composer'] }}
                        @if (! empty($song['arranger']))
                            / arr. {{ $song['arranger'] }}
                        @endif
                        @if (! empty($song['voicing']))
                            &middot; {{ $song['voicing'] }}
                        @endif
                    </div>
                </div>
                <flux:badge size="sm" :color="$song['source'] === 'internal_catalog' ? 'teal' : 'zinc'">
                    {{ $song['source'] === 'internal_catalog' ? __('In ChoirTrends') : __('Web') }}
                </flux:badge>
            </div>

            <p class="mt-2 text-sm text-neutral-700 dark:text-neutral-300">{{ $song['fit_rationale'] }}</p>

            <div class="mt-3 grid grid-cols-4 gap-2 text-center text-xs">
                @foreach (['soprano' => __('Soprano'), 'alto' => __('Alto'), 'tenor' => __('Tenor'), 'bass' => __('Bass')] as $part => $label)
                    <div class="rounded-lg bg-neutral-50 px-2 py-1.5 dark:bg-neutral-800">
                        <div class="text-neutral-400 dark:text-neutral-500">{{ $label }}</div>
                        <div class="font-medium capitalize text-neutral-700 dark:text-neutral-200">{{ $song['difficulty_by_part'][$part] ?? 'unknown' }}</div>
                    </div>
                @endforeach
            </div>

            @if (! empty($song['tags']))
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach ($song['tags'] as $tag)
                        <flux:badge size="sm" rounded>{{ $tag }}</flux:badge>
                    @endforeach
                </div>
            @endif

            @if (! empty($song['youtube_url']))
                <a href="{{ $song['youtube_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex items-center gap-1.5 text-sm text-red-600 hover:text-red-500">
                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    {{ __('Watch on YouTube') }}
                </a>
            @endif
        </div>
    @empty
        <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('No matches found. Try rephrasing your search.') }}</p>
    @endforelse
</div>
