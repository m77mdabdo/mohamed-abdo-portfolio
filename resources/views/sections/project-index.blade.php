{{-- All projects. The category filter works without JavaScript (?category=slug) and instantly with it. --}}
<section class="index wrap" id="{{ $s->domId() }}" data-module="index" aria-label="{{ $s->t('title') }}">
    @if($categories)
        <nav class="filters" aria-label="{{ $site->ui('a11y.projects_filter') }}" data-filters>
            <a href="?#{{ $s->domId() }}" class="pill {{ $activeCategory ? '' : 'on' }}" data-filter="" @if(! $activeCategory) aria-current="true" @endif>{{ $s->t('all_label') }}</a>
            @foreach($categories as $c)
                <a href="?category={{ $c['slug'] }}#{{ $s->domId() }}" class="pill {{ $activeCategory === $c['slug'] ? 'on' : '' }}" data-filter="{{ $c['slug'] }}" @if($activeCategory === $c['slug']) aria-current="true" @endif>{{ tr($c['name']) }}</a>
            @endforeach
        </nav>
    @endif
    <div class="head mono" aria-hidden="true"><span>{{ $s->t('title') }}</span><span>{{ $s->t('type_label') }}</span><span>{{ $s->t('status_label') }}</span></div>
    <ul class="rows" role="list">
        @foreach($projects as $i => $p)
            @php $cats = collect($p['categories'] ?? [])->pluck('slug')->all(); @endphp
            <li data-cats="{{ implode(' ', $cats) }}" @if($activeCategory && ! in_array($activeCategory, $cats, true)) hidden @endif>
                <a class="row" href="{{ route('projects.show', ['locale' => $locale, 'slug' => $p['slug']]) }}" data-i="{{ $i }}" data-cursor="{{ $s->t('cursor_label') }}">
                    <h3>{{ tr($p['title']) }}</h3><span>{{ tr($p['kind']) }}</span><span class="status {{ $p['status_tone'] === 'live' ? 'live' : '' }}">{{ tr($p['status_label']) }}</span>
                </a>
            </li>
        @endforeach
    </ul>
    <div class="preview" data-preview aria-hidden="true"><div class="strip" data-strip>
        @foreach($projects as $p)
            @if($cover = $site->media($p['cover_media_id']))<img src="{{ $cover->url() }}" alt="" loading="lazy" decoding="async" width="420" height="263">@else<i></i>@endif
        @endforeach
    </div></div>
</section>
