<section class="services wrap" id="{{ $s->domId() }}" data-module="services">
    @if($s->filled('eyebrow'))<p class="mono eyebrow">{{ $s->t('eyebrow') }}</p>@endif
    @foreach($services as $svc)
        <div class="svc"><span class="k keep mono">{{ $svc['number'] }}</span><h3>{{ tr($svc['title']) }}</h3><p>{{ tr($svc['description']) }}</p></div>
    @endforeach
</section>
