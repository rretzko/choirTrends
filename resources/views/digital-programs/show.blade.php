<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        {{ $dp->program?->event_name ?? 'Concert Program' }}
        @if($dp->program?->event_date) — {{ $dp->program->event_date->format('F j, Y') }} @endif
        | {{ config('app.name') }}
    </title>
    <meta name="description" content="Digital concert program for {{ $dp->program?->event_name }}.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">
    @vite(['resources/css/app.css'])

    {{-- ── Theme CSS custom properties ── --}}
    <style>
        :root {
            --dp-bg:           {{ $themeStyle['bg'] }};
            --dp-surface:      {{ $themeStyle['surface'] }};
            --dp-surface2:     {{ $themeStyle['surface2'] }};
            --dp-text:         {{ $themeStyle['text'] }};
            --dp-text-muted:   {{ $themeStyle['text_muted'] }};
            --dp-accent:       {{ $themeStyle['accent'] }};
            --dp-border:       {{ $themeStyle['border'] }};
            --dp-header-bg:    {{ $themeStyle['header_bg'] }};
            --dp-header-text:  {{ $themeStyle['header_text'] }};
            --dp-song-title:   {{ $themeStyle['song_title'] }};
            --dp-section-lbl:  {{ $themeStyle['section_label'] }};
            --dp-lyrics-bg:    {{ $themeStyle['lyrics_bg'] }};
            --dp-inter-text:   {{ $themeStyle['inter_text'] }};
            --dp-font:         {{ $themeStyle['font'] }};
            --dp-heading-font: {{ $themeStyle['heading_font'] }};
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            background-color: var(--dp-bg);
            color: var(--dp-text);
            font-family: var(--dp-font);
            font-size: 16px;
            line-height: 1.65;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Phase 5: Print stylesheet ── */

        @page {
            size: letter {{ strtolower($dp->print_orientation ?? 'portrait') }};
            margin: 0.625in 0.75in;
        }

        @media print {
            /* Hide interactive / screen-only elements */
            .no-print { display: none !important; }

            /* Force white background and black text regardless of theme */
            *, *::before, *::after {
                background: white !important;
                background-color: white !important;
                color: black !important;
                border-color: #ccc !important;
                box-shadow: none !important;
            }

            html, body {
                font-size: 10pt;
                line-height: 1.5;
                background: white !important;
                color: black !important;
            }

            /* Header: keep on one block */
            header {
                page-break-inside: avoid;
                break-inside: avoid;
                padding: 0.4in 0.5in;
            }

            header h1 {
                font-size: 22pt;
            }

            /* Main content padding tightened for print */
            main {
                padding: 0.2in 0;
                max-width: 100%;
            }

            /* Landscape: two-column body layout */
            @if($dp->print_orientation === 'Landscape')
            main {
                column-count: 2;
                column-gap: 0.4in;
            }
            @endif

            /* Each ensemble section: prefer not to split, but allow if too long */
            section {
                break-inside: auto;
                page-break-inside: auto;
                margin-bottom: 0.3in;
            }

            /* Song items: never break mid-song */
            .song-item {
                page-break-inside: avoid;
                break-inside: avoid;
                margin-bottom: 0.15in;
            }

            /* Roster block: keep together if reasonably sized */
            .roster-section {
                page-break-inside: avoid;
                break-inside: avoid;
                margin-top: 0.2in;
            }

            /* Intermission banner: keep with surrounding content */
            .intermission-banner {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            /* Footer: always starts on a new "section" but not forced page break */
            footer {
                margin-top: 0.3in;
                padding-top: 0.2in;
                border-top-width: 1px;
            }

            /* QR code: fixed small size for print */
            footer .qr-wrap svg {
                width: 100px !important;
                height: 100px !important;
            }

            /* Anchor links: show as plain text */
            a { text-decoration: none; color: inherit; }

            /* Headings */
            h2 { font-size: 14pt; margin-bottom: 0.1in; }
            h3 { font-size: 11pt; }

            /* Center welcome, acknowledgments, and sponsors sections */
            .print-center { text-align: center; }

            @if($dp->print_orientation !== 'Landscape')
            /* Portrait: page break after each major section */
            .portrait-break {
                break-after: page;
                page-break-after: always;
            }
            @endif
        }
    </style>
</head>
<body>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- HEADER                                                  --}}
{{-- ══════════════════════════════════════════════════════ --}}
<header style="background-color: var(--dp-header-bg); color: var(--dp-header-text);">
    <div class="mx-auto max-w-2xl px-6 py-12 text-center">

        {{-- School name --}}
        @if($dp->program?->school)
            <p class="mb-3 text-xs font-semibold uppercase tracking-widest"
                style="color: var(--dp-accent); opacity: 0.9;">
                {{ $dp->program->school->school_name }}
            </p>
        @endif

        {{-- Event name --}}
        <h1 class="text-3xl font-bold leading-tight sm:text-4xl"
            style="font-family: var(--dp-heading-font); color: var(--dp-header-text);">
            {{ $dp->program?->event_name ?? 'Concert Program' }}
        </h1>

        {{-- Date --}}
        @if($dp->program?->event_date)
            <p class="mt-4 text-lg" style="color: var(--dp-header-text); opacity: 0.8;">
                {{ $dp->program->event_date->format('l, F j, Y') }}
            </p>
        @endif

        {{-- Director --}}
        @if($dp->program?->director_name)
            <p class="mt-2 text-sm" style="color: var(--dp-header-text); opacity: 0.65;">
                {{ $dp->program->director_name }}, Director
            </p>
        @endif

    </div>
