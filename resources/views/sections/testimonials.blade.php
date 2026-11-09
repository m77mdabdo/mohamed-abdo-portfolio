<section class="quotes wrap" id="{{ $s->domId() }}" data-module="reveal">
    @if($s->filled('eyebrow'))<p class="mono eyebrow">{{ $s->t('eyebrow') }}</p>@endif
    <div class="q-list">
        @foreach($quotes as $q)
            <figure class="q"><blockquote>“{{ tr($q['quote']) }}”</blockquote><figcaption><b>{{ $q['name'] }}</b>@if(tr($q['role']))<span>{{ tr($q['role']) }}</span>@endif</figcaption></figure>
        @endforeach
    </div>
</section>
