@props(['placeholder' => null])

<div class="search-box flex-grow-1" style="min-width: min(100%, 16rem)">
    <x-input type="search" :placeholder="$placeholder" :aria-label="$placeholder" {{ $attributes->class(['search']) }} />
    <x-icon name="search" class="search-icon" />
</div>
