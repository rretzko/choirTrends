<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="flex min-h-screen flex-col bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 antialiased">

        <header class="sticky top-0 z-50 backdrop-blur-md bg-white/80 dark:bg-zinc-950/80 border-b border-zinc-200 dark:border-zinc-800">
            <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <img src="{{ asset('favicon.svg') }}" alt="{{ config('app.name') }}" class="size-9">
                    <span class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>
                </a>
                @if (Route::has('login'))
                    <nav class="flex items-center gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}"
                               class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-teal-700 text-white text-sm font-medium hover:bg-teal-600 transition-colors">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="px-5 py-2 rounded-lg bg-teal-700 text-white text-sm font-medium hover:bg-teal-600 transition-colors">
                                    Get Started
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </div>
        </header>

        <main class="flex-1 w-full max-w-6xl mx-auto px-6 py-8">
            {{ $slot }}
        </main>

        @include('partials.footer')

        @fluxScripts
    </body>
</html>
