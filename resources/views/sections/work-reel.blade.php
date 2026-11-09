@php $count = count($projects); @endphp
<section class="reel" id="{{ $s->domId() }}" data-module="reel">
    <div class="reel-in">
        <div class="reel-head wrap">
            <div>
                @if($s->filled('eyebrow'))<p class="mono eyebrow">{{ $s->t('eyebrow') }}</p>@endif
                @if($s->filled('title'))<h2 class="split">{{ $s->words('title', true) }}</h2>@endif
            </div>
            <p class="reel-count mono keep" dir="ltr" aria-hidden="true"><b data-reel-num>01</b> / {{ str_pad((string) $count, 2, '0', STR_PAD_LEFT) }}</p>
        </div>
        <div class="track" data-reel-track>
            @foreach($projects as $i => $p)
                <a class="card" href="{{ route('projects.show', ['locale' => $locale, 'slug' => $p['slug']]) }}" data-cursor="{{ $s->t('cursor_label') }}">
                    <div class="media" style="--tone:{{ hex_color($p['color'], '#1C181A') }}">
                        <x-picture :media="$site->media($p['cover_media_id'])" :alt="tr($p['title']).' — '.tr($p['kind'])" sizes="(max-width: 760px) 80vw, 46vw" :loading="$i < 3 ? 'eager' : 'lazy'" />
                        <div class="shine"></div>
                    </div>
                    <div class="info">
                        <div><span class="num keep">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span><h3>{{ tr($p['title']) }}</h3><p>{{ tr($p['kind']) }}</p></div>
                        <span class="status {{ $p['status_tone'] === 'live' ? 'live' : '' }}">{{ tr($p['status_label']) }}</span>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="wrap"><div class="reel-progress"><i data-reel-bar></i></div></div>
    </div>
</section>
