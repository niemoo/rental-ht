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
     * Velzon (Bootstrap) classes for the relative-date text shown next to this status.
     */
    public function textClass(): string
    {
        return match ($this) {
            self::Overdue => 'text-danger',
            self::Soon => 'text-warning',
            default => 'text-muted',
        };
    }

    /**
     * Velzon (Bootstrap) classes for this status's pill: green done on time, amber done late or
     * due within a day, light blue still out within the deadline, red still out past it,
     * blue not started yet, struck-through grey cancelled.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Completed => 'bg-success-subtle text-success',
            self::CompletedLate, self::Soon => 'bg-warning-subtle text-warning',
            self::OnTime => 'bg-info-subtle text-info',
            self::Overdue => 'bg-danger-subtle text-danger',
            self::Cancelled => 'bg-light text-muted text-decoration-line-through',
            self::Upcoming => 'bg-secondary-subtle text-secondary',
        };
    }
}
