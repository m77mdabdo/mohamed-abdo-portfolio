<section class="process wrap" id="{{ $s->domId() }}" data-module="process">
    @if($s->filled('eyebrow'))<p class="mono eyebrow">{{ $s->t('eyebrow') }}</p>@endif
    <ol class="steps">
        @foreach($steps as $step)
            <li class="step"><i class="bar"></i><h3>{{ tr($step['title']) }}</h3><p>{{ tr($step['description']) }}</p></li>
        @endforeach
    </ol>
</section>
