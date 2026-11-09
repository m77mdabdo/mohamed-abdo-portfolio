@php
    $status = session('contact_status');
    $email = $identity['email'] ?? '';
    $types = array_values(array_filter(array_map(fn ($t) => $s->itemText($t, 'label'), $s->items('project_types'))));
@endphp
<section class="contact wrap" id="{{ $s->domId() }}" data-module="contact">
    @if($s->filled('eyebrow'))<p class="mono eyebrow">{{ $s->t('eyebrow') }}</p>@endif
    @if($s->filled('title'))<h2 class="split">{{ $s->words('title', true) }}</h2>@endif
    <div class="line">@if($email && $s->filled('circle_label'))<a class="btn circle-btn" href="mailto:{{ $email }}" data-magnetic data-strength="0.5">{{ $s->t('circle_label') }}</a>@endif</div>
    <div class="cols">
        <div>
            @if($email)
                <div class="email-line"><code>{{ $email }}</code>@if($s->filled('copy_label'))<button class="pill" type="button" data-copy="{{ $email }}" data-copied="{{ $s->t('copied_label') }}">{{ $s->t('copy_label') }}</button>@endif</div>
            @endif
            <div class="pillrow">
                @foreach($identity['social'] ?? [] as $link)
                    @if($link['url'])<a class="pill" href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" data-magnetic>{{ $link['label'] }} ↗</a>@endif
                @endforeach
            </div>
            <p class="city">{{ tr($identity['city']) }}</p>
        </div>
        @if($s->on('show_form'))
            <form class="f" method="post" action="{{ route('contact.store', ['locale' => $locale]) }}" data-contact-form novalidate
                  data-success="{{ $s->t('success_message') }}" data-error="{{ $s->t('error_message') }}">
                @csrf
                <input type="hidden" name="submission_key" value="{{ $submissionKey }}">
                <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                <label for="f-name-{{ $s->uid }}">{{ $s->t('name_label') }}
                    <input id="f-name-{{ $s->uid }}" name="name" required maxlength="120" autocomplete="name" value="{{ old('name') }}" @error('name') aria-invalid="true" aria-describedby="e-name-{{ $s->uid }}" @enderror>
                    @error('name')<span class="err" id="e-name-{{ $s->uid }}">{{ $message }}</span>@enderror
                </label>
                <label for="f-email-{{ $s->uid }}">{{ $s->t('email_label') }}
                    <input id="f-email-{{ $s->uid }}" name="email" type="email" required maxlength="190" autocomplete="email" dir="ltr" value="{{ old('email') }}" @error('email') aria-invalid="true" aria-describedby="e-email-{{ $s->uid }}" @enderror>
                    @error('email')<span class="err" id="e-email-{{ $s->uid }}">{{ $message }}</span>@enderror
                </label>
                @if($types)
                    <label for="f-type-{{ $s->uid }}">{{ $s->t('type_label') }}
                        <select id="f-type-{{ $s->uid }}" name="project_type">@foreach($types as $type)<option @selected(old('project_type') === $type)>{{ $type }}</option>@endforeach</select>
                    </label>
                @endif
                <label for="f-msg-{{ $s->uid }}">{{ $s->t('message_label') }}
                    <textarea id="f-msg-{{ $s->uid }}" name="message" rows="3" maxlength="5000">{{ old('message') }}</textarea>
                    @error('message')<span class="err">{{ $message }}</span>@enderror
                </label>
                <div class="f-actions">
                    <button class="btn solid" type="submit">{{ $s->t('submit_label') }} <span class="arr" aria-hidden="true">→</span></button>
                    @if($s->filled('note'))<p class="note">{{ $s->t('note') }}</p>@endif
                </div>
                <p class="f-status {{ $status ? ($status['ok'] ? 'ok' : 'bad') : '' }}" role="status" aria-live="polite" data-form-status>{{ $status['message'] ?? '' }}</p>
            </form>
        @endif
    </div>
</section>
