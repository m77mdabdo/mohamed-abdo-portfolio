@php $paragraphs = array_filter(preg_split('/\n\s*\n/u', $s->t('body')) ?: []); @endphp
<section class="text-block wrap tone-{{ $s->preset ?: 'dark' }}" id="{{ $s->domId() }}" data-module="reveal">
    @if($s->filled('eyebrow'))<p class="mono eyebrow">{{ $s->t('eyebrow') }}</p>@endif
    @if($s->filled('title'))<h2>{{ $s->title('title') }}</h2>@endif
    <div class="prose">@foreach($paragraphs as $p)<p>{!! nl2br(e(trim($p)), false) !!}</p>@endforeach</div>
</section>
