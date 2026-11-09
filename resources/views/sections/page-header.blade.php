<section class="page-head wrap preset-{{ $s->preset ?: 'glow' }}" id="{{ $s->domId() }}" data-module="reveal">
    @if(($s->preset ?: 'glow') === 'glow')<div class="leak l1" aria-hidden="true"></div><div class="leak l2" aria-hidden="true"></div>@endif
    @if($s->filled('eyebrow'))<p class="mono eyebrow">{{ $s->t('eyebrow') }}</p>@endif
    <h1 class="split">{{ $s->words('title', true) }}</h1>
    @if($s->filled('intro'))<p class="intro">{{ $s->t('intro') }}</p>@endif
    @if($buttons = $s->buttons())<div class="hero-cta">@foreach($buttons as $button)<x-btn :button="$button" />@endforeach</div>@endif
</section>
