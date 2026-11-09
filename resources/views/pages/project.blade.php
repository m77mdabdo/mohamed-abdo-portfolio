@extends('layouts.site', ['bodyClass' => 'is-case'])

@php
    $L = fn (string $key) => tr($labels[$key] ?? null);
    $title = tr($project['title']);
    $cover = $site->media($project['cover_media_id']);
    $desktop = array_values(array_filter($project['gallery'] ?? [], fn ($g) => $g['kind'] === 'desktop'));
    $mobile = array_values(array_filter($project['gallery'] ?? [], fn ($g) => $g['kind'] === 'mobile'));
    $backHref = $workPage ? $site->pageUrl($workPage, $locale) : route('home', ['locale' => $locale]).'#work';
    $pad = fn (int $n) => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
@endphp

@section('content')
<article class="case-page" data-module="case" style="--tone:{{ hex_color($project['color'], '#1C181A') }}">
    <div class="case-top wrap">
        <a class="btn" href="{{ $backHref }}" data-magnetic><span class="arr back" aria-hidden="true">→</span> {{ $L('back') }}</a>
        <span class="mono keep case-count" dir="ltr">{{ $pad($position) }} / {{ $pad($total) }}</span>
    </div>
    <header class="case-hero wrap">
        <p class="mono eyebrow">{{ tr($project['kind']) }}</p>
        <h1 class="split">{{ \App\Support\RichTitle::words($title, true) }}</h1>
        <p class="kind">{{ tr($project['summary']) }}</p>
        <dl class="case-meta">
            <div><dt>{{ $L('role') }}</dt><dd>{{ tr($project['role']) ?: $L('role_default') }}</dd></div>
            <div><dt>{{ $L('status') }}</dt><dd><span class="status {{ $project['status_tone'] === 'live' ? 'live' : '' }}">{{ tr($project['status_label']) }}</span></dd></div>
            @if(tr($project['client']))
                <div><dt>{{ $L('client') }}</dt><dd>{{ tr($project['client']) }}</dd></div>
            @else
                <div><dt>{{ $L('languages') }}</dt><dd>{{ tr($project['languages']) ?: $L('languages_default') }}</dd></div>
            @endif
            <div><dt>{{ $L('stack') }}</dt><dd class="tags">@foreach($project['stack'] ?? [] as $tech)<span>{{ $tech['name'] }}</span>@endforeach</dd></div>
            @if($project['year'] && $project['year'] !== '—')<div><dt>{{ $L('year') }}</dt><dd class="keep">{{ $project['year'] }}</dd></div>@endif
        </dl>
    </header>

    @if($cover)
        <div class="wrap"><div class="case-img"><x-picture :media="$cover" :alt="$title" sizes="100vw" :priority="true" /></div></div>
    @endif

    <div class="case-body wrap">
        <div><p class="mono eyebrow">{{ $L('challenge') }}</p></div>
        <div>
            @if(tr($project['brief']))<h2>{{ tr($project['brief']) }}</h2>@endif
            @if(tr($project['built']))
                <p class="mono eyebrow sol">{{ $L('solution') }}</p><p>{{ tr($project['built']) }}</p>
            @endif
            @if($project['features'])
                <p class="mono eyebrow">{{ $L('features') }}</p>
                <ul class="feat">@foreach($project['features'] as $f)@if(tr($f))<li>{{ tr($f) }}</li>@endif @endforeach</ul>
            @endif
            <div class="case-links">
                @if($project['live_url'])<a class="btn solid" href="{{ $project['live_url'] }}" target="_blank" rel="noopener noreferrer">{{ $L('visit') }} ↗</a>@endif
                @if($project['repo_url'])<a class="btn" href="{{ $project['repo_url'] }}" target="_blank" rel="noopener noreferrer">{{ $L('repo') }} ↗</a>@endif
            </div>
        </div>
    </div>

    @if($desktop || $mobile)
        <section class="case-shots wrap" aria-label="{{ $L('shots') }}">
            <p class="mono eyebrow">{{ $L('shots') }}</p>
            @if($desktop)
                <div class="shots">
                    @foreach($desktop as $n => $shot)
                        <figure><x-picture :media="$site->media($shot['media_id'])" :alt="tr($shot['caption']) ?: $site->ui('project.shot_alt', ['title' => $title, 'n' => $n + 1])" sizes="(max-width: 760px) 100vw, 50vw" /></figure>
                    @endforeach
                </div>
            @endif
            @if($mobile)
                <div class="phones">
                    @foreach($mobile as $n => $shot)
                        <figure><x-picture :media="$site->media($shot['media_id'])" :alt="tr($shot['caption']) ?: $site->ui('project.mobile_alt', ['title' => $title, 'n' => $n + 1])" sizes="(max-width: 760px) 30vw, 260px" /></figure>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    @if($next)
        <a class="case-next" href="{{ route('projects.show', ['locale' => $locale, 'slug' => $next['slug']]) }}" data-cursor="{{ $L('next') }}">
            <small class="mono">{{ $L('next') }}</small><strong>{{ tr($next['title']) }}</strong>
        </a>
    @endif
</article>
@endsection
