<section class="cta-band tone-{{ $s->preset ?: 'brick' }}" id="{{ $s->domId() }}" data-module="reveal">
    <div class="wrap">
        <h2 class="split">{{ $s->words('title', true) }}</h2>
        @if($s->filled('text'))<p>{{ $s->t('text') }}</p>@endif
        @if($buttons = $s->buttons())<div class="hero-cta">@foreach($buttons as $button)<x-btn :button="$button" />@endforeach</div>@endif
    </div>
</section>
