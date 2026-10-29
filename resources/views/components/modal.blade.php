@props(['title', 'show' => null, 'footer' => null, 'size' => 'sm'])

{{--
    Velzon (Bootstrap) dialog opened by Alpine rather than Bootstrap's JS, so Livewire re-renders don't fight it.
    Driven either by a Livewire boolean ($show = property name, entangled)
    or by the surrounding Alpine scope's `open` variable when $show is null.
--}}
<div
    @if ($show) x-data="{ open: $wire.entangle('{{ $show }}') }" @endif
    x-show="open"
    x-cloak
    x-on:keydown.escape.window="open = false"
>
    <div class="modal-backdrop show"></div>
    <div class="modal d-block" tabindex="-1" role="dialog" aria-modal="true" x-on:click.self="open = false">
        <div @class(['modal-dialog modal-dialog-centered modal-dialog-scrollable', 'modal-lg' => $size === 'lg'])>
            <div class="modal-content text-start" x-trap.noscroll="open">
                <div class="modal-header bg-light p-3">
                    <h5 class="modal-title">{{ $title }}</h5>
                    <button type="button" class="btn-close" x-on:click="open = false" aria-label="Tutup"></button>
                </div>
                <div class="modal-body text-wrap">{{ $slot }}</div>
                @if ($footer)
                    <div class="modal-footer">{{ $footer }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
