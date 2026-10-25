@props(['title', 'subtitle' => null, 'action' => null])

<div class="flex items-end justify-between gap-4">
    <div>
        <h1 class="text-xl font-semibold tracking-tight lg:text-2xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-0.5 text-sm text-muted-foreground">{{ $subtitle }}</p>
        @endif
    </div>
    {{ $action }}
</div>
