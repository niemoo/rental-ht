<?php

namespace App\Enums;

/**
 * Kinds of extra charge added to a rental on top of the rent.
 */
enum ChargeType: string
{
    case LateFee = 'late_fee';
    case Damage = 'damage';
    case Loss = 'loss';
    case Delivery = 'delivery';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::LateFee => 'Denda telat',
            self::Damage => 'Kerusakan',
            self::Loss => 'Kehilangan',
            self::Delivery => 'Ongkos antar',
            self::Other => 'Lainnya',
        };
    }

    /**
     * All cases as [value => label], in declaration order.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])->all();
    }
}
