{{-- Published versions of one page / project / list / setting, newest first. --}}
<div style="display:grid;gap:.5rem">
    @forelse($revisions as $i => $rev)
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;padding:.75rem 1rem;border:1px solid rgba(127,127,127,.25);border-radius:.75rem">
            <div style="display:grid;gap:.15rem">
                <strong>{{ __('Version :v', ['v' => $rev->version]) }} @if($i === 0)<span style="color:#16a34a;font-weight:500">· {{ __('live now') }}</span>@endif</strong>
                <span style="font-size:.8rem;opacity:.75">{{ $rev->created_at?->locale(app()->getLocale())->isoFormat('LLL') }} · {{ $rev->publisher?->name ?? __('System') }}</span>
                @if($rev->note)<span style="font-size:.8rem;opacity:.75">{{ $rev->note }}</span>@endif
            </div>
            @if($canPublish && $i > 0)
                <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                    <x-filament::button size="sm" color="gray" wire:click="restoreRevision({{ $rev->id }})">{{ __('Restore to draft') }}</x-filament::button>
                    <x-filament::button size="sm" wire:click="restoreRevision({{ $rev->id }}, true)" wire:confirm="{{ __('Put version :v live now?', ['v' => $rev->version]) }}">{{ __('Restore and publish') }}</x-filament::button>
                </div>
            @endif
        </div>
    @empty
        <p>{{ __('Nothing has been published yet.') }}</p>
    @endforelse
</div>
