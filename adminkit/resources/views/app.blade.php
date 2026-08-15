<!DOCTYPE html>
<html lang="{{ $branding['language'] ?? 'id' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ $branding['app_name'] }}</title>

    <meta name="app-name" content="{{ $branding['app_name'] }}">
    <meta name="description" content="{{ $branding['meta_description'] }}">
    <meta name="keywords" content="{{ $branding['meta_keywords'] }}">
    @unless ($branding['search_indexable'])
        <meta name="robots" content="noindex, nofollow">
    @endunless
    @if ($branding['canonical_url'])
        <link rel="canonical" href="{{ $branding['canonical_url'] }}">
    @endif

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $branding['app_name'] }}">
    <meta property="og:title" content="{{ $branding['meta_title'] }}">
    <meta property="og:description" content="{{ $branding['meta_description'] }}">
    @if ($branding['og_image'])
        <meta property="og:image" content="{{ url($branding['og_image']) }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif

    @if ($branding['favicon'])
        <link rel="icon" href="{{ $branding['favicon'] }}">
    @endif

    <script>
        (function () {
            try {
                var t = localStorage.getItem('adminkit.theme') || 'system';
                var dark = t === 'dark' || (t === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="antialiased">
    @inertia
</body>
</html>
