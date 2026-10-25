<?php

namespace App\Enums;

/**
 * Physical condition of a unit, set by staff when editing a unit or when it comes back from a rental.
 *
 * Separate from whether the unit is rented: only units in Good condition can be booked.
 */
enum UnitCondition: string
{
    case Good = 'good';
    case UnderRepair = 'under_repair';
    case Damaged = 'damaged';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Good => 'Baik',
            self::UnderRepair => 'Dalam perbaikan',
            self::Damaged => 'Rusak',
            self::Lost => 'Hilang',
        };
    }

    public function isUsable(): bool
    {
        return $this === self::Good;
    }

    /**
     * Tailwind classes for the condition badge.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Good => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400',
            self::UnderRepair => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-400',
            self::Damaged => 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400',
            self::Lost => 'border-slate-300 bg-slate-100 text-slate-700 dark:border-slate-500/30 dark:bg-slate-500/15 dark:text-slate-300',
        };
    }

    /**
     * Position when sorting by condition: usable first, lost last.
     */
    public function rank(): int
    {
        return array_search($this, self::cases(), true);
    }

    /**
     * All cases as [value => label], in declaration order.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $condition): array => [$condition->value => $condition->label()])->all();
    }
}
