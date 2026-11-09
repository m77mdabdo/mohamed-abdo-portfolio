<section class="statement wrap" id="{{ $s->domId() }}" data-module="statement">
    @if($s->filled('eyebrow'))<p class="mono eyebrow">{{ $s->t('eyebrow') }}</p>@endif
    <h2 class="stmt">{{ $s->words('line1') }} <span class="accent">{{ $s->words('line2') }}</span> <span class="signal">{{ $s->words('line3') }}</span></h2>
</section>
