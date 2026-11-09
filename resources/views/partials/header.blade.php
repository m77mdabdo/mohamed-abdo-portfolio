@php
    $menu = $site->menu('header', $locale);
    $drawer = $site->menu('drawer', $locale);
    $home = route('home', ['locale' => $locale]);
    $switchHref = $alternates[$other] ?? route('home', ['locale' => $other]);
@endphp
<header class="top wrap">
    <a class="logo" href="{{ $home }}" data-magnetic>© {{ tr($identity['name']) }} <i class="keep">{{ tr($header['logo_tagline']) }}</i></a>
    <nav class="links" aria-label="{{ $site->ui('a11y.main_nav') }}">
        @foreach($menu as $item)
            <a href="{{ $item['href'] }}" data-magnetic @if($item['new_tab']) target="_blank" rel="noopener noreferrer" @endif>{{ $item['label'] }}</a>
        @endforeach
        <a class="lang" href="{{ $switchHref }}" hreflang="{{ $other }}" lang="{{ $other }}" data-lang-switch data-magnetic aria-label="{{ tr($header['switch_label']) }}">{{ $other === 'en' ? 'EN' : 'ع' }}</a>
        <button class="menu-btn lang" type="button" data-drawer-open aria-controls="drawer" aria-expanded="false">{{ tr($header['menu_label']) }}</button>
    </nav>
</header>
<button class="fab" type="button" id="fab" data-drawer-open aria-controls="drawer" aria-expanded="false" aria-label="{{ tr($header['menu_label']) }}" data-magnetic><span></span></button>
<div id="drawer" aria-hidden="true" inert>
    <div class="scrim" data-drawer-close></div>
    <div class="panel" role="dialog" aria-modal="true" aria-label="{{ tr($header['menu_label']) }}">
        <button class="close lang" type="button" data-drawer-close>{{ tr($header['close_label']) }}</button>
        <div>
            <p class="mono drawer-label">{{ tr($header['menu_label']) }}</p>
            @foreach($drawer as $item)
                <a class="big" href="{{ $item['href'] }}" data-drawer-link @if($item['new_tab']) target="_blank" rel="noopener noreferrer" @endif>{{ $item['label'] }}</a>
                @foreach($item['children'] as $child)
                    <a class="big sub" href="{{ $child['href'] }}" data-drawer-link>{{ $child['label'] }}</a>
                @endforeach
            @endforeach
        </div>
        <div class="drawer-foot">
            @foreach($identity['social'] ?? [] as $link)
                @if($link['url'])<a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">{{ $link['label'] }}</a>@endif
            @endforeach
            <a class="lang" href="{{ $switchHref }}" hreflang="{{ $other }}" lang="{{ $other }}" data-lang-switch>{{ \App\Support\Locales::label($other) }}</a>
        </div>
    </div>
</div>
