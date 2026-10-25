@props(['placeholder' => null])

<div class="relative flex-1">
    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground" />
    <x-input type="search" :placeholder="$placeholder" {{ $attributes->class(['pl-9 shadow-xs']) }} />
</div>