</header>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- MAIN CONTENT                                            --}}
{{-- ══════════════════════════════════════════════════════ --}}
<main class="mx-auto max-w-2xl px-4 py-8 sm:px-6">

    {{-- ── Welcome message ── --}}
    @if($dp->welcome_message)
        <section class="portrait-break print-center mb-10 rounded-xl p-6"
            style="background: var(--dp-surface); border: 1px solid var(--dp-border);">
            <h2 class="mb-3 text-xs font-bold uppercase tracking-widest"
                style="color: var(--dp-section-lbl);">
                {{ __('A Note from the Director') }}
            </h2>
            <div class="text-base leading-relaxed"
                style="color: var(--dp-text);">{!! $dp->welcome_message !!}</div>
        </section>
    @endif

    {{-- ── Ensemble groups ── --}}
    @foreach($ensembleGroups as $group)
        @php
            $ek           = $group['key'];
            $ensemble     = $group['ensemble'];
            $songs        = $group['songs'];
            $ensDir       = $group['ensemble_director'];
            $ensSort      = $group['ensemble_sort_order'];
            $groupRosters = $rostersByEnsemble->get($ek, collect());
            $groupHonors  = $honorsByEnsemble->get($ek, collect())->sortBy('sort_order')->values();
            $honorMap     = $groupHonors->keyBy('sort_order'); // sort_order = superscript number
        @endphp

        <section class="portrait-break mb-12">

            {{-- Ensemble heading --}}
            @if($ensemble || $ek === 'general')
                <div class="mb-6 border-b pb-3" style="border-color: var(--dp-border);">
                    <h2 class="text-xl font-bold sm:text-2xl"
                        style="font-family: var(--dp-heading-font); color: var(--dp-accent);">
                        {{ $ensemble?->ensemble_name ?? __('Program') }}
                    </h2>
                    @if($ensDir && $ensDir !== $dp->program?->director_name)
                        <p class="mt-1 text-sm" style="color: var(--dp-text-muted);">
                            {{ $ensDir }}, Director
                        </p>
                    @endif
                    @if($ensemble?->a_cappella)
                        <p class="mt-1 text-xs font-medium italic" style="color: var(--dp-text-muted);">
                            {{ __('a cappella') }}
                        </p>
                    @endif
                </div>
            @endif

            {{-- Song list --}}
            <ol class="space-y-6 list-none p-0 m-0">
                @foreach($songs as $si => $song)
                    <li class="song-item relative pl-0">

                        {{-- Song number + title + composer/arranger on one row --}}
                        @php
                            $credits = [];
                            if ($song->composer) $credits[] = $song->composer->artist_name;
                            if ($song->arranger) $credits[] = 'arr. ' . $song->arranger->artist_name;
                        @endphp
                        <div class="flex items-baseline gap-3">
                            <span class="shrink-0 text-sm font-bold tabular-nums"
                                style="color: var(--dp-text-muted); min-width: 1.5rem;">
                                {{ $si + 1 }}.
                            </span>
                            <div class="flex flex-wrap items-baseline gap-x-2">
                                <h3 class="text-lg font-semibold leading-snug"
                                    style="color: var(--dp-song-title);">
                                    {{ $song->song_title }}
                                </h3>
                                @if(!empty($credits))
                                    <span class="text-sm" style="color: var(--dp-text-muted);">
                                        {{ implode(' / ', $credits) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Program notes: soloists, accompanists, guest conductors, etc. --}}
                        @if(!empty($notesBySongId[$song->id] ?? null))
                            <p class="mt-1 ml-9 text-sm italic leading-relaxed"
                                style="color: var(--dp-text-muted);">
                                {{ $notesBySongId[$song->id] }}
                            </p>
                        @endif

                        {{-- Lyrics (if enabled and available) --}}
                        @if(in_array($song->id, $showLyricsSongIds) && isset($lyricsBySongId[$song->id]))
                            <div class="mt-3 ml-9 rounded-lg p-4 text-sm leading-loose"
                                style="background: var(--dp-lyrics-bg); color: var(--dp-text);">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wider"
                                    style="color: var(--dp-text-muted);">
                                    {{ __('Lyrics') }}
                                </p>
                                <div class="whitespace-pre-line">{{ $lyricsBySongId[$song->id]->content }}</div>
                                @if(!empty($lyricsBySongId[$song->id]->source))
                                    <p class="mt-3 text-xs italic" style="color: var(--dp-text-muted);">
                                        {{ $lyricsBySongId[$song->id]->source }}
                                    </p>
                                @endif
                            </div>
                        @endif

                    </li>
                @endforeach
            </ol>

            {{-- ── Roster (if any students for this ensemble) ── --}}
            @if($groupRosters->isNotEmpty())
                <div class="roster-section mt-8 rounded-xl p-5"
                    style="background: var(--dp-surface); border: 1px solid var(--dp-border);">

                    <h3 class="mb-4 text-xs font-bold uppercase tracking-widest"
                        style="color: var(--dp-section-lbl);">
                        {{ $ensemble?->ensemble_name ?? __('Roster') }}
                    </h3>

                    {{-- Group by voice part --}}
                    @php
                        $rosterByPart = $groupRosters->groupBy(fn($r) => $r->voice_part ?? '__none__');
                        $hasVoiceParts = $rosterByPart->keys()->filter(fn($k) => $k !== '__none__')->isNotEmpty();
                    @endphp

                    @foreach($rosterByPart as $part => $students)
                        @if($hasVoiceParts && $part !== '__none__')
                            <p class="mt-3 mb-1 text-xs font-bold uppercase tracking-wider"
                                style="color: var(--dp-text-muted);">
                                {{ $part }}
                            </p>
                        @endif
                        <p class="text-sm leading-loose" style="color: var(--dp-text);">
                            @foreach($students as $si2 => $student)
                                {{-- Student name with honor superscripts --}}
                                {{ $student->student_name }}@php
                                    $studentHonorNumbers = $student->honors->pluck('sort_order')->sort()->values();
                                @endphp@if($studentHonorNumbers->isNotEmpty())<sup style="color: var(--dp-accent); font-weight: 700;">{{ $studentHonorNumbers->implode(',') }}</sup>@endif@if(!$loop->last), @endif
                            @endforeach
                        </p>
                    @endforeach

                    {{-- Honor legend --}}
                    @if($groupHonors->isNotEmpty())
                        <div class="mt-4 border-t pt-3 text-xs"
                            style="border-color: var(--dp-border); color: var(--dp-text-muted);">
                            @foreach($groupHonors as $honor)
                                <span class="mr-4 whitespace-nowrap">
                                    <sup style="color: var(--dp-accent); font-weight: 700;">{{ $honor->sort_order }}</sup>
                                    {{ $honor->label }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

        </section>

        {{-- ── Intermission banner ── --}}
        @if($dp->intermission_after_ensemble && (int) $dp->intermission_after_ensemble === (int) $ensSort && !$loop->last)
            <div class="intermission-banner my-10 flex items-center gap-4">
                <div class="flex-1 border-t" style="border-color: var(--dp-border);"></div>
                <span class="shrink-0 text-xs font-bold uppercase tracking-widest px-4"
                    style="color: var(--dp-inter-text);">
                    — {{ __('Intermission') }} —
                </span>
                <div class="flex-1 border-t" style="border-color: var(--dp-border);"></div>
            </div>
        @endif

    @endforeach

    {{-- ── Acknowledgments ── --}}
    @if($dp->acknowledgments)
        <section class="portrait-break print-center mb-8 rounded-xl p-6"
            style="background: var(--dp-surface); border: 1px solid var(--dp-border);">
            <h2 class="mb-3 text-xs font-bold uppercase tracking-widest"
                style="color: var(--dp-section-lbl);">
                {{ __('Acknowledgments') }}
            </h2>
            <div class="text-sm leading-relaxed"
                style="color: var(--dp-text);">{!! $dp->acknowledgments !!}</div>
        </section>
    @endif

    {{-- ── Sponsors ── --}}
    @if($dp->sponsor_text)
        <section class="portrait-break print-center mb-8 rounded-xl p-6"
            style="background: var(--dp-surface); border: 1px solid var(--dp-border);">
            <h2 class="mb-3 text-xs font-bold uppercase tracking-widest"
                style="color: var(--dp-section-lbl);">
                {{ __('Sponsors & Patrons') }}
            </h2>
            <div class="text-sm leading-relaxed"
                style="color: var(--dp-text);">{!! $dp->sponsor_text !!}</div>
        </section>
    @endif

</main>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- FOOTER                                                  --}}
{{-- ══════════════════════════════════════════════════════ --}}
<footer class="no-print border-t mt-8 px-4 py-10"
    style="border-color: var(--dp-border);">
    <div class="mx-auto max-w-2xl sm:px-6">

        {{-- QR code + URL --}}
        <div class="flex flex-col items-center gap-6 sm:flex-row sm:items-start sm:gap-10">

            {{-- QR code in a white box (always scannable) --}}
            <div class="shrink-0">
                <div class="qr-wrap rounded-xl bg-white p-3 shadow-sm">
                    {!! $qrCode !!}
                </div>
                <p class="mt-2 text-center text-xs" style="color: var(--dp-text-muted);">
                    {{ __('Scan to share') }}
                </p>
            </div>

            {{-- Right side --}}
            <div class="text-center sm:text-left">
                <p class="mb-1 text-xs font-semibold uppercase tracking-wider"
                    style="color: var(--dp-section-lbl);">
                    {{ __('Web Address') }}
                </p>
                <p class="mb-4 break-all font-mono text-sm"
                    style="color: var(--dp-text-muted);">
                    {{ url('/p/' . $dp->slug) }}
                </p>

                <div class="no-print flex flex-wrap gap-2">
                    <button onclick="window.print()"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-opacity hover:opacity-80"
                        style="background: var(--dp-surface2); color: var(--dp-text); border: 1px solid var(--dp-border);">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        {{ __('Print Program') }}
                    </button>

                    <a href="{{ route('program.qr', $dp->slug) }}" target="_blank"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-opacity hover:opacity-80"
                        style="background: var(--dp-surface2); color: var(--dp-text); border: 1px solid var(--dp-border);">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 3.5V16M4 4h4v4H4V4zm12 0h4v4h-4V4zM4 16h4v4H4v-4z"/>
                        </svg>
                        {{ __('Large QR Code') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Attribution --}}
        <div class="mt-10 border-t pt-6 text-center text-xs"
            style="border-color: var(--dp-border); color: var(--dp-section-lbl);">
            {{ __('Created with') }}
            <a href="{{ url('/') }}"
                class="font-medium hover:underline"
                style="color: var(--dp-text-muted);">
                {{ config('app.name') }}
            </a>
        </div>

    </div>
</footer>

@if(request()->boolean('print'))
<script>window.addEventListener('load', () => window.print());</script>
@endif

</body>
</html>
