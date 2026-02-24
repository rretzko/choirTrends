<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>

    <flux:footer class="!p-0">
        @include('partials.footer')
    </flux:footer>
</x-layouts.app.sidebar>
