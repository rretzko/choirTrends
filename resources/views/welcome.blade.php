<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="flex min-h-screen flex-col bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 antialiased">

        {{-- Navigation --}}
        <header class="sticky top-0 z-50 backdrop-blur-md bg-white/80 dark:bg-zinc-950/80 border-b border-zinc-200 dark:border-zinc-800">
            <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('favicon.svg') }}" alt="{{ config('app.name') }}" class="size-9">
                    <span class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>
                </div>
                @if (Route::has('login'))
                    <nav class="flex items-center gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}"
                               class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-teal-700 text-white text-sm font-medium hover:bg-teal-600 transition-colors">
                                Dashboard
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
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

        {{-- Hero Section --}}
        <section class="relative overflow-hidden">
            {{-- Background decoration --}}
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <svg class="absolute -top-24 -right-24 w-[600px] h-[600px] opacity-[0.04] dark:opacity-[0.06]" viewBox="0 0 512 512" fill="none">
                    <circle cx="256" cy="256" r="240" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M80 340 Q256 420 432 340" fill="none" stroke="currentColor" stroke-width="2"/>
                    <path d="M65 365 Q256 445 447 365" fill="none" stroke="currentColor" stroke-width="2"/>
                    <path d="M55 390 Q256 470 457 390" fill="none" stroke="currentColor" stroke-width="2"/>
                    <path d="M55 415 Q256 495 457 415" fill="none" stroke="currentColor" stroke-width="2"/>
                    <path d="M70 438 Q256 515 442 438" fill="none" stroke="currentColor" stroke-width="2"/>
                </svg>
                <svg class="absolute -bottom-32 -left-32 w-[400px] h-[400px] opacity-[0.03] dark:opacity-[0.05]" viewBox="0 0 512 512" fill="none">
                    <circle cx="256" cy="256" r="240" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M80 340 Q256 420 432 340" fill="none" stroke="currentColor" stroke-width="2"/>
                    <path d="M65 365 Q256 445 447 365" fill="none" stroke="currentColor" stroke-width="2"/>
                    <path d="M55 390 Q256 470 457 390" fill="none" stroke="currentColor" stroke-width="2"/>
                </svg>
            </div>

            <div class="max-w-6xl mx-auto px-6 pt-20 pb-16 lg:pt-28 lg:pb-24">
                <div class="max-w-3xl mx-auto text-center">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-50 dark:bg-teal-950/50 border border-teal-200 dark:border-teal-800 text-teal-700 dark:text-teal-300 text-xs font-medium mb-6">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z"/></svg>
                        AI-Powered Choral Concert Program Analysis
                    </div>

                    <h1 class="text-4xl lg:text-6xl font-semibold tracking-tight leading-tight mb-6">
                        Discover What's <span class="bg-gradient-to-r from-teal-600 to-cyan-500 bg-clip-text text-transparent">Trending</span> in Choral Music
                    </h1>

                    <p>
                        Submit your past concert programs to discover trends in repertoire, composers, and arrangers across the choral community.
                    </p>

                    <a href="https://choirtrends.s3.us-east-1.amazonaws.com/mp4s/ChoirTrends-v3.mp4" target="_blank" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-white/10 dark:bg-white/5 border border-teal-300/40 dark:border-teal-600/40 text-teal-700 dark:text-teal-300 text-sm font-medium hover:bg-teal-50 dark:hover:bg-teal-950/50 transition-colors backdrop-blur-sm my-6">
                        <svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        Watch the Overview
                    </a>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        @if (Route::has('register'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="inline-flex items-center gap-2 px-8 py-3 rounded-xl bg-gradient-to-r from-teal-700 to-teal-600 text-white font-medium hover:from-teal-600 hover:to-teal-500 transition-all shadow-lg shadow-teal-500/20">
                                    Go to Dashboard
                                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                </a>
                            @else
                                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-8 py-3 rounded-xl bg-gradient-to-r from-teal-700 to-teal-600 text-white font-medium hover:from-teal-600 hover:to-teal-500 transition-all shadow-lg shadow-teal-500/20">
                                    Start for Free
                                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                </a>
                                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-8 py-3 rounded-xl border border-zinc-300 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 font-medium hover:bg-zinc-50 dark:hover:bg-zinc-900 transition-colors">
                                    Sign In
                                </a>
                            @endauth
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- How It Works --}}
        <section class="py-16 lg:py-24 bg-zinc-50 dark:bg-zinc-900/50">
            <div class="max-w-6xl mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl lg:text-4xl font-semibold tracking-tight mb-4">How It Works</h2>
                    <p class="text-zinc-600 dark:text-zinc-400 text-lg max-w-xl mx-auto">Three simple steps to contribute to and benefit from choral music insights.</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8 lg:gap-12">
                    {{-- Step 1 --}}
                    <div class="relative text-center">
                        <div class="inline-flex items-center justify-center size-16 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-700 text-white mb-6 shadow-lg shadow-teal-500/20">
                            <svg class="size-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-3">Upload a Program</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">Upload a concert program as a PDF, image, or web address. That's all you need to get started.</p>
                    </div>

                    {{-- Step 2 --}}
                    <div class="relative text-center">
                        <div class="inline-flex items-center justify-center size-16 rounded-2xl bg-gradient-to-br from-cyan-500 to-blue-600 text-white mb-6 shadow-lg shadow-cyan-500/20">
                            <svg class="size-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z"/></svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-3">AI Extracts the Data</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">Our AI reads your program and extracts song titles, composers, arrangers, ensembles, and more.</p>
                    </div>

                    {{-- Step 3 --}}
                    <div class="relative text-center">
                        <div class="inline-flex items-center justify-center size-16 rounded-2xl bg-gradient-to-br from-violet-500 to-purple-700 text-white mb-6 shadow-lg shadow-violet-500/20">
                            <svg class="size-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6"/></svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-3">Explore the Trends</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">Browse aggregated data to discover trending songs, popular composers, and what peers are performing.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Sample Data Preview --}}
        <section class="py-16 lg:py-24">
            <div class="max-w-6xl mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl lg:text-4xl font-semibold tracking-tight mb-4">A Growing Community</h2>
                    <p class="text-zinc-600 dark:text-zinc-400 text-lg max-w-xl mx-auto">Choral directors are already sharing programs and discovering insights.</p>
                </div>

                {{-- Stats Grid --}}
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-16">
                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 text-center">
                        <div class="flex items-center justify-center size-10 rounded-lg bg-green-100 dark:bg-green-900/50 mx-auto mb-3">
                            <svg class="size-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                        </div>
                        <div class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ \App\Models\Program::count() }}</div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Programs</div>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 text-center">
                        <div class="flex items-center justify-center size-10 rounded-lg bg-rose-100 dark:bg-rose-900/50 mx-auto mb-3">
                            <svg class="size-5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 11-.99-3.467l2.31-.66a2.25 2.25 0 001.632-2.163zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 01-.99-3.467l2.31-.66A2.25 2.25 0 009 15.553z"/></svg>
                        </div>
                        <div class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ \App\Models\SongTitle::count() }}</div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Songs</div>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 text-center">
                        <div class="flex items-center justify-center size-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 mx-auto mb-3">
                            <svg class="size-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        </div>
                        <div class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ \App\Models\Artist::count() }}</div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Composers &amp; Arrangers</div>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 text-center">
                        <div class="flex items-center justify-center size-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 mx-auto mb-3">
                            <svg class="size-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342"/></svg>
                        </div>
                        <div class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ \App\Models\School::count() }}</div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Schools</div>
                    </div>
                </div>

                {{-- Sample Program Card --}}
                <div class="max-w-3xl mx-auto">
                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden shadow-sm">
                        <div class="bg-gradient-to-r from-teal-700 to-teal-600 px-6 py-4">
                            <div class="flex items-center gap-3 text-white">
                                <svg class="size-5 opacity-80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                <span class="font-medium">Sample Concert Program</span>
                            </div>
                        </div>
                        <div class="px-6 py-5">
                            <h3 class="text-lg font-semibold mb-1">Spring Choral Festival 2025</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-5">Northview High School &mdash; May 10, 2025</p>

                            <div class="space-y-4">
                                {{-- Ensemble 1 --}}
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="size-2 rounded-full bg-purple-500"></div>
                                        <span class="text-sm font-medium text-purple-700 dark:text-purple-400">Concert Choir</span>
                                    </div>
                                    <div class="ml-4 space-y-1.5">
                                        <div class="flex items-baseline justify-between text-sm">
                                            <span class="text-zinc-800 dark:text-zinc-200">Lux Aurumque</span>
                                            <span class="text-zinc-500 dark:text-zinc-400 text-xs">Eric Whitacre</span>
                                        </div>
                                        <div class="flex items-baseline justify-between text-sm">
                                            <span class="text-zinc-800 dark:text-zinc-200">Sure On This Shining Night</span>
                                            <span class="text-zinc-500 dark:text-zinc-400 text-xs">Morten Lauridsen</span>
                                        </div>
                                        <div class="flex items-baseline justify-between text-sm">
                                            <span class="text-zinc-800 dark:text-zinc-200">Sicut Cervus</span>
                                            <span class="text-zinc-500 dark:text-zinc-400 text-xs">Giovanni Pierluigi da Palestrina</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Ensemble 2 --}}
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="size-2 rounded-full bg-cyan-500"></div>
                                        <span class="text-sm font-medium text-cyan-700 dark:text-cyan-400">Women's Ensemble</span>
                                    </div>
                                    <div class="ml-4 space-y-1.5">
                                        <div class="flex items-baseline justify-between text-sm">
                                            <span class="text-zinc-800 dark:text-zinc-200">Daemon Irrepit Callidus</span>
                                            <span class="text-zinc-500 dark:text-zinc-400 text-xs">Gy&ouml;rgy Orb&aacute;n</span>
                                        </div>
                                        <div class="flex items-baseline justify-between text-sm">
                                            <span class="text-zinc-800 dark:text-zinc-200">Ave Maria</span>
                                            <span class="text-zinc-500 dark:text-zinc-400 text-xs">Franz Biebl</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 pt-4 border-t border-zinc-100 dark:border-zinc-800 flex items-center gap-2 text-xs text-zinc-400 dark:text-zinc-500">
                                <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                                Extracted automatically by AI from an uploaded PDF
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Features --}}
        <section class="py-16 lg:py-24 bg-zinc-50 dark:bg-zinc-900/50">
            <div class="max-w-6xl mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl lg:text-4xl font-semibold tracking-tight mb-4">Built for Choral Directors</h2>
                    <p class="text-zinc-600 dark:text-zinc-400 text-lg max-w-xl mx-auto">Everything you need to stay informed about the choral music landscape.</p>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-8">
                        <div class="flex items-center justify-center size-12 rounded-xl bg-teal-100 dark:bg-teal-900/50 mb-5">
                            <svg class="size-6 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 11-.99-3.467l2.31-.66a2.25 2.25 0 001.632-2.163zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 01-.99-3.467l2.31-.66A2.25 2.25 0 009 15.553z"/></svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2">Song Trend Discovery</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">See which songs are being performed the most across programs. Find new repertoire ideas or validate your own selections.</p>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-8">
                        <div class="flex items-center justify-center size-12 rounded-xl bg-blue-100 dark:bg-blue-900/50 mb-5">
                            <svg class="size-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2">Composer &amp; Arranger Database</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">Explore a growing database of composers and arrangers, and see which of their works are being performed this season.</p>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-8">
                        <div class="flex items-center justify-center size-12 rounded-xl bg-purple-100 dark:bg-purple-900/50 mb-5">
                            <svg class="size-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2">Community-Driven Insights</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">Every director who shares a program helps the entire community. The more programs shared, the richer the data for everyone.</p>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-8">
                        <div class="flex items-center justify-center size-12 rounded-xl bg-amber-100 dark:bg-amber-900/50 mb-5">
                            <svg class="size-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6"/></svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2">School &amp; Ensemble Tracking</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">Track programs by school and ensemble type. See what concert choirs, chamber groups, and vocal jazz ensembles are performing.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="py-16 lg:py-24">
            <div class="max-w-3xl mx-auto px-6 text-center">
                <div class="rounded-3xl bg-gradient-to-br from-teal-700 via-teal-600 to-cyan-600 p-12 lg:p-16 shadow-2xl shadow-teal-500/20 relative overflow-hidden">
                    {{-- Decorative staff lines --}}
                    <svg class="absolute inset-0 w-full h-full opacity-10 pointer-events-none" viewBox="0 0 800 400" fill="none" preserveAspectRatio="none">
                        <path d="M0 100 Q400 150 800 80" stroke="white" stroke-width="2" fill="none"/>
                        <path d="M0 150 Q400 200 800 130" stroke="white" stroke-width="2" fill="none"/>
                        <path d="M0 200 Q400 250 800 180" stroke="white" stroke-width="2" fill="none"/>
                        <path d="M0 250 Q400 300 800 230" stroke="white" stroke-width="2" fill="none"/>
                        <path d="M0 300 Q400 350 800 280" stroke="white" stroke-width="2" fill="none"/>
                    </svg>

                    <div class="relative">
                        <h2 class="text-3xl lg:text-4xl font-semibold text-white mb-4">Ready to Join?</h2>
                        <p class="text-teal-100 text-lg mb-8 max-w-lg mx-auto">Share your concert programs and discover what the choral world is singing. Free to use.</p>
                        @guest
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-8 py-3 rounded-xl bg-white text-teal-700 font-semibold hover:bg-teal-50 transition-colors shadow-lg">
                                    Create Your Account
                                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                </a>
                            @endif
                        @endguest
                        @auth
                            <a href="{{ url('/dashboard') }}" class="inline-flex items-center gap-2 px-8 py-3 rounded-xl bg-white text-teal-700 font-semibold hover:bg-teal-50 transition-colors shadow-lg">
                                Go to Dashboard
                                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </section>

        @include('partials.footer')

        @fluxScripts
    </body>
</html>
