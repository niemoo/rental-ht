<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\SortsTable;
use App\Support\Reports\ReceivablesReport;
use App\Support\Reports\Reports;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Piutang report: what customers still owe as of today, grouped by age, plus overpayments to refund.
 * It has no period: the period values in the URL are only passed along to the other report tabs.
 */
#[Title('Laporan piutang')]
class Receivables extends Component
{
    use SortsTable;

    /** @var list<string> Keys of {@see ReceivablesReport::AGINGS}. */
    #[Url(as: 'aging', except: [])]
    public array $agings = [];

    #[Url(as: 'sort', except: 'balance')]
    public string $sortBy = 'balance';

    #[Url(as: 'dir', except: 'desc')]
    public string $sortDirection = 'desc';

    /** Kept only so switching tabs keeps the period picked on the other reports. */
    #[Url(except: 'month')]
    public string $period = 'month';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'balance' => 'balance',
            'invoice' => 'rentals.invoice_number',
            'customer' => 'customer_name',
            'start' => 'rentals.start_date',
            'due' => 'rentals.due_date',
            'aging' => 'owed_since',
            'total' => 'rentals.total_amount',
            'paid' => 'rentals.paid_amount',
        ];
    }

    public function render(): View
    {
        $report = new ReceivablesReport(Reports::agings($this->agings));
        $figures = $report->figures();

        return view('livewire.reports.receivables', [
            'report' => $report,
            'figures' => $figures,
            'rentals' => $this->paginateSorted($report->tableQuery()),
            'overpaid' => $report->overpaidQuery()->get(),
            'agingFilter' => [
                'options' => ReceivablesReport::AGINGS,
                'counts' => collect($figures['by_aging'])->map(fn (array $group): int => $group['count'])->all(),
            ],
        ]);
    }
}
