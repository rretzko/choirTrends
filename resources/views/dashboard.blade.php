<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>

        <livewire:onboarding.welcome-modal />
        <livewire:onboarding.setup-checklist />

        <livewire:dashboard />
    </div>
</x-layouts.app>
