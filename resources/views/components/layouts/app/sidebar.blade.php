<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        @stack('styles')
    </head>
    <body class="min-h-screen bg-blue-100 dark:bg-zinc-800">
        <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 rtl:space-x-reverse in-data-flux-sidebar-collapsed-desktop:hidden" wire:navigate>
                    <x-app-logo />
                </a>
                <flux:sidebar.collapse class="in-data-flux-sidebar-collapsed-desktop:!opacity-100 in-data-flux-sidebar-collapsed-desktop:!relative" />
            </flux:sidebar.header>

            @if (session()->has('impersonating_from'))
                <div class="rounded-lg bg-amber-500 px-3 py-2 text-sm font-medium text-black in-data-flux-sidebar-collapsed-desktop:hidden">
                    <div>{{ __('Impersonating') }}</div>
                    <div class="font-semibold">{{ auth()->user()->name }}</div>
                    <form method="POST" action="{{ route('founder.impersonate.stop') }}" class="mt-2">
                        @csrf
                        <flux:button type="submit" variant="filled" size="sm" class="!bg-amber-700 !text-white hover:!bg-amber-800">
                            {{ __('Stop') }}
                        </flux:button>
                    </form>
                </div>
            @endif

            <flux:sidebar.nav>
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate class="mb-4">{{ __('Dashboard') }}</flux:sidebar.item>
                <flux:sidebar.item icon="document-text" :href="route('programs.index')" :current="request()->routeIs('programs.*')" wire:navigate>{{ __('Programs') }}</flux:sidebar.item>
                <flux:sidebar.item icon="arrow-up-tray" :href="route('addProgram')" :current="request()->routeIs('addProgram')" wire:navigate>{{ __('Add Program') }}</flux:sidebar.item>
                <flux:sidebar.item icon="musical-note" :href="route('artists.index')" :current="request()->routeIs('artists.*')" wire:navigate>{{ __('Composers/Arrangers') }}</flux:sidebar.item>
                <flux:sidebar.item icon="user-group" :href="route('ensembles.index')" :current="request()->routeIs('ensembles.*')" wire:navigate>{{ __('Ensembles') }}</flux:sidebar.item>
                <flux:sidebar.item icon="academic-cap" :href="route('schools.index')" :current="request()->routeIs('schools.*')" wire:navigate>{{ __('Schools') }}</flux:sidebar.item>
                <flux:sidebar.item icon="queue-list" :href="route('song-titles.index')" :current="request()->routeIs('song-titles.*')" wire:navigate>{{ __('Song Titles') }}</flux:sidebar.item>
                <flux:separator class="my-2"/>
                <flux:sidebar.item icon="book-open" :href="route('user-guide.index')" :current="request()->routeIs('user-guide.*')" wire:navigate>{{ __("User's Guide") }}</flux:sidebar.item>
                <flux:separator class="my-2"/>
                <flux:sidebar.item icon="bug-ant" :href="route('feedback.index') . '?tab=report'" :current="request()->routeIs('feedback.*')" wire:navigate>{{ __('Feedback') }}</flux:sidebar.item>
                <flux:sidebar.item icon="light-bulb" :href="route('quick-tips.index')" :current="request()->routeIs('quick-tips.*')" wire:navigate>{{ __('Quick Tips') }}</flux:sidebar.item>
                <flux:sidebar.group expandable :expanded="false" icon="book-open" heading="Documentation" class="grid">
                    <flux:sidebar.item icon="map" :href="route('documentation.site-guide')" :current="request()->routeIs('documentation.site-guide')" wire:navigate>{{ __('Site Guide') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="arrow-up-tray" :href="route('documentation.add-program-guide')" :current="request()->routeIs('documentation.add-program-guide')" wire:navigate>{{ __('Add Program Guide') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('documentation.programs-guide')" :current="request()->routeIs('documentation.programs-guide')" wire:navigate>{{ __('Programs Guide') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="musical-note" :href="route('documentation.composers-arrangers-guide')" :current="request()->routeIs('documentation.composers-arrangers-guide')" wire:navigate>{{ __('Composers/Arrangers Guide') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="user-group" :href="route('documentation.ensembles-guide')" :current="request()->routeIs('documentation.ensembles-guide')" wire:navigate>{{ __('Ensembles Guide') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="academic-cap" :href="route('documentation.schools-guide')" :current="request()->routeIs('documentation.schools-guide')" wire:navigate>{{ __('Schools Guide') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="queue-list" :href="route('documentation.song-titles-guide')" :current="request()->routeIs('documentation.song-titles-guide')" wire:navigate>{{ __('Song Titles Guide') }}</flux:sidebar.item>
                </flux:sidebar.group>
                <flux:sidebar.item icon="moon" x-data x-show="!$flux.dark" x-on:click.prevent="$flux.dark = true">{{ __('Dark Mode') }}</flux:sidebar.item>
                <flux:sidebar.item icon="sun" x-data x-show="$flux.dark" x-on:click.prevent="$flux.dark = false">{{ __('Light Mode') }}</flux:sidebar.item>
            </flux:sidebar.nav>

            <flux:sidebar.spacer />

            @if (auth()->user()->isFounder() && ! session()->has('impersonating_from'))
                <flux:sidebar.nav>
                    <flux:sidebar.group expandable :expanded="false" icon="shield-check" heading="Founder" class="grid">
                        <flux:sidebar.item icon="chart-bar" :href="route('founder.dashboard')" :current="request()->routeIs('founder.dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="user-plus" :href="route('founder.addProgram')" :current="request()->routeIs('founder.addProgram*')" wire:navigate>{{ __('Add Program for User') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="document-duplicate" :href="route('founder.duplicates')" :current="request()->routeIs('founder.duplicates')" wire:navigate>{{ __('Duplicates') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="identification" :href="route('founder.impersonate')" :current="request()->routeIs('founder.impersonate')" wire:navigate>{{ __('Impersonate User') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="key" :href="route('founder.changeUserPassword')" :current="request()->routeIs('founder.changeUserPassword')" wire:navigate>{{ __('Change User Password!') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="clipboard-document-list" :href="route('founder.issues')" :current="request()->routeIs('founder.issues')" wire:navigate>{{ __('Issues') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="magnifying-glass" :href="route('founder.songTitleConflicts')" :current="request()->routeIs('founder.songTitleConflicts')" wire:navigate>{{ __('Song Title Conflicts') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="light-bulb" :href="route('founder.quickTips')" :current="request()->routeIs('founder.quickTips')" wire:navigate>{{ __('Quick Tips') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="envelope" :href="route('founder.newsletter')" :current="request()->routeIs('founder.newsletter')" wire:navigate>{{ __('Newsletter') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="book-open" :href="route('founder.userGuide')" :current="request()->routeIs('founder.userGuide')" wire:navigate>{{ __("User's Guide") }}</flux:sidebar.item>
                        <flux:sidebar.item icon="users" :href="route('founder.users')" :current="request()->routeIs('founder.users')" wire:navigate>{{ __('Users') }}</flux:sidebar.item>
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endif

            <!-- Desktop User Menu -->
            <flux:dropdown class="max-lg:hidden" position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Profile') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            @if (session()->has('impersonating_from'))
                <form method="POST" action="{{ route('founder.impersonate.stop') }}" class="flex items-center gap-2">
                    @csrf
                    <flux:badge color="amber" size="sm">{{ __('Impersonating :name', ['name' => auth()->user()->name]) }}</flux:badge>
                    <flux:button type="submit" variant="filled" size="xs" class="!bg-amber-700 !text-white hover:!bg-amber-800">
                        {{ __('Stop') }}
                    </flux:button>
                </form>
            @endif

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Profile') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
        @stack('scripts')
    </body>
</html>
