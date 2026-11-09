@php
    // Error pages must render even when the database is unavailable, so every lookup is guarded.
    $locale = in_array(request()->segment(1), ['ar', 'en'], true) ? request()->segment(1) : (str_starts_with((string) request()->getPreferredLanguage(['ar', 'en']), 'ar') ? 'ar' : 'en');
    app()->setLocale($locale);
    $text = function (string $key) use ($locale) {
        try {
            return app(\App\Content\Site::class)->ui($key, [], $locale);
        } catch (\Throwable) {
            return \App\Content\Settings\UiStrings::resolve($key, $locale);
        }
    };
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $text('errors.title_'.$code) }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/site.css'])
</head>
<body class="error-page">
<main class="err-wrap wrap">
    <p class="mono eyebrow keep">{{ $code }}</p>
    <h1>{{ $text('errors.title_'.$code) }}</h1>
    <p>{{ $text('errors.text_'.$code) }}</p>
    <a class="btn solid" href="/{{ $locale }}">{{ $text('errors.home') }} <span class="arr" aria-hidden="true">→</span></a>
</main>
</body>
</html>
