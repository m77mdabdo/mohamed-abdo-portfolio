<section class="skills-section wrap" id="{{ $s->domId() }}" data-module="reveal">
    @if($s->filled('eyebrow'))<p class="mono eyebrow">{{ $s->t('eyebrow') }}</p>@endif
    <div class="skill-groups">
        @foreach($groups as $group)
            <div class="skill-group"><h3>{{ tr($group['name']) }}</h3><p class="chips">@foreach($group['items'] ?? [] as $item)<i>{{ $item }}</i>@endforeach</p></div>
        @endforeach
    </div>
</section>
