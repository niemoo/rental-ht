@props(['variant' => 'default', 'size' => 'default', 'href' => null])

@php
    $classes = [
        'inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg border border-transparent text-sm font-medium whitespace-nowrap transition-all outline-none select-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 active:translate-y-px disabled:pointer-events-none disabled:opacity-50',
        match ($variant) {
            'outline' => 'border-border bg-background hover:bg-muted hover:text-foreground dark:border-input dark:bg-input/30 dark:hover:bg-input/50',
            'ghost' => 'hover:bg-muted hover:text-foreground dark:hover:bg-muted/50',
            default => 'bg-primary text-primary-foreground hover:bg-primary/80',
        },
        match ($size) {
            'sm' => 'h-7 rounded-md px-2.5 text-[0.8rem]',
            'icon' => 'size-8',
            default => 'h-8 px-2.5',
        },
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button'])->class($classes) }}>{{ $slot }}</button>
@endif
