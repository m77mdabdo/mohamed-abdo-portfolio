<section class="stackband" id="{{ $s->domId() }}" data-module="band" aria-label="{{ $s->plain('title') ?: $s->t('eyebrow') }}">
    <div class="wrap sb-head">
        @if($s->filled('eyebrow'))<p class="mono eyebrow">{{ $s->t('eyebrow') }}</p>@endif
        @if($s->filled('title'))<h2 class="split">{{ $s->words('title', true) }}</h2>@endif
    </div>
    <div class="sb-rows">
        @foreach($rows as $r => $row)
            <div class="sb-row" data-dir="{{ $r % 2 ? 1 : -1 }}">
                <ul class="sb-track" role="list">
                    @foreach([0, 1, 2] as $copy)
                        @foreach($row as $tech)
                            <li class="tile" style="--c:{{ hex_color($tech['color'], \App\Support\Icons::color($tech['icon'], '#49DDE4')) }}" @if($copy) aria-hidden="true" @endif><i>{{ \App\Support\Icons::svg($tech['icon'], 26) }}</i><b dir="ltr">{{ $tech['name'] }}</b></li>
                        @endforeach
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</section>
