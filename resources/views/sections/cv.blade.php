@php $missing = session('cv_missing'); @endphp
<section class="cv wrap" id="{{ $s->domId() }}" data-module="cv">
    @if($s->on('show_card'))
        <div class="cv-card">
            <div class="cv-light" aria-hidden="true"></div>
            <x-picture :media="$portrait" alt="" sizes="(max-width: 900px) 80vw, 40vw" />
            <div class="cv-id"><span class="mono keep">{{ tr($identity['role']) }}</span><strong>{{ tr($identity['name']) }}</strong></div>
        </div>
    @endif
    <div class="cv-body">
        @if($s->filled('eyebrow'))<p class="mono eyebrow">{{ $s->t('eyebrow') }}</p>@endif
        @if($s->filled('title'))
            <h2 class="split cv-title">{{ $s->words('title', true) }}<svg class="scribble" viewBox="0 0 300 30" preserveAspectRatio="none" aria-hidden="true"><path class="doodle" pathLength="1" d="M4 20 C 60 6, 120 28, 170 14 S 260 4, 296 18"/></svg></h2>
        @endif
        <div class="cv-dl">
            @if($cvFile)
                @if($s->filled('view_label'))<a class="btn" href="{{ $cvUrl }}?view=1" target="_blank" rel="noopener" data-magnetic>{{ $s->t('view_label') }} <span aria-hidden="true">↗</span></a>@endif
                <a class="btn solid" href="{{ $cvUrl }}" data-magnetic data-cv-link>{{ $s->t('download_label') }} <span class="arr" aria-hidden="true">↓</span></a>
            @else
                <span class="btn off" aria-disabled="true">{{ $s->t('pending_label') }}</span>
                <p class="cv-note">{{ $missing ? $site->ui('cv.missing') : $s->t('pending_note') }}</p>
            @endif
        </div>
        @if($experience)
            @if($s->filled('experience_title'))<h3 class="cv-h">{{ $s->t('experience_title') }}</h3>@endif
            <div class="tl2" data-timeline>
                <svg class="tl-line" aria-hidden="true" preserveAspectRatio="none"><path pathLength="1"/></svg>
                @foreach($experience as $job)
                    <article class="job {{ $job['is_current'] ? 'current' : '' }}">
                        <i class="node" aria-hidden="true"></i>
                        @if($job['is_current'] && $s->filled('now_label'))<span class="sticker">{{ $s->t('now_label') }}</span>@endif
                        <span class="when mono">{{ tr($job['period']) }}</span>
                        <h4>{{ tr($job['role']) }}</h4>
                        <span class="org">{{ tr($job['organization']) }}</span>
                        @if(tr($job['description']))<p>{{ tr($job['description']) }}</p>@endif
                    </article>
                @endforeach
            </div>
        @endif
        @if($languages)
            @if($s->filled('languages_title'))<h3 class="cv-h">{{ $s->t('languages_title') }}</h3>@endif
            <div class="langs">
                @foreach($languages as $lang)
                    <div class="lg"><strong>{{ tr($lang['name']) }}</strong><svg viewBox="0 0 220 24" preserveAspectRatio="none" aria-hidden="true"><path class="doodle" pathLength="1" d="M4 16 C 40 4, 70 22, 110 12 S 180 6, 216 14"/></svg><span>{{ tr($lang['level']) }}</span></div>
                @endforeach
            </div>
        @endif
    </div>
</section>
