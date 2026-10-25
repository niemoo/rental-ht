<?php

namespace App\Livewire\Concerns;

use App\Support\ReportPeriod;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

/**
 * The period picker on the report pages: a preset or a custom from–to range, kept in the URL so the
 * period carries over between report tabs, the print view and the downloads.
 */
trait FiltersReportPeriod
{
    /** A key of {@see ReportPeriod::PRESETS}. */
    #[Url(except: 'month')]
    public string $period = 'month';

    /** Y-m-d; only used with the custom period. */
    #[Url(except: '')]
    public string $from = '';

    /** Y-m-d; only used with the custom period. */
    #[Url(except: '')]
    public string $to = '';

    /**
     * Switching to a custom range with no dates yet starts it at this month.
     */
    public function updatedPeriod(string $period): void
    {
        if ($period === 'custom' && (blank($this->from) || blank($this->to))) {
            $current = ReportPeriod::fromInput('month');
            $this->from = $current->from->toDateString();
            $this->to = $current->to->toDateString();
        }

        unset($this->reportPeriod);
    }

    #[Computed]
    public function reportPeriod(): ReportPeriod
    {
        return ReportPeriod::fromInput($this->period, $this->from, $this->to);
    }
}
