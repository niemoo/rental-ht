{{-- A whole-rupiah amount: a number input with an "Rp" prefix. Attributes go to the input (id, wire:model…). --}}
<div class="relative">
    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-sm text-muted-foreground">Rp</span>
    <x-input type="number" min="0" step="1" inputmode="numeric" {{ $attributes->class(['pl-9 tabular-nums']) }} />
</div>
