@props(['button'])
{{-- A CMS button: label, resolved link and style (solid / ghost / outline). --}}
<a class="btn {{ $button['style'] === 'outline' ? '' : $button['style'] }}" href="{{ $button['href'] }}" data-magnetic
   @if($button['new_tab']) target="_blank" rel="noopener noreferrer" @endif
   @if($button['download']) data-cv-link @endif>
    {{ $button['label'] }}
    @if($button['external'] || $button['new_tab'])<span aria-hidden="true">↗</span>
    @elseif($button['download'])<span class="arr" aria-hidden="true">↓</span>
    @elseif($button['style'] === 'solid')<span class="arr" aria-hidden="true">→</span>@endif
</a>
