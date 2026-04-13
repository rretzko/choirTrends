<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>ChoirTrends User's Guide</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
        }

        /* Cover Page */
        .cover {
            text-align: center;
            padding-top: 200px;
            page-break-after: always;
        }

        .cover h1 {
            font-size: 28pt;
            margin-bottom: 10px;
            color: #000;
        }

        .cover .subtitle {
            font-size: 16pt;
            color: #555;
            margin-bottom: 40px;
        }

        .cover .tagline {
            font-size: 11pt;
            color: #777;
            font-style: italic;
        }

        /* Table of Contents */
        .toc {
            page-break-after: always;
        }

        .toc h2 {
            font-size: 18pt;
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
            margin-bottom: 20px;
        }

        .toc ul {
            list-style: none;
            padding: 0;
        }

        .toc li {
            padding: 6px 0;
            border-bottom: 1px dotted #ccc;
            font-size: 12pt;
        }

        /* Section Styling */
        .section {
            page-break-before: always;
        }

        .section:first-of-type {
            page-break-before: auto;
        }

        .section-title {
            font-size: 18pt;
            color: #000;
            border-bottom: 2px solid #333;
            padding-bottom: 6px;
            margin-bottom: 16px;
            margin-top: 0;
        }

        h3 {
            font-size: 14pt;
            color: #111;
            margin-top: 20px;
            margin-bottom: 8px;
        }

        h4 {
            font-size: 12pt;
            color: #222;
            margin-top: 16px;
            margin-bottom: 6px;
        }

        p {
            margin: 8px 0;
        }

        ul, ol {
            margin: 8px 0;
            padding-left: 24px;
        }

        li {
            margin-bottom: 4px;
        }

        strong {
            color: #000;
        }

        em {
            color: #555;
        }

        a {
            color: #333;
            text-decoration: underline;
        }

        /* Tip/callout boxes (PDF version) */
        .guide-tip {
            padding: 10px 14px;
            margin: 12px 0;
            border-left: 4px solid #555;
            background-color: #f8f8f8;
        }

        .guide-tip-success {
            border-left-color: #333;
        }

        .guide-tip-info {
            border-left-color: #666;
        }

        .guide-tip-warning {
            border-left-color: #999;
        }

        .guide-tip p {
            margin: 0;
        }

        .guide-tip-icon {
            display: none;
        }

        /* Step cards (PDF version) */
        .guide-step {
            padding: 8px 0 8px 0;
            margin: 6px 0;
            border-bottom: 1px solid #eee;
        }

        .guide-step-number {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 10px;
            background-color: #333;
            color: white;
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
            line-height: 20px;
            margin-right: 8px;
        }

        .guide-step h4 {
            display: inline;
            margin: 0;
        }

        .guide-step p {
            margin: 4px 0 0 28px;
        }

        /* Feature items (PDF version) */
        .guide-feature {
            padding: 6px 0;
            margin: 4px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .guide-feature p {
            margin: 0;
        }

        .guide-feature-icon {
            display: none;
        }

        /* Screenshot placeholders (PDF version) */
        .guide-screenshot {
            text-align: center;
            padding: 16px;
            margin: 12px 0;
            border: 1px dashed #aaa;
            color: #999;
            font-size: 10pt;
            font-style: italic;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    {{-- Cover Page --}}
    <div class="cover">
        <h1>ChoirTrends</h1>
        <div class="subtitle">User's Guide</div>
        <div class="tagline">Discover What's Trending in Choral Music</div>
    </div>

    {{-- Table of Contents --}}
    <div class="toc">
        <h2>Table of Contents</h2>
        <ul>
            @foreach ($sections as $section)
                <li>{{ $loop->iteration }}. {{ $section->title }}</li>
            @endforeach
        </ul>
    </div>

    {{-- Sections --}}
    @foreach ($sections as $section)
        <div class="section">
            <h2 class="section-title">{{ $loop->iteration }}. {{ $section->title }}</h2>
            {!! $section->body !!}
        </div>
    @endforeach

    <div class="footer">
        ChoirTrends User's Guide &mdash; {{ now()->format('F Y') }} &mdash; ChoirTrends.com
    </div>
</body>
</html>
