@props([
    'youtubeUrl' => null,
    'model' => 'youtubeUrlInput',
    'saveAction',
    'removeAction',
])

@php
    $youTube = \App\Services\YouTubeUrl::parse($youtubeUrl);
@endphp

<div class="space-y-2">
    <flux:field>
        <flux:label>{{ __('YouTube Link') }}</flux:label>
        <div class="flex items-start gap-2">
            <flux:input
                wire:model="{{ $model }}"
                wire:keydown.enter.prevent="{{ $saveAction }}"
                type="url"
                icon="link"
                placeholder="https://www.youtube.com/watch?v=…"
                class="flex-1"
            />
            <flux:button wire:click="{{ $saveAction }}" type="button" variant="primary" size="base">
                {{ $youTube ? __('Update') : __('Add') }}
            </flux:button>
        </div>
        <flux:error :name="$model" />
    </flux:field>

    @if ($youTube)
        <div class="flex items-center gap-3">
            <a href="{{ $youTube->watchUrl() }}" target="_blank" rel="noopener noreferrer" class="shrink-0">
                <img src="{{ $youTube->thumbnailUrl() }}" alt="{{ __('YouTube video thumbnail') }}" loading="lazy" class="h-14 w-24 rounded object-cover" />
            </a>
            <div class="flex min-w-0 flex-1 items-center gap-1">
                <a href="{{ $youTube->watchUrl() }}" target="_blank" rel="noopener noreferrer" class="truncate text-sm text-teal-600 hover:underline dark:text-teal-400">
                    {{ $youTube->watchUrl() }}
                </a>
            </div>
            <flux:button wire:click="{{ $removeAction }}" wire:confirm="{{ __('Remove this YouTube link?') }}" type="button" variant="ghost" size="sm" class="text-red-600 hover:text-red-500">
                <flux:icon name="trash" class="size-4" />
                {{ __('Remove') }}
            </flux:button>
        </div>
    @endif
</div>
