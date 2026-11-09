{{-- Dashboard language switch (Arabic / English). --}}
@php $other = app()->getLocale() === 'ar' ? 'en' : 'ar'; @endphp
<form method="post" action="{{ route('admin.locale') }}" class="ma-locale-switch">
    @csrf
    <input type="hidden" name="locale" value="{{ $other }}">
    <button type="submit" class="fi-btn fi-size-sm fi-color-gray fi-btn-color-gray" style="padding:.35rem .7rem;border-radius:.5rem;border:1px solid rgba(127,127,127,.35);font-size:.8rem">
        {{ $other === 'en' ? 'English' : 'العربية' }}
    </button>
</form>
