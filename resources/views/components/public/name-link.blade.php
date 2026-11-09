@props(['name' => null, 'url' => null])

@if ($name === null)
    —
@elseif ($url)
    <flux:link :href="$url" variant="subtle">{{ $name }}</flux:link>
@else
    {{ $name }}
@endif
