<?php

namespace App\Support;

use App\Enums\DiscountType;
use App\Enums\PaymentStatus;
use App\Models\Rental;
use App\Models\RentalItem;
use Carbon\CarbonInterface;

/**
 * What a rental costs and how much of it has been paid. All amounts are whole rupiah.
 *
 * Rent = each unit's daily rate × the number of rental days. Total = rent − discount + extra charges;
 * a cancelled rental owes only its cancellation fee plus any extra charges.
 */
final readonly class RentalBill
{
    /**
     * @param  list<array{category: string, units: list<string>, quantity: int, rate: int, amount: int}>  $lines  Rent grouped by category and rate.
     */
    public function __construct(
        public int $days,
        public array $lines,
        public int $subtotal,
        public int $discount,
        public int $charges,
        public bool $isCancelled,
        public int $cancellationFee,
        public int $total,
        public int $paid,
    ) {}

    public static function for(Rental $rental): self
    {
        $rental->loadMissing(['items.unit.category', 'charges', 'payments']);

        $days = self::days($rental->start_date, $rental->due_date);

        $lines = $rental->items
            ->groupBy(fn (RentalItem $item): string => $item->unit->category->name.'|'.$item->daily_rate)
            ->map(fn ($items): array => [
                'category' => $items->first()->unit->category->name,
                'units' => $items->map(fn (RentalItem $item): string => $item->unit->code)->sort(SORT_NATURAL)->values()->all(),
                'quantity' => $items->count(),
                'rate' => (int) $items->first()->daily_rate,
                'amount' => $items->count() * (int) $items->first()->daily_rate * $days,
            ])
            ->sortBy([['category', 'asc'], ['rate', 'asc']])
            ->values()
            ->all();

        $subtotal = array_sum(array_column($lines, 'amount'));
        $discount = self::discountFor($rental->discount_type, (int) $rental->discount_value, $subtotal);
        $charges = (int) $rental->charges->sum('amount');
        $isCancelled = $rental->isCancelled();
        $cancellationFee = (int) $rental->cancellation_fee;

        return new self(
            days: $days,
            lines: $lines,
            subtotal: $subtotal,
            discount: $discount,
            charges: $charges,
            isCancelled: $isCancelled,
            cancellationFee: $cancellationFee,
            total: $isCancelled ? $cancellationFee + $charges : $subtotal - $discount + $charges,
            paid: (int) $rental->payments->sum('amount'),
        );
    }

    /**
     * Rental days, counting both the start and the due day.
     */
    public static function days(CarbonInterface $start, CarbonInterface $due): int
    {
        return (int) $start->copy()->startOfDay()->diffInDays($due->copy()->startOfDay()) + 1;
    }

    /**
     * The discount in rupiah: a percent of the subtotal (capped at 100%) or a fixed amount (capped at the subtotal).
     */
    public static function discountFor(?DiscountType $type, int $value, int $subtotal): int
    {
        return match ($type) {
            DiscountType::Percent => (int) round($subtotal * min($value, 100) / 100),
            DiscountType::Amount => min($value, $subtotal),
            null => 0,
        };
    }

    /**
     * Suggested late fee: for each unit, its daily rate × the calendar days it came back (or is still out)
     * past the due date.
     *
     * @return array{days: int, amount: int} days is the longest delay among the units.
     */
    public static function suggestedLateFee(Rental $rental, ?CarbonInterface $now = null): array
    {
        $now ??= now();
        $dueDay = $rental->due_date->copy()->startOfDay();
        $days = 0;
        $amount = 0;

        foreach ($rental->items()->get() as $item) {
            $late = max(0, (int) $dueDay->diffInDays(($item->returned_at ?? $now)->copy()->startOfDay(), false));
            $days = max($days, $late);
            $amount += $late * $item->daily_rate;
        }

        return ['days' => $days, 'amount' => $amount];
    }

    /**
     * What is still owed; negative when the customer paid more than the total.
     */
    public function balance(): int
    {
        return $this->total - $this->paid;
    }

    public function status(): PaymentStatus
    {
        return PaymentStatus::for($this->total, $this->paid);
    }

    /**
     * Format whole rupiah for display, e.g. Rp1.250.000 or −Rp50.000.
     */
    public static function rupiah(int $amount): string
    {
        return ($amount < 0 ? '−' : '').'Rp'.number_format(abs($amount), 0, ',', '.');
    }
}
