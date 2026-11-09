@php
    $foot = $site->settings('footer');
    $footMenu = $site->menu('footer', $locale);
    $liveProjects = ($foot['show_live'] ?? true) ? array_filter($site->projects(), fn ($p) => ! empty($p['live_url'])) : [];
    $email = $identity['email'] ?? '';
@endphp
<footer class="foot">
    <div class="wrap foot-top">
        <div class="foot-cta">
            <p class="mono eyebrow">{{ tr($foot['cta']) }}</p>
            @if($email)
                <a class="foot-mail keep" dir="ltr" href="mailto:{{ $email }}" data-magnetic data-strength="0.15">{{ $email }}<span aria-hidden="true">↗</span></a>
            @endif
            @if($tel = \App\Support\Phone::tel($identity['phone'] ?? ''))
                <a class="foot-phone keep" dir="ltr" href="{{ $tel }}">{{ \App\Support\Phone::display($identity['phone']) }}</a>
            @endif
        </div>
        <div class="foot-cols">
            <nav aria-label="{{ $site->ui('a11y.footer_nav') }}">
                <h2 class="h5">{{ tr($foot['nav_title']) }}</h2>
                @foreach($footMenu as $item)
                    <a href="{{ $item['href'] }}" @if($item['new_tab']) target="_blank" rel="noopener noreferrer" @endif>{{ $item['label'] }}</a>
                @endforeach
            </nav>
            @if($liveProjects)
                <div>
                    <h2 class="h5">{{ tr($foot['live_title']) }}</h2>
                    @foreach($liveProjects as $p)
                        <a href="{{ $p['live_url'] }}" target="_blank" rel="noopener noreferrer">{{ tr($p['title']) }} ↗</a>
                    @endforeach
                </div>
            @endif
            <div>
                <h2 class="h5">{{ tr($foot['social_title']) }}</h2>
                @foreach($identity['social'] ?? [] as $link)
                    @if($link['url'])<a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">{{ $link['label'] }} ↗</a>@endif
                @endforeach
            </div>
            <div>
                <h2 class="h5">{{ tr($foot['time_title']) }}</h2>
                <b class="keep" data-clock>--:--</b>
                <span>{{ tr($identity['city']) }}</span>
            </div>
        </div>
    </div>
    <div class="foot-giant" aria-hidden="true"><span>{{ tr($foot['giant_name']) }}</span></div>
    <div class="wrap foot-bar">
        <span>{{ tr($foot['edition']) }} · {{ tr($foot['rights']) }}</span>
        <a href="#main" class="foot-up" data-scroll-top data-magnetic>{{ tr($foot['top_label']) }} <span aria-hidden="true">↑</span></a>
    </div>
</footer>
