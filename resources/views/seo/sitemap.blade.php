{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach($entries as $entry)
@foreach($entry as $lang => $url)
    <url>
        <loc>{{ $url }}</loc>
@foreach($entry as $altLang => $altUrl)
        <xhtml:link rel="alternate" hreflang="{{ $altLang }}" href="{{ $altUrl }}"/>
@endforeach
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ $entry['ar'] ?? $url }}"/>
    </url>
@endforeach
@endforeach
</urlset>
