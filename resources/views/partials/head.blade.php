<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

@php
    $appName = config('app.name');
    $defaultDescription = 'Upload concert programs and discover trending choral repertoire, composers, and arrangers. A free, community-driven resource for choral directors.';

    $pageTitle = isset($title) && $title
        ? $title . ' | ' . $appName
        : $appName . ' — Discover What\'s Trending in Choral Music';
    $metaDescription = $description ?? $defaultDescription;
    $canonicalUrl = $canonical ?? url()->current();
    $ogImageUrl = $ogImage ?? asset('og-image.png');
@endphp

<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">
<link rel="canonical" href="{{ $canonicalUrl }}">

{{-- Open Graph --}}
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:site_name" content="{{ $appName }}">
<meta property="og:image" content="{{ $ogImageUrl }}">
<meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
<meta name="twitter:image" content="{{ $ogImageUrl }}">

<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
<meta name="theme-color" content="#006D77">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

{{-- Structured Data --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@graph": [
        {
            "@type": "Organization",
            "name": "{{ $appName }}",
            "url": "{{ config('app.url') }}",
            "logo": "{{ asset('favicon.svg') }}",
            "description": "A community-driven platform for choral directors to share concert programs and discover trending repertoire."
        },
        {
            "@type": "WebSite",
            "name": "{{ $appName }}",
            "url": "{{ config('app.url') }}",
            "description": "{{ $defaultDescription }}"
        }
    ]
}
</script>
