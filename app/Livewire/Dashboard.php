<?php

namespace App\Livewire;

use App\Livewire\Concerns\ReturnsRentals;
use App\Livewire\Concerns\SortsTable;
use App\Models\Customer;
use App\Models\Rental;
use App\Support\DashboardMetrics;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Dasbor')]
class Dashboard extends Component
{
    use ReturnsRentals, SortsTable;

    #[Url(as: 'sort', except: 'due')]
    public string $sortBy = 'due';

    #[Url(as: 'dir', except: 'asc')]
    public string $sortDirection = 'asc';

    /**
     * Sortable columns of the "Sedang disewa" table.
     *
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'due' => 'rentals.due_date',
            'start' => 'rentals.start_date',
            'customer' => 'customer_name',
            'units' => 'open_items_count',
        ];
    }

    protected function perPage(): int
    {
        return 10;
    }

    public function render(): View
    {
        $metrics = new DashboardMetrics;
        $fleet = $metrics->fleet();

        $outNow = $this->paginateSorted(
            Rental::query()
                ->outNow()
                ->select('rentals.*')
                ->addSelect(['customer_name' => Customer::query()->select('name')->whereColumn('customers.id', 'rentals.customer_id')])
                ->with(['customer', 'openItems.unit'])
                ->withCount('openItems')
        );

        $upcoming = Rental::query()
            ->upcoming()
            ->with(['customer', 'openItems.unit'])
            ->orderBy('start_date')
            ->get();

        return view('livewire.dashboard', [
            'fleet' => $fleet,
            'monthStat' => $metrics->rentalsThisMonth(),
            'perMonth' => $metrics->rentalsPerMonth(),
            'activity' => $metrics->rentalActivity(),
            'utilization' => $fleet['total'] > 0 ? (int) round($fleet['out_now'] / $fleet['total'] * 100) : 0,
            'outNow' => $outNow,
            'upcoming' => $upcoming,
            // Overdue means due on an earlier calendar day, matching RentalTiming.
            'overdueCount' => Rental::query()->outNow()->where('due_date', '<', today())->count(),
        ]);
    }
}
