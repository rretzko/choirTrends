{{-- Sticky footer: use mt-auto on this or flex-col + flex-1 on the parent container --}}
<footer class="mt-auto border-t border-zinc-200 dark:border-zinc-700 py-3 px-6">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-zinc-500 dark:text-zinc-400">
        <span>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>

        <div class="flex items-center gap-4">
            <flux:modal.trigger name="about-modal">
                <button type="button" class="hover:text-zinc-700 dark:hover:text-zinc-200 transition-colors cursor-pointer">
                    About
                </button>
            </flux:modal.trigger>

            <span>v{{ config('app.version') }}</span>

            <a href="https://mfrholdings.com" target="_blank" rel="noopener noreferrer" class="hover:text-zinc-700 dark:hover:text-zinc-200 transition-colors">
                Powered by MFR Holdings, LLC
            </a>
        </div>
    </div>
</footer>

<flux:modal name="about-modal" class="md:w-96">
    <div class="space-y-4">
        <flux:heading size="lg">About {{ config('app.name') }}</flux:heading>
        <flux:text>
            Information about the app to be completed later.
        </flux:text>
    </div>
</flux:modal>
