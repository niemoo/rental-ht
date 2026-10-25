<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\FiltersReportPeriod;
use App\Livewire\Concerns\SortsTable;
use App\Models\Rental;
use App\Support\Reports\RentalsReport;
use App\Support\Reports\Reports;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Penyewaan report: the rentals that started in the period, their billed value, cancellations,
 * discounts and extra charges.
 */
#[Title('Laporan penyewaan')]
class Rentals extends Component
{
    use FiltersReportPeriod, SortsTable;

    /** @var list<string> Keys of {@see Rental::STATUSES}. */
    #[Url(as: 'status', except: [])]
    public array $statuses = [];

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
            'invoice' => 'rentals.invoice_number',
            'customer' => 'customer_name',
            'days' => 'days',
            'units' => 'items_count',
            'status' => 'status_rank',
            'total' => 'rentals.total_amount',
            'balance' => 'balance',
        ];
    }

    public function render(): View
    {
        $report = new RentalsReport($this->reportPeriod, Reports::statuses($this->statuses));
        $now = $report->now;

        return view('livewire.reports.rentals', [
            'report' => $report,
            'figures' => $report->figures(),
            'chart' => $report->chart(),
            'rentals' => $this->paginateSorted($report->tableQuery()),
            'statusFilter' => [
                'options' => Rental::STATUSES,
                'counts' => collect(Rental::STATUSES)->map(fn (string $label, string $status): int => Rental::query()
                    ->whereBetween('start_date', [$this->reportPeriod->from, $this->reportPeriod->to])
                    ->inStatus($status, $now)
                    ->count())->all(),
            ],
        ]);
    }
}
