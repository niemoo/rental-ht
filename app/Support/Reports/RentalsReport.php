<?php

namespace App\Support\Reports;

use App\Enums\ChargeType;
use App\Models\Customer;
use App\Models\Rental;
use App\Models\RentalCharge;
use App\Support\RentalBill;
use App\Support\ReportPeriod;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Penyewaan: the rentals that started in the period and what they are worth (billed, paid or not).
 * Cancelled rentals are counted and listed, but only their cancellation fee and extra charges count toward
 * omzet; averages leave them out.
 */
final class RentalsReport implements Report
{
    public readonly CarbonImmutable $now;

    /**
     * @param  list<string>  $statuses  Keys of {@see Rental::STATUSES} to list; empty means all. The figures ignore it.
     */
    public function __construct(
        public readonly ReportPeriod $period,
        public readonly array $statuses = [],
        ?CarbonInterface $now = null,
    ) {
        $this->now = CarbonImmutable::instance($now ?? now());
    }

    public function title(): string
    {
        return 'Laporan Penyewaan';
    }

    public function subtitle(): string
    {
        return $this->period->label();
    }

    public function filename(): string
    {
        return 'laporan-penyewaan-'.$this->period->from->toDateString().'-'.$this->period->to->toDateString();
    }

    /**
     * @return array{count: int, cancelled: int, cancelled_pct: int, omzet: int, average_value: int, average_days: float, discount: int, charges: array<string, int>}
     */
    public function figures(): array
    {
        $totals = $this->inPeriod()
            ->toBase()
            ->selectRaw('COUNT(*) as rentals')
            ->selectRaw('SUM(CASE WHEN cancelled_at IS NOT NULL THEN 1 ELSE 0 END) as cancelled')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as omzet')
            ->selectRaw('COALESCE(SUM(CASE WHEN cancelled_at IS NULL THEN total_amount ELSE 0 END), 0) as kept_omzet')
            ->selectRaw('AVG(CASE WHEN cancelled_at IS NULL THEN DATEDIFF(due_date, start_date) + 1 END) as average_days')
            ->first();

        $count = (int) $totals->rentals;
        $cancelled = (int) $totals->cancelled;
        $kept = $count - $cancelled;

        $charges = RentalCharge::query()
            ->whereHas('rental', fn (Builder $rental) => $rental->whereBetween('start_date', [$this->period->from, $this->period->to]))
            ->toBase()
            ->select('type')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return [
            'count' => $count,
            'cancelled' => $cancelled,
            'cancelled_pct' => $count === 0 ? 0 : (int) round($cancelled / $count * 100),
            'omzet' => (int) $totals->omzet,
            'average_value' => $kept === 0 ? 0 : (int) round($totals->kept_omzet / $kept),
            'average_days' => round((float) $totals->average_days, 1),
            'discount' => $this->discountTotal(),
            'charges' => collect(ChargeType::cases())->mapWithKeys(fn (ChargeType $type): array => [$type->value => (int) ($charges[$type->value] ?? 0)])->all(),
        ];
    }

    /**
     * Rentals and omzet per day or month.
     *
     * @return array{categories: list<string>, rentals: list<int>, omzet: list<int>}
     */
    public function chart(): array
    {
        $buckets = $this->period->buckets();
        $counts = [];
        $omzet = [];

        foreach ($this->inPeriod()->get(['start_date', 'total_amount']) as $rental) {
            $key = $this->period->bucketKey($rental->start_date);
            $counts[$key] = ($counts[$key] ?? 0) + 1;
            $omzet[$key] = ($omzet[$key] ?? 0) + $rental->total_amount;
        }

        return [
            'categories' => $buckets->values()->all(),
            'rentals' => $buckets->keys()->map(fn (string $key): int => $counts[$key] ?? 0)->all(),
            'omzet' => $buckets->keys()->map(fn (string $key): int => $omzet[$key] ?? 0)->all(),
        ];
    }

    /**
     * Rentals in the period (status filter applied) with the columns the table shows and sorts by.
     *
     * @return Builder<Rental>
     */
    public function tableQuery(): Builder
    {
        return $this->inPeriod()
            ->when($this->statuses !== [], fn (Builder $query) => $query->where(function (Builder $query): void {
                foreach ($this->statuses as $status) {
                    $query->orWhere(fn (Builder $query) => $query->inStatus($status, $this->now));
                }
            }))
            ->select('rentals.*')
            ->addSelect(['customer_name' => Customer::query()->withTrashed()->select('name')->whereColumn('customers.id', 'rentals.customer_id')])
            ->selectRaw(Rental::statusRankSql(), [$this->now])
            ->selectRaw('DATEDIFF(rentals.due_date, rentals.start_date) + 1 as days')
            ->selectRaw('rentals.total_amount - rentals.paid_amount as balance')
            ->with(['customer', 'openItems'])
            ->withCount('items')
            ->withMax('items', 'returned_at');
    }

    public function summary(): array
    {
        $figures = $this->figures();

        return [
            'Jumlah pemesanan' => (string) $figures['count'],
            'Omzet' => RentalBill::rupiah($figures['omzet']),
            'Rata-rata nilai' => RentalBill::rupiah($figures['average_value']),
            'Rata-rata durasi' => str_replace('.', ',', (string) $figures['average_days']).' hari',
            'Dibatalkan' => $figures['cancelled'].' ('.$figures['cancelled_pct'].'%)',
            'Total diskon' => RentalBill::rupiah($figures['discount']),
            ...collect($figures['charges'])->mapWithKeys(fn (int $total, string $type): array => [ChargeType::from($type)->label() => RentalBill::rupiah($total)])->all(),
        ];
    }

    public function headings(): array
    {
        return ['No. invoice', 'Pelanggan', 'Acara', 'Mulai', 'Kembali', 'Hari', 'Unit', 'Status', 'Total', 'Dibayar', 'Sisa'];
    }

    public function moneyColumns(): array
    {
        return [8, 9, 10];
    }

    public function rows(): iterable
    {
        foreach ($this->tableQuery()->orderBy('rentals.start_date')->orderBy('rentals.id')->lazy() as $rental) {
            yield [
                $rental->invoice_number,
                $rental->customer_name,
                $rental->event_name,
                $rental->start_date->toDateString(),
                $rental->due_date->toDateString(),
                (int) $rental->days,
                $rental->items_count,
                $rental->timing()->pill,
                $rental->total_amount,
                $rental->paid_amount,
                (int) $rental->balance,
            ];
        }
    }

    /**
     * Discounts given on the rentals that went ahead. Worked out per rental, since a percent discount
     * depends on that rental's subtotal.
     */
    private function discountTotal(): int
    {
        return $this->inPeriod()
            ->notCancelled()
            ->whereNotNull('discount_type')
            ->where('discount_value', '>', 0)
            ->with(['items.unit.category', 'charges', 'payments'])
            ->get()
            ->sum(fn (Rental $rental): int => $rental->bill()->discount);
    }

    /**
     * @return Builder<Rental>
     */
    private function inPeriod(): Builder
    {
        return Rental::query()->whereBetween('rentals.start_date', [$this->period->from, $this->period->to]);
    }
}
