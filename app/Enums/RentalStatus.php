<?php

namespace App\Enums;

enum RentalStatus: string
{
    case Upcoming = 'upcoming';
    case Overdue = 'overdue';
    case Soon = 'soon';
    case OnTime = 'on-time';
    case Completed = 'completed';
    case CompletedLate = 'completed-late';
    case Cancelled = 'cancelled';

    /**
     * Tailwind classes for the relative-date text shown next to this status.
     */
    public function textClass(): string
    {
        return match ($this) {
            self::Overdue => 'text-red-600 dark:text-red-400',
            self::Soon => 'text-amber-600 dark:text-amber-400',
            default => 'text-muted-foreground',
        };
    }

    /**
     * Tailwind classes for this status's pill: green done on time, orange done late,
     * blue still out within the deadline, red still out past it, grey not started yet,
     * struck-through grey cancelled.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Completed => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400',
            self::CompletedLate => 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-500/20 dark:bg-orange-500/10 dark:text-orange-400',
            self::OnTime, self::Soon => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-400',
            self::Overdue => 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400',
            self::Cancelled => 'border-slate-200 bg-white text-slate-400 line-through dark:border-slate-600/30 dark:bg-transparent dark:text-slate-500',
            self::Upcoming => 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-500/20 dark:bg-slate-500/10 dark:text-slate-300',
        };
    }
}
