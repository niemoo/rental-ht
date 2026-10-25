<?php

namespace App\Livewire\Reports;

use App\Enums\PaymentMethod;
use App\Livewire\Concerns\FiltersReportPeriod;
use App\Livewire\Concerns\SortsTable;
use App\Support\Reports\Reports;
use App\Support\Reports\RevenueReport;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Pendapatan report: omzet next to the money actually received, by payment method, plus every payment.
 */
#[Title('Laporan pendapatan')]
class Revenue extends Component
{
    use FiltersReportPeriod, SortsTable;

    /** @var list<string> PaymentMethod values. */
    #[Url(as: 'method', except: [])]
    public array $methods = [];

    #[Url(as: 'sort', except: 'date')]
    public string $sortBy = 'date';

    #[Url(as: 'dir', except: 'desc')]
    public string $sortDirection = 'desc';

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'date' => 'rental_payments.paid_at',
            'invoice' => 'rentals.invoice_number',
            'customer' => 'customer_name',
            'method' => 'rental_payments.method',
            'amount' => 'rental_payments.amount',
        ];
    }

    public function render(): View
    {
        $report = new RevenueReport($this->reportPeriod, Reports::methods($this->methods));
        $unfiltered = new RevenueReport($this->reportPeriod);

        return view('livewire.reports.revenue', [
            'report' => $report,
            'figures' => $report->figures(),
            'byMethod' => $report->byMethod(),
            'chart' => $report->chart(),
            'payments' => $this->paginateSorted($report->tableQuery()),
            'methodFilter' => [
                'options' => PaymentMethod::options(),
                'counts' => $unfiltered->payments()->toBase()->select('rental_payments.method')->selectRaw('COUNT(*) as aggregate')
                    ->groupBy('rental_payments.method')->pluck('aggregate', 'method')->all(),
            ],
        ]);
    }
}
