{{-- Hero: light leaks, giant moving name, 3D scene (lazy-loaded), cut-out portrait, title and buttons. --}}
<section class="hero" id="{{ $s->domId() }}" data-module="hero" data-scene-enabled="{{ $s->on('show_scene') ? '1' : '0' }}">
    <div class="leak l1"></div><div class="leak l2"></div><div class="leak l3"></div>
    <div class="name" aria-hidden="true"><div class="track" data-name-track>@for($i = 0; $i < 4; $i++)<span class="{{ $i % 2 ? 'o' : 's' }}">{{ $marquee }}</span><em></em>@endfor</div></div>
    <figure class="me" data-me>
        @if($s->on('show_scene'))<canvas data-gl aria-hidden="true"></canvas>@endif
        <div class="me-ring" aria-hidden="true"></div>
        <x-picture :media="$portrait" :alt="$site->ui('a11y.portrait', ['name' => tr($identity['name'])])" sizes="(max-width: 860px) 92vw, 560px" :priority="true" />
        @if($s->on('show_clock'))
            <span class="me-tag keep"><i></i>{{ tr($identity['city_short']) }} · <b data-clock>--:--</b></span>
        @endif
    </figure>
    <div class="hero-copy wrap">
        @if($s->filled('badge'))<span class="badge"><b></b>{{ $s->t('badge') }}</span>@endif
        <h1 class="hero-h">{{ $s->title('title') }}</h1>
        @if($s->filled('subtitle'))<p class="hero-sub">{{ $s->t('subtitle') }}</p>@endif
        @if($buttons = $s->buttons())
            <div class="hero-cta">@foreach($buttons as $button)<x-btn :button="$button" />@endforeach</div>
        @endif
    </div>
    @if($s->filled('scroll_label'))<div class="scrollcue mono"><span>{{ $s->t('scroll_label') }}</span><i></i></div>@endif
</section>
