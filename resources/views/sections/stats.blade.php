<section class="stats-band wrap" id="{{ $s->domId() }}" data-module="reveal">
    @if($s->filled('eyebrow'))<p class="mono eyebrow">{{ $s->t('eyebrow') }}</p>@endif
    <div class="stats">@foreach($stats as $stat)<div><b class="keep">{{ $stat['value'] }}</b><span>{{ tr($stat['label']) }}</span></div>@endforeach</div>
</section>
