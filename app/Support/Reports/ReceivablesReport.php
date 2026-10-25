<?php

namespace App\Support\Reports;

use App\Models\Customer;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Support\RentalBill;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Piutang: every rental with money still owed, as of now (not over a period), grouped by how long it has
 * been owed. The age of a finished rental counts from its due date, or from the day it was cancelled.
 * Rentals the customer overpaid are listed separately, as refunds still to make.
 */
final class ReceivablesReport implements Report
{
    /** Age groups, from not started yet to longest overdue. */
    public const AGINGS = [
        'upcoming' => 'Belum mulai',
        'running' => 'Sedang berjalan',
        'days_0_7' => 'Selesai 0–7 hari',
        'days_8_30' => 'Selesai 8–30 hari',
        'days_over_30' => 'Selesai > 30 hari',
    ];

    public readonly CarbonImmutable $now;

    /**
     * @param  list<string>  $agings  Keys of {@see self::AGINGS} to keep; empty means all.
     */
    public function __construct(
        public readonly array $agings = [],
        ?CarbonInterface $now = null,
    ) {
        $this->now = CarbonImmutable::instance($now ?? now());
    }

    public function title(): string
    {
        return 'Laporan Piutang';
    }

    public function subtitle(): string
    {
        return 'Per '.$this->now->translatedFormat('j M Y');
    }

    public function filename(): string
    {
        return 'laporan-piutang-'.$this->now->toDateString();
    }

    /**
     * Totals per age group (ignoring the age filter), plus overall and overpaid figures.
     *
     * @return array{total: int, count: int, by_aging: array<string, array{count: int, total: int}>, overpaid_total: int, overpaid_count: int}
     */
    public function figures(): array
    {
        $groups = Rental::query()->whereColumn('total_amount', '>', 'paid_amount')
            ->toBase()
            ->selectRaw($this->agingSql().' as aging')
            ->selectRaw('COUNT(*) as rentals')
            ->selectRaw('SUM(total_amount - paid_amount) as owed')
            ->groupBy('aging')
            ->get()
            ->keyBy('aging');

        $overpaid = Rental::query()->whereColumn('paid_amount', '>', 'total_amount')
            ->toBase()
            ->selectRaw('COUNT(*) as rentals')
            ->selectRaw('COALESCE(SUM(paid_amount - total_amount), 0) as owed')
            ->first();

        $byAging = collect(self::AGINGS)->map(fn (string $label, string $aging): array => [
            'count' => (int) ($groups[$aging]->rentals ?? 0),
            'total' => (int) ($groups[$aging]->owed ?? 0),
        ])->all();

        return [
            'total' => array_sum(array_column($byAging, 'total')),
            'count' => array_sum(array_column($byAging, 'count')),
            'by_aging' => $byAging,
            'overpaid_total' => (int) $overpaid->owed,
            'overpaid_count' => (int) $overpaid->rentals,
        ];
    }

    /**
     * Rentals still owing money (age filter applied), with age group, days owed, balance and customer name.
     *
     * @return Builder<Rental>
     */
    public function tableQuery(): Builder
    {
        $aging = $this->agingSql();

        return Rental::query()
            ->whereColumn('rentals.total_amount', '>', 'rentals.paid_amount')
            ->when($this->agings !== [], fn (Builder $query) => $query->whereRaw("({$aging}) IN (".implode(', ', array_fill(0, count($this->agings), '?')).')', $this->agings))
            ->select('rentals.*')
            ->addSelect(['customer_name' => Customer::query()->withTrashed()->select('name')->whereColumn('customers.id', 'rentals.customer_id')])
            ->selectRaw("{$aging} as aging")
            ->selectRaw($this->ageReferenceSql().' as owed_since')
            ->selectRaw('rentals.total_amount - rentals.paid_amount as balance')
            ->with('customer');
    }

    /**
     * Rentals the customer paid more than the total for: money to give back.
     *
     * @return Builder<Rental>
     */
    public function overpaidQuery(): Builder
    {
        return Rental::query()
            ->whereColumn('rentals.paid_amount', '>', 'rentals.total_amount')
            ->select('rentals.*')
            ->selectRaw('rentals.paid_amount - rentals.total_amount as overpaid')
            ->with('customer')
            ->orderByDesc('overpaid');
    }

    /**
     * Whole days a finished rental has been owed (from its due date or cancellation), 0 when not finished.
     */
    public function daysOwed(Rental $rental): int
    {
        if (! in_array($rental->aging, ['days_0_7', 'days_8_30', 'days_over_30'], true)) {
            return 0;
        }

        return (int) max(0, CarbonImmutable::parse($rental->owed_since)->startOfDay()->diffInDays($this->now->startOfDay()));
    }

    public function summary(): array
    {
        $figures = $this->figures();

        return [
            'Total piutang' => RentalBill::rupiah($figures['total']),
            'Jumlah pemesanan' => (string) $figures['count'],
            ...collect($figures['by_aging'])->mapWithKeys(fn (array $group, string $aging): array => [
                self::AGINGS[$aging] => RentalBill::rupiah($group['total']).' ('.$group['count'].')',
            ])->all(),
            'Lebih bayar (perlu dikembalikan)' => RentalBill::rupiah($figures['overpaid_total']).' ('.$figures['overpaid_count'].')',
        ];
    }

    public function headings(): array
    {
        return ['No. invoice', 'Pelanggan', 'No. HP', 'Mulai', 'Kembali', 'Umur', 'Hari terutang', 'Total', 'Dibayar', 'Sisa'];
    }

    public function moneyColumns(): array
    {
        return [7, 8, 9];
    }

    public function rows(): iterable
    {
        foreach ($this->tableQuery()->orderByDesc('balance')->orderBy('rentals.id')->lazy() as $rental) {
            yield [
                $rental->invoice_number,
                $rental->customer_name,
                $rental->customer->phone,
                $rental->start_date->toDateString(),
                $rental->due_date->toDateString(),
                self::AGINGS[$rental->aging],
                $this->daysOwed($rental),
                $rental->total_amount,
                $rental->paid_amount,
                $rental->balance,
            ];
        }
    }

    /**
     * SQL giving each rental its key in {@see self::AGINGS}. The current time is written into the SQL
     * (it comes from the server, never from input), so the expression can be reused in SELECT and WHERE.
     */
    private function agingSql(): string
    {
        $now = "'".$this->now->format('Y-m-d H:i:s')."'";
        $openItems = RentalItem::query()
            ->whereColumn('rental_items.rental_id', 'rentals.id')
            ->whereNull('rental_items.returned_at')
            ->toRawSql();
        $daysOwed = 'DATEDIFF('.$now.', '.$this->ageReferenceSql().')';

        return 'CASE'
            .' WHEN rentals.cancelled_at IS NULL AND rentals.start_date > '.$now." THEN 'upcoming'"
            .' WHEN rentals.cancelled_at IS NULL AND EXISTS ('.$openItems.") THEN 'running'"
            .' WHEN '.$daysOwed." <= 7 THEN 'days_0_7'"
            .' WHEN '.$daysOwed." <= 30 THEN 'days_8_30'"
            ." ELSE 'days_over_30' END";
    }

    /**
     * The moment a finished rental's debt starts ageing: when it was cancelled, else its due date.
     */
    private function ageReferenceSql(): string
    {
        return 'COALESCE(rentals.cancelled_at, rentals.due_date)';
    }
}
