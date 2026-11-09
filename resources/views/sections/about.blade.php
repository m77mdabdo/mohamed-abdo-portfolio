<section class="about wrap" id="{{ $s->domId() }}" data-module="about">
    <div class="grid">
        <div>
            @if($s->filled('eyebrow'))<p class="mono eyebrow">{{ $s->t('eyebrow') }}</p>@endif
            @if($s->filled('lead'))<p class="lead split">{{ $s->words('lead', true) }}</p>@endif
            @if($s->filled('body'))<p class="body">{{ $s->t('body') }}</p>@endif
            @if($stats)
                <div class="stats">@foreach($stats as $stat)<div><b class="keep">{{ $stat['value'] }}</b><span>{{ tr($stat['label']) }}</span></div>@endforeach</div>
            @endif
        </div>
        @if($cube)
            <div class="tech3d" data-module="cube" role="img" aria-label="{{ collect($cube)->pluck('name')->implode(', ') }}"
                 data-faces='@json(collect($cube)->map(fn ($t) => ['n' => $t['name'], 'r' => tr($t['role'])])->values())'>
                <div class="stage"><div class="ring r1"></div><div class="ring r2"></div>
                    <div class="cube3" data-cube>
                        @foreach($cube as $i => $tech)
                            <div class="f f{{ $i + 1 }}" style="--c:{{ hex_color($tech['color'], \App\Support\Icons::color($tech['icon'])) }}"><span class="ico">{{ \App\Support\Icons::svg($tech['icon'], 74) }}</span><b class="keep" dir="ltr">{{ $tech['name'] }}</b></div>
                        @endforeach
                    </div>
                    <div class="shadow"></div>
                </div>
                <div class="tech-cap" aria-hidden="true">
                    <span class="mono keep" dir="ltr" data-cube-idx>01 / {{ str_pad((string) count($cube), 2, '0', STR_PAD_LEFT) }}</span>
                    <strong class="keep" dir="ltr" data-cube-name>{{ $cube[0]['name'] }}</strong>
                    <span data-cube-role>{{ tr($cube[0]['role']) }}</span>
                </div>
            </div>
        @endif
    </div>
</section>
