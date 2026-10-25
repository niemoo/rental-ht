@props(['title', 'subtitle' => null])

<div class="min-w-0 rounded-2xl border border-border bg-card p-4 shadow-xs lg:p-5">
    <div class="mb-3">
        <h3 class="text-[17px] font-semibold text-foreground">{{ $title }}</h3>
        @if ($subtitle)
            <p class="mt-0.5 text-sm text-muted-foreground">{{ $subtitle }}</p>
        @endif
    </div>
    {{ $slot }}
</div>
