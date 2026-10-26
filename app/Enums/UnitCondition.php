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
     * Velzon (Bootstrap) classes for the condition badge.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Good => 'bg-success-subtle text-success',
            self::UnderRepair => 'bg-warning-subtle text-warning',
            self::Damaged => 'bg-danger-subtle text-danger',
            self::Lost => 'bg-dark-subtle text-body',
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
