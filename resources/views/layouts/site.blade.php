@php
    use App\Support\Locales;
    $dir = Locales::dir($locale);
    $other = Locales::other($locale);
    $identity = $site->settings('identity');
    $header = $site->settings('header');
    $look = $site->settings('appearance');
    $seo = $site->settings('seo');
    $favicon = $site->media($seo['favicon'] ?? null);
    $motion = $look['motion'] ?? 'full';
    $greetings = array_values(array_filter(array_column($look['greetings'] ?? [], 'text')));
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $dir }}" class="no-js"
      data-motion="{{ $motion }}"
      data-smooth="{{ ($look['smooth_scroll'] ?? true) ? '1' : '0' }}"
      data-cursor="{{ ($look['custom_cursor'] ?? true) ? '1' : '0' }}"
      data-scene="{{ ($look['scene_3d'] ?? true) ? '1' : '0' }}"
      data-loader="{{ ($look['loader'] ?? true) && ($isHome ?? false) ? '1' : '0' }}"
      data-tz="{{ $identity['timezone'] ?: 'Asia/Riyadh' }}"
      data-tz-label="{{ $identity['timezone_label'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $meta['title'] }}</title>
    @if($meta['description'])<meta name="description" content="{{ $meta['description'] }}">@endif
    @if($meta['noindex'] || $site->isPreview())<meta name="robots" content="noindex, nofollow">@endif
    <link rel="canonical" href="{{ $meta['canonical'] }}">
    @foreach($alternates as $lang => $href)
        <link rel="alternate" hreflang="{{ $lang }}" href="{{ $href }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $alternates['ar'] ?? $meta['canonical'] }}">
    <meta property="og:type" content="{{ $meta['type'] }}">
    <meta property="og:title" content="{{ $meta['title'] }}">
    @if($meta['description'])<meta property="og:description" content="{{ $meta['description'] }}">@endif
    <meta property="og:url" content="{{ $meta['canonical'] }}">
    <meta property="og:locale" content="{{ $locale === 'ar' ? 'ar_SA' : 'en_US' }}">
    <meta property="og:site_name" content="{{ tr($identity['name']) }}">
    @if($meta['image'])
        <meta property="og:image" content="{{ $meta['image']->url() }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif
    <meta name="theme-color" content="#121012">
    @if($favicon)
        <link rel="icon" href="{{ $favicon->url() }}">
    @else
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @endif
    {{-- The fonts of the first screen are fetched early so the text does not jump when they arrive (CLS). --}}
    @foreach($locale === 'ar'
        ? ['alexandria/files/alexandria-arabic-800-normal', 'ibm-plex-sans-arabic/files/ibm-plex-sans-arabic-arabic-400-normal']
        : ['bricolage-grotesque/files/bricolage-grotesque-latin-800-normal', 'instrument-sans/files/instrument-sans-latin-400-normal'] as $font)
        <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset('node_modules/@fontsource/'.$font.'.woff2') }}">
    @endforeach
    {{-- Approved palette from Settings › Appearance (validated against a fixed list of colours). --}}
    <style nonce="{{ Vite::cspNonce() }}">:root{--brick:{{ $look['accent'] }};--turq:{{ $look['signal'] }}}</style>
    <script nonce="{{ Vite::cspNonce() }}">document.documentElement.classList.replace('no-js','js')</script>
    @vite(['resources/css/site.css', 'resources/ts/site.ts'])
    @if($meta['jsonld'])
        <script type="application/ld+json" nonce="{{ Vite::cspNonce() }}">{!! json_encode($meta['jsonld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endif
</head>
<body class="{{ $bodyClass ?? '' }}">
@if($site->isPreview())
    <div class="preview-bar mono" role="status">{{ $site->ui('preview.banner') }} <a href="{{ url('/admin') }}">{{ $site->ui('preview.exit') }}</a></div>
@endif
<a class="skip mono" href="#main">{{ tr($header['skip_label']) }}</a>

@include('partials.header')

<main id="main" tabindex="-1">
    @yield('content')
</main>

@include('partials.footer')

<div id="curtain" aria-hidden="true"></div>
@if($look['grain'] ?? true)<div class="grain" aria-hidden="true"></div>@endif
<div class="spot" id="spot" aria-hidden="true"></div>
<div class="cursor" aria-hidden="true"><div class="dot" id="cdot"></div><div class="ring" id="cring"></div></div>
<template id="loader-tpl"><div id="loader" aria-hidden="true"><div class="greet" id="greet"></div><svg viewBox="0 0 100 20" preserveAspectRatio="none"><path d="M0 0 Q50 20 100 0 Z"/></svg></div></template>
<script type="application/json" id="site-data" nonce="{{ Vite::cspNonce() }}">{!! json_encode([
    'greetings' => $greetings,
    'other' => $other,
    'sending' => $site->ui('form.sending'),
    'tooMany' => $site->ui('form.too_many'),
    'failure' => $site->ui('form.failure'),
    'required' => $site->ui('form.required'),
    'invalidEmail' => $site->ui('form.invalid_email'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
</body>
</html>
