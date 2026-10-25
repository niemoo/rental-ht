<?php

namespace App\Support;

use App\Enums\RentalStatus;
use Carbon\CarbonInterface;

/**
 * Where a rental stands relative to today, plus the Indonesian copy shown for it.
 */
final readonly class RentalTiming
{
    public function __construct(
        public RentalStatus $status,
        public string $label,
        public string $pill,
    ) {}

    public static function cancelled(): self
    {
        return new self(RentalStatus::Cancelled, 'dibatalkan', 'Dibatalkan');
    }

    /**
     * A fully returned rental: late when its last unit came back on a calendar day after the due date.
     */
    public static function completed(CarbonInterface $dueDate, ?CarbonInterface $lastReturnedAt): self
    {
        $daysLate = $lastReturnedAt ? self::calendarDaysBetween($dueDate, $lastReturnedAt) : 0;

        if ($daysLate > 0) {
            return new self(RentalStatus::CompletedLate, "kembali {$daysLate} hari terlambat", "Selesai, telat {$daysLate}h");
        }

        return new self(RentalStatus::Completed, '', 'Selesai');
    }

    /**
     * Classify an open (not fully returned) rental by calendar days, not hours.
     */
    public static function for(CarbonInterface $startDate, CarbonInterface $dueDate, ?CarbonInterface $now = null): self
    {
        $now ??= now();

        $startsIn = self::calendarDaysBetween($now, $startDate);
        if ($startsIn > 0) {
            return new self(
                RentalStatus::Upcoming,
                $startsIn === 1 ? 'mulai besok' : "mulai {$startsIn} hari lagi",
                'Mendatang',
            );
        }

        $dueIn = self::calendarDaysBetween($now, $dueDate);
        if ($dueIn < 0) {
            $daysLate = -$dueIn;

            return new self(RentalStatus::Overdue, "{$daysLate} hari lalu", "Terlambat {$daysLate}h");
        }

        if ($dueIn <= 1) {
            return new self(
                RentalStatus::Soon,
                $dueIn === 0 ? 'jatuh tempo hari ini' : 'jatuh tempo besok',
                'Segera',
            );
        }

        return new self(RentalStatus::OnTime, "jatuh tempo {$dueIn} hari lagi", 'Berjalan');
    }

    private static function calendarDaysBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) round($from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay(), false));
    }
}
