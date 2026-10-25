<?php

namespace App\Livewire\Rentals;

use App\Enums\PaymentStatus;
use App\Livewire\Concerns\SortsTable;
use App\Models\Customer;
use App\Models\Rental;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Rental history: every order, newest first by default. Status and Pembayaran are filtered from popups
 * on their column headers (multi-select; an empty selection means no filter).
 */
#[Title('Riwayat')]
class Index extends Component
{
    use SortsTable;

    #[Url(except: '')]
    public string $search = '';

    /** @var list<string> Keys of {@see Rental::STATUSES}. */
    #[Url(as: 'status', except: [])]
    public array $statuses = [];

    /** @var list<string> PaymentStatus values. */
    #[Url(as: 'payment', except: [])]
    public array $paymentStatuses = [];

    #[Url(as: 'sort', except: 'start')]
    public string $sortBy = 'start';

    #[Url(as: 'dir', except: 'desc')]
    public string $sortDirection = 'desc';

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'start' => 'rentals.start_date',
            'due' => 'rentals.due_date',
            'customer' => 'customer_name',
            'event' => 'rentals.event_name',
            'units' => 'items_count',
            'status' => 'status_rank',
            'payment' => 'balance',
        ];
    }

    public function render(): View
    {
        $now = now();

        $rentals = $this->paginateSorted(
            $this->filtered(Rental::query(), $now)
                ->select('rentals.*')
                ->addSelect(['customer_name' => Customer::query()->select('name')->whereColumn('customers.id', 'rentals.customer_id')])
                ->selectRaw(Rental::statusRankSql(), [$now])
                ->selectRaw('rentals.total_amount - rentals.paid_amount as balance')
                ->with(['customer', 'openItems'])
                ->withCount('items')
                ->withMax('items', 'returned_at')
        );

        return view('livewire.rentals.index', [
            'rentals' => $rentals,
            'total' => Rental::query()->count(),
            'statusFilter' => [
                'options' => Rental::STATUSES,
                'counts' => collect(Rental::STATUSES)->map(fn (string $label, string $status): int => $this->filtered(Rental::query(), $now, except: 'statuses')
                    ->where(fn (Builder $query) => $query->inStatus($status, $now))->count())->all(),
            ],
            'paymentFilter' => [
                'options' => PaymentStatus::options(),
                'counts' => collect(PaymentStatus::cases())->mapWithKeys(fn (PaymentStatus $status): array => [
                    $status->value => $this->filtered(Rental::query(), $now, except: 'paymentStatuses')->whereRaw($status->sqlCondition())->count(),
                ])->all(),
            ],
        ]);
    }

    /**
     * Apply the search and both column filters, optionally leaving one out (used for that filter's own counts).
     *
     * @param  'statuses'|'paymentStatuses'|null  $except
     * @param  Builder<Rental>  $query
     * @return Builder<Rental>
     */
    private function filtered(Builder $query, CarbonInterface $now, ?string $except = null): Builder
    {
        $search = trim($this->search);
        $statuses = $except === 'statuses' ? [] : $this->selectedStatuses();
        $paymentStatuses = $except === 'paymentStatuses' ? [] : $this->selectedPaymentStatuses();

        return $query
            ->when($statuses !== [], fn (Builder $query) => $query->where(function (Builder $query) use ($statuses, $now): void {
                foreach ($statuses as $status) {
                    $query->orWhere(fn (Builder $query) => $query->inStatus($status, $now));
                }
            }))
            ->when($paymentStatuses !== [], fn (Builder $query) => $query->where(function (Builder $query) use ($paymentStatuses): void {
                foreach ($paymentStatuses as $status) {
                    $query->orWhereRaw($status->sqlCondition());
                }
            }))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereLike('event_name', "%{$search}%")
                ->orWhereHas('customer', fn (Builder $customer) => $customer->whereLike('name', "%{$search}%"))
                ->orWhereHas('items.unit', fn (Builder $unit) => $unit->whereLike('code', "%{$search}%"))));
    }

    /**
     * The valid statuses picked; anything else (e.g. edited into the URL) is dropped.
     *
     * @return list<string>
     */
    private function selectedStatuses(): array
    {
        return collect($this->statuses)
            ->filter(fn (mixed $value): bool => is_string($value))
            ->intersect(array_keys(Rental::STATUSES))
            ->values()
            ->all();
    }

    /**
     * The valid payment statuses picked; anything else is dropped.
     *
     * @return list<PaymentStatus>
     */
    private function selectedPaymentStatuses(): array
    {
        return collect($this->paymentStatuses)
            ->filter(fn (mixed $value): bool => is_string($value))
            ->map(fn (string $value): ?PaymentStatus => PaymentStatus::tryFrom($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
