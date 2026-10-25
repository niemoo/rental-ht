@props(['status'])

{{-- $status: an App\Enums\RentalStatus (which picks its own colours), or the unit states "rented" / "available". --}}
<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-xs font-medium whitespace-nowrap',
    $status instanceof \App\Enums\RentalStatus ? $status->badgeClass() : match ($status) {
        'overdue' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400',
        'soon' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-400',
        'available' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400',
        'upcoming' => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-400',
        'completed' => 'border-slate-200 bg-slate-50 text-slate-500 dark:border-slate-600/30 dark:bg-slate-500/10 dark:text-slate-400',
        default => 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-500/20 dark:bg-slate-500/10 dark:text-slate-300',
    },
]) }}>
    <span class="size-1.5 rounded-full bg-current opacity-90"></span>
    {{ $slot }}
</span>
