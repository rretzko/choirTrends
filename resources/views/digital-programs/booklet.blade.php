<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $dp->program?->event_name ?? 'Concert Program' }} — Booklet</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #d1d5db;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 10pt;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── Screen wrapper ── */
        .screen-wrapper {
            padding: 0.4in 0;
        }

        /* ── Sheet side: one landscape face of a physical sheet ── */
        .sheet-side {
            width: 11in;
            height: 8.5in;
            display: flex;
            background: white;
            margin: 0 auto 0.3in;
            box-shadow: 0 2px 10px rgba(0,0,0,.25);
            position: relative;
            overflow: hidden;
        }

        /* ── Screen labels ── */
        .sheet-label {
            position: absolute;
            top: -1.5em;
            left: 0;
            font-family: system-ui, sans-serif;
            font-size: 8pt;
            color: #6b7280;
        }

        /* ── Fold guide ── */
        .fold-line {
            position: absolute;
            left: 50%;
            top: 0.25in;
            bottom: 0.25in;
            width: 0;
            border-left: 1px dashed #9ca3af;
            pointer-events: none;
            z-index: 10;
        }

        /* ── Each half-page panel ── */
        .panel {
            width: 5.5in;
            height: 8.5in;
            padding: 0.5in 0.45in;
            overflow: hidden;
            position: relative;
        }

        .panel-left {
            border-right: 0.5pt solid #e5e7eb;
        }

        /* ── Cover panel ── */
        .cover-wrap {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            text-align: center;
            gap: 0.12in;
        }

        .cover-school {
            font-size: 8.5pt;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6b7280;
        }

        .cover-title {
            font-size: 22pt;
            font-weight: bold;
            line-height: 1.2;
        }

        .cover-date {
            font-size: 11pt;
            color: #374151;
            margin-top: 0.05in;
        }

        .cover-director {
            font-size: 9pt;
            color: #6b7280;
            margin-top: 0.05in;
        }

        /* ── Section label ── */
        .section-label {
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 0.12in;
        }

        /* ── Welcome / Ack / Sponsors text ── */
        .prose {
            font-size: 9.5pt;
            line-height: 1.7;
            color: #111;
        }

        .prose + .section-label {
            margin-top: 0.2in;
        }

        /* ── Ensemble panel ── */
        .ensemble-name {
            font-size: 14pt;
            font-weight: bold;
            border-bottom: 0.5pt solid #d1d5db;
            padding-bottom: 0.07in;
            margin-bottom: 0.1in;
        }

        .ensemble-meta {
            font-size: 8.5pt;
            color: #6b7280;
            margin-bottom: 0.08in;
        }

        .song-list {
            list-style: none;
            padding: 0;
        }

        .song-item {
            margin-bottom: 0.1in;
            line-height: 1.3;
        }

        .song-number {
            font-size: 8pt;
            color: #9ca3af;
            display: inline-block;
            min-width: 1.1em;
        }

        .song-title-text {
            font-size: 10pt;
            font-weight: 600;
        }

        .song-credits {
            font-size: 8pt;
            color: #6b7280;
        }

        .song-notes {
            font-size: 8pt;
            font-style: italic;
            color: #6b7280;
            padding-left: 1.1em;
        }

        /* ── Roster ── */
        .roster-wrap {
            margin-top: 0.14in;
            border-top: 0.5pt solid #e5e7eb;
            padding-top: 0.1in;
        }

        .roster-part-heading {
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6b7280;
            margin: 0.06in 0 0.02in;
        }

        .roster-names {
            font-size: 8.5pt;
            line-height: 1.55;
            color: #1f2937;
        }

        .honor-legend {
            font-size: 7.5pt;
            color: #9ca3af;
            margin-top: 0.07in;
        }

        /* ── Print ── */
        @page {
            size: 11in 8.5in landscape;
            margin: 0;
        }

        @media print {
            body { background: white; }

            .screen-wrapper { padding: 0; }

            .no-print,
            .sheet-label,
            .fold-line { display: none !important; }

            .sheet-side {
                width: 11in;
                height: 8.5in;
                margin: 0;
                box-shadow: none;
                page-break-after: always;
                break-after: page;
            }

            .sheet-side:last-child {
                page-break-after: auto;
                break-after: auto;
            }

            .panel {
                height: 8.5in;
            }

            .panel-left {
                border-right: 0.5pt solid #d1d5db;
            }

            *, *::before, *::after {
                background: white !important;
                background-color: white !important;
                color: black !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

{{-- ── Screen toolbar ── --}}
<div class="no-print" style="background:#1f2937; color:#f9fafb; font-family:system-ui,sans-serif; padding:0.35in 0.5in; display:flex; align-items:center; gap:1.5em; font-size:10pt;">
    <strong>{{ $dp->program?->event_name ?? 'Concert Program' }} — Booklet Layout</strong>
    <span style="color:#9ca3af;">{{ count($sheetSides) / 2 }} sheet(s) · {{ count($sheetSides) }} printed sides</span>
    <button onclick="window.print()" style="margin-left:auto; padding:0.3em 1em; background:#3b82f6; color:white; border:none; border-radius:6px; font-size:10pt; cursor:pointer;">
        Print Booklet
    </button>
    <a href="{{ route('program.public', $dp->slug) }}" style="color:#93c5fd; text-decoration:none;">← Program</a>
</div>

<div class="screen-wrapper">

@foreach($sheetSides as $sideIdx => $side)
    @php $sheetNum = intdiv($sideIdx, 2) + 1; @endphp

    <div class="sheet-side">

        {{-- Screen labels --}}
        <div class="sheet-label no-print">
            Sheet {{ $sheetNum }}, {{ $side['side'] === 'front' ? 'Front — place face up in printer' : 'Back — flip on long edge' }}
        </div>

        {{-- Fold guide --}}
        <div class="fold-line"></div>

        {{-- Left panel --}}
        <div class="panel panel-left">
            @include('digital-programs.booklet-panel', [
                'panel'            => $side['left'],
                'dp'               => $dp,
                'rostersByEnsemble'=> $rostersByEnsemble,
                'honorsByEnsemble' => $honorsByEnsemble,
                'notesBySongId'    => $notesBySongId,
                'showLyricsSongIds'=> $showLyricsSongIds,
                'lyricsBySongId'   => $lyricsBySongId,
            ])
        </div>

        {{-- Right panel --}}
        <div class="panel">
            @include('digital-programs.booklet-panel', [
                'panel'            => $side['right'],
                'dp'               => $dp,
                'rostersByEnsemble'=> $rostersByEnsemble,
                'honorsByEnsemble' => $honorsByEnsemble,
                'notesBySongId'    => $notesBySongId,
                'showLyricsSongIds'=> $showLyricsSongIds,
                'lyricsBySongId'   => $lyricsBySongId,
            ])
        </div>

    </div>
@endforeach

</div>{{-- /.screen-wrapper --}}

@if(request()->boolean('print'))
    <script>window.addEventListener('load', () => window.print());</script>
@endif

</body>
</html>
