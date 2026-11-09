<x-filament-widgets::widget>
    @php $pending = $this->pending(); @endphp
    <x-filament::section :heading="__('Waiting to be published')" icon="heroicon-o-rocket-launch"
        :description="$pending ? __('These changes are saved as drafts. Visitors still see the previous version.') : __('Everything you saved is live. The website shows the latest version.')">
        @if($pending)
            <x-slot name="afterHeader">
                @if($this->canPublish())
                    <x-filament::button size="sm" wire:click="publishAll" wire:confirm="{{ __('Publish all :n items now?', ['n' => count($pending)]) }}">{{ __('Publish all') }}</x-filament::button>
                @endif
            </x-slot>
            <div style="display:grid;gap:.4rem">
                @foreach($pending as $item)
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:.55rem .8rem;border:1px solid rgba(127,127,127,.22);border-radius:.6rem">
                        <span>{{ $item['label'] }} @if($item['never'])<small style="opacity:.7">· {{ __('never published') }}</small>@endif</span>
                        @if($this->canPublish())
                            <x-filament::button size="xs" color="gray" wire:click="publishOne('{{ $item['key'] }}')">{{ __('Publish') }}</x-filament::button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
