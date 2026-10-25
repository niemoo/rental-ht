<?php

namespace App\Enums;

/**
 * How a rental's discount is given: a fixed rupiah amount, or a percent of the rent subtotal.
 */
enum DiscountType: string
{
    case Amount = 'amount';
    case Percent = 'percent';

    public function label(): string
    {
        return match ($this) {
            self::Amount => 'Rp',
            self::Percent => '%',
        };
    }
}
