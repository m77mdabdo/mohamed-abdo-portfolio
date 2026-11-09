@php $paragraphs = array_filter(preg_split('/\n\s*\n/u', $s->t('body')) ?: []); @endphp
<section class="image-text wrap tone-{{ $s->preset ?: 'dark' }} img-{{ $s->raw('image_side', 'end') }}" id="{{ $s->domId() }}" data-module="reveal">
    <div class="it-copy">
        @if($s->filled('eyebrow'))<p class="mono eyebrow">{{ $s->t('eyebrow') }}</p>@endif
        @if($s->filled('title'))<h2>{{ $s->title('title') }}</h2>@endif
        <div class="prose">@foreach($paragraphs as $p)<p>{!! nl2br(e(trim($p)), false) !!}</p>@endforeach</div>
        @if($buttons = $s->buttons())<div class="hero-cta">@foreach($buttons as $button)<x-btn :button="$button" />@endforeach</div>@endif
    </div>
    @if($image = $s->media('image'))<div class="it-media"><x-picture :media="$image" sizes="(max-width: 860px) 100vw, 50vw" /></div>@endif
</section>
