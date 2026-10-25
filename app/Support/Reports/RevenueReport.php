<?php

namespace App\Support\Reports;

use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Rental;
use App\Models\RentalPayment;
use App\Support\RentalBill;
use App\Support\ReportPeriod;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pendapatan: money actually received in the period (by payment date), next to the omzet — the value of
 * every rental that started in the period, paid or not. Refunds are negative payments and count against
 * the money received. A cancelled rental's omzet is just its cancellation fee and extra charges.
 */
final class RevenueReport implements Report
{
    /** @var array{omzet: int, unpaid: int, received: int, refunded: int, net: int}|null */
    private ?array $summaryFigures = null;

    /**
     * @param  list<PaymentMethod>  $methods  Only count payments made this way; empty means every method.
     */
    public function __construct(
        public readonly ReportPeriod $period,
        public readonly array $methods = [],
    ) {}

    public function title(): string
    {
        return 'Laporan Pendapatan';
    }

    public function subtitle(): string
    {
        return $this->period->label();
    }

    public function filename(): string
    {
        return 'laporan-pendapatan-'.$this->period->from->toDateString().'-'.$this->period->to->toDateString();
    }

    /**
     * @return array{omzet: int, unpaid: int, received: int, refunded: int, net: int}
     */
    public function figures(): array
    {
        if ($this->summaryFigures !== null) {
            return $this->summaryFigures;
        }

        $rentals = $this->rentalsStartedInPeriod()
            ->toBase()
            ->selectRaw('COALESCE(SUM(total_amount), 0) as omzet')
            ->selectRaw('COALESCE(SUM(GREATEST(total_amount - paid_amount, 0)), 0) as unpaid')
            ->first();

        $payments = $this->payments()
            ->toBase()
            ->selectRaw('COALESCE(SUM(CASE WHEN rental_payments.amount > 0 THEN rental_payments.amount ELSE 0 END), 0) as received')
            ->selectRaw('COALESCE(SUM(CASE WHEN rental_payments.amount < 0 THEN -rental_payments.amount ELSE 0 END), 0) as refunded')
            ->first();

        return $this->summaryFigures = [
            'omzet' => (int) $rentals->omzet,
            'unpaid' => (int) $rentals->unpaid,
            'received' => (int) $payments->received,
            'refunded' => (int) $payments->refunded,
            'net' => (int) $payments->received - (int) $payments->refunded,
        ];
    }

    /**
     * Net money received per payment method, every method listed even when zero.
     *
     * @return array<string, int> PaymentMethod value => rupiah
     */
    public function byMethod(): array
    {
        $totals = $this->payments()->toBase()
            ->select('rental_payments.method')
            ->selectRaw('SUM(rental_payments.amount) as total')
            ->groupBy('rental_payments.method')
            ->pluck('total', 'method');

        return collect(PaymentMethod::cases())
            ->filter(fn (PaymentMethod $method): bool => $this->methods === [] || in_array($method, $this->methods, true))
            ->mapWithKeys(fn (PaymentMethod $method): array => [$method->value => (int) ($totals[$method->value] ?? 0)])
            ->all();
    }

    /**
     * Net money received per day or month, one series per payment method.
     *
     * @return array{categories: list<string>, series: list<array{name: string, data: list<int>}>}
     */
    public function chart(): array
    {
        $buckets = $this->period->buckets();
        $sums = [];

        foreach ($this->payments()->get(['rental_payments.amount', 'rental_payments.paid_at', 'rental_payments.method']) as $payment) {
            $sums[$payment->method->value][$this->period->bucketKey($payment->paid_at)] = ($sums[$payment->method->value][$this->period->bucketKey($payment->paid_at)] ?? 0) + $payment->amount;
        }

        return [
            'categories' => $buckets->values()->all(),
            'series' => collect(array_keys($this->byMethod()))
                ->map(fn (string $method): array => [
                    'name' => PaymentMethod::from($method)->label(),
                    'data' => $buckets->keys()->map(fn (string $key): int => $sums[$method][$key] ?? 0)->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Payments in the period (method filter applied) with invoice number and customer name, for the table.
     *
     * @return Builder<RentalPayment>
     */
    public function payments(): Builder
    {
        return RentalPayment::query()
            ->join('rentals', 'rentals.id', '=', 'rental_payments.rental_id')
            ->whereNull('rentals.deleted_at')
            ->whereBetween('rental_payments.paid_at', [$this->period->from->toDateString(), $this->period->to->toDateString()])
            ->when($this->methods !== [], fn (Builder $query) => $query->whereIn('rental_payments.method', array_column($this->methods, 'value')));
    }

    /**
     * {@see self::payments()} with the columns the table shows and sorts by.
     *
     * @return Builder<RentalPayment>
     */
    public function tableQuery(): Builder
    {
        return $this->payments()
            ->select('rental_payments.*', 'rentals.invoice_number')
            ->addSelect(['customer_name' => Customer::query()->withTrashed()->select('name')->whereColumn('customers.id', 'rentals.customer_id')])
            ->with(['rental', 'creator:id,name']);
    }

    public function summary(): array
    {
        $figures = $this->figures();

        return [
            'Omzet' => RentalBill::rupiah($figures['omzet']),
            'Uang masuk' => RentalBill::rupiah($figures['received']),
            'Pengembalian dana' => RentalBill::rupiah($figures['refunded']),
            'Uang masuk bersih' => RentalBill::rupiah($figures['net']),
            'Omzet belum dibayar' => RentalBill::rupiah($figures['unpaid']),
            ...collect($this->byMethod())->mapWithKeys(fn (int $total, string $method): array => ['Bersih via '.PaymentMethod::from($method)->label() => RentalBill::rupiah($total)])->all(),
        ];
    }

    public function headings(): array
    {
        return ['Tanggal bayar', 'No. invoice', 'Pelanggan', 'Metode', 'Jenis', 'Jumlah', 'Catatan', 'Dicatat oleh'];
    }

    public function moneyColumns(): array
    {
        return [5];
    }

    public function rows(): iterable
    {
        foreach ($this->tableQuery()->orderBy('rental_payments.paid_at')->orderBy('rental_payments.id')->lazy() as $payment) {
            yield [
                $payment->paid_at->toDateString(),
                $payment->invoice_number,
                $payment->customer_name,
                $payment->method->label(),
                $payment->isRefund() ? 'Pengembalian dana' : 'Pembayaran',
                $payment->amount,
                $payment->note,
                $payment->creator?->name,
            ];
        }
    }

    /**
     * @return Builder<Rental>
     */
    private function rentalsStartedInPeriod(): Builder
    {
        return Rental::query()->whereBetween('start_date', [$this->period->from, $this->period->to]);
    }
}
