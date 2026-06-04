<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
    <title>{{ $program->program?->event_name ?? 'Digital Program' }} — ChoirTrends</title>
</head>
<body class="flex min-h-screen items-center justify-center bg-zinc-900 text-zinc-100">
    <div class="space-y-4 text-center">
        <flux:icon.musical-note class="mx-auto size-16 text-zinc-500" />
        <flux:heading size="xl">{{ $program->program?->event_name ?? __('Digital Program') }}</flux:heading>
        <flux:text class="text-zinc-400">{{ __('Full digital program view coming soon.') }}</flux:text>
        <flux:text class="font-mono text-xs text-zinc-600">{{ $program->slug }}</flux:text>
    </div>
</body>
</html>
