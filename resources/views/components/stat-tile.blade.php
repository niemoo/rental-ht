@props(['label', 'value', 'icon' => null, 'sub' => null, 'trend' => null])

{{-- $trend: percentage change (int) or null. Shown instead of $sub when present. --}}
<div class="rounded-2xl border border-border bg-card p-4 shadow-xs lg:p-5">
    @if ($icon)
        <div class="mb-4 grid size-11 place-items-center rounded-xl bg-muted text-foreground lg:mb-5 lg:size-12">
            <x-icon :name="$icon" class="size-6" />
        </div>
    @endif
    <div class="flex items-end justify-between gap-2">
        <div>
            <div class="text-sm text-muted-foreground">{{ $label }}</div>
            <div class="mt-1 text-2xl font-bold tracking-tight tabular-nums lg:text-3xl">{{ $value }}</div>
        </div>
        @if ($trend !== null)
            <span @class([
                'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold',
                'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' => $trend >= 0,
                'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400' => $trend < 0,
            ])>
                <x-icon :name="$trend >= 0 ? 'arrow-up' : 'arrow-down'" class="size-3" />
                {{ abs($trend) }}%
            </span>
        @elseif ($sub)
            <span class="text-xs text-muted-foreground">{{ $sub }}</span>
        @endif
    </div>
</div>
