<?php

namespace App\Enums;

/**
 * Where a rental's bill stands, worked out from its total and the payments recorded against it.
 * Never set by hand: see {@see self::for()}.
 */
enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overpaid = 'overpaid';

    public static function for(int $total, int $paid): self
    {
        return match (true) {
            $paid > $total => self::Overpaid,
            $paid === $total => self::Paid,
            $paid <= 0 => self::Unpaid,
            default => self::Partial,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum bayar',
            self::Partial => 'DP',
            self::Paid => 'Lunas',
            self::Overpaid => 'Lebih bayar',
        };
    }

    /**
     * Velzon (Bootstrap) classes for the payment badge.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Unpaid => 'bg-danger-subtle text-danger',
            self::Partial => 'bg-warning-subtle text-warning',
            self::Paid => 'bg-success-subtle text-success',
            self::Overpaid => 'bg-primary-subtle text-primary',
        };
    }

    /**
     * SQL condition matching rentals in this status, from the stored rentals.total_amount / paid_amount.
     */
    public function sqlCondition(): string
    {
        return match ($this) {
            self::Overpaid => 'rentals.paid_amount > rentals.total_amount',
            self::Paid => 'rentals.paid_amount = rentals.total_amount',
            self::Unpaid => 'rentals.paid_amount <= 0 AND rentals.paid_amount < rentals.total_amount',
            self::Partial => 'rentals.paid_amount > 0 AND rentals.paid_amount < rentals.total_amount',
        };
    }

    /**
     * All cases as [value => label], in declaration order.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])->all();
    }
}
