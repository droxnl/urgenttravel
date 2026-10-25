<!doctype html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#111113">
    <link rel="icon" type="image/png" href="/images/brand/urgent-travel-services-logo.png">
    <meta name="robots" content="{{ $seo['robots'] ?? 'index, follow' }}">
    <meta name="description" content="{{ $seo['description'] ?? '' }}">
    <link rel="canonical" href="{{ $seo['canonical'] ?? url()->current() }}">
    <meta property="og:locale" content="{{ $seo['locale'] ?? 'en_US' }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Urgent Travel Services">
    <meta property="og:title" content="{{ $seo['og_title'] ?? $seo['title'] ?? '' }}">
    <meta property="og:description" content="{{ $seo['og_description'] ?? $seo['description'] ?? '' }}">
    <meta property="og:url" content="{{ $seo['canonical'] ?? url()->current() }}">
    @if (! empty($seo['alternates']['en']))
        <link rel="alternate" hreflang="en" href="{{ rtrim(url('/'), '/') }}{{ $seo['alternates']['en'] }}" data-i18n-alternate>
    @endif
    @if (! empty($seo['alternates']['nl']))
        <link rel="alternate" hreflang="nl" href="{{ rtrim(url('/'), '/') }}{{ $seo['alternates']['nl'] }}" data-i18n-alternate>
        <link rel="alternate" hreflang="x-default" href="{{ rtrim(url('/'), '/') }}{{ $seo['alternates']['en'] ?? '/' }}" data-i18n-alternate>
    @endif
    @if (! empty($seo['og_image']))
        <meta property="og:image" content="{{ str_starts_with($seo['og_image'], 'http') ? $seo['og_image'] : url($seo['og_image']) }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">

    <title>{{ $seo['title'] ?? 'Urgent Travel Services' }}</title>

    @if (! empty($seo['schema']))
        <script type="application/ld+json">{!! json_encode($seo['schema'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to content</a>
    <div id="app"></div>
    <script>
        window.__APP_BOOTSTRAP__ = @json($bootstrap ?? []);
    </script>
</body>
</html>
