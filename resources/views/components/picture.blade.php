@props(['media' => null, 'alt' => null, 'sizes' => '100vw', 'loading' => 'lazy', 'priority' => false, 'class' => null, 'imgClass' => null])
{{-- Responsive image: AVIF/WebP copies when ready, the original as fallback; fixed width/height prevent layout shift. --}}
@if ($media)
    @php
        $altText = $alt ?? $media->altText();
        $avif = $media->srcset('avif');
        $webp = $media->srcset('webp');
    @endphp
    <picture @if($class) class="{{ $class }}" @endif>
        @if ($avif)<source type="image/avif" srcset="{{ $avif }}" sizes="{{ $sizes }}">@endif
        @if ($webp)<source type="image/webp" srcset="{{ $webp }}" sizes="{{ $sizes }}">@endif
        <img src="{{ $media->url() }}" alt="{{ $altText }}"
             @if($media->width) width="{{ $media->width }}" height="{{ $media->height }}" @endif
             loading="{{ $priority ? 'eager' : $loading }}" decoding="async"
             @if($priority) fetchpriority="high" @endif
             {{ $attributes->merge(['class' => $imgClass]) }}>
    </picture>
@endif
