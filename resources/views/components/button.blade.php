@props(['variant' => 'default', 'size' => 'default', 'href' => null])

{{-- Velzon button. variant: default (primary), outline (neutral), ghost, success, danger. size: default, sm, icon. --}}
@php
    $classes = [
        'btn d-inline-flex align-items-center justify-content-center gap-1 text-nowrap',
        match ($variant) {
            'outline' => 'btn-light',
            'ghost' => 'btn-ghost-dark',
            'success' => 'btn-success',
            'danger' => 'btn-danger',
            default => 'btn-primary',
        },
        match ($size) {
            'sm' => 'btn-sm',
            'icon' => 'btn-icon btn-sm',
            default => null,
        },
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button'])->class($classes) }}>{{ $slot }}</button>
@endif
