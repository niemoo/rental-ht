@props(['title', 'show' => null, 'footer' => null, 'size' => 'sm'])

{{--
    Dialog driven either by a Livewire boolean ($show = property name, entangled)
    or by the surrounding Alpine scope's `open` variable when $show is null.
--}}
<div
    @if ($show) x-data="{ open: $wire.entangle('{{ $show }}') }" @endif
    x-show="open"
    x-cloak
    x-on:keydown.escape.window="open = false"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
>
    <div x-show="open" x-transition.opacity.duration.100ms class="fixed inset-0 bg-black/10 backdrop-blur-xs" x-on:click="open = false"></div>

    <div
        x-show="open"
        x-transition.duration.100ms
        x-trap.noscroll="open"
        @class([
            'relative grid max-h-[calc(100dvh-2rem)] w-full gap-4 overflow-y-auto rounded-xl bg-popover p-4 text-sm text-popover-foreground ring-1 ring-foreground/10',
            'max-w-sm' => $size === 'sm',
            'max-w-md' => $size === 'md',
            'max-w-2xl' => $size === 'lg',
        ])
    >
        <h2 class="text-base leading-none font-medium">{{ $title }}</h2>

        {{ $slot }}

        @if ($footer)
            <div class="-mx-4 -mb-4 flex flex-col-reverse gap-2 rounded-b-xl border-t bg-muted/50 p-4 sm:flex-row sm:justify-end">
                {{ $footer }}
            </div>
        @endif

        <x-button variant="ghost" size="icon" class="absolute top-2 right-2" x-on:click="open = false" aria-label="Tutup">
            <x-icon name="x" />
        </x-button>
    </div>
</div>
