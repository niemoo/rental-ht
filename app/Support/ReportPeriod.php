<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * The date range a report covers: a preset (today, this week, …) or a custom from–to range.
 * Both ends are inclusive whole days. Built from the same query values the report pages keep in their URL
 * (period, from, to), so the page, its print view and its exports always cover the same days.
 */
final readonly class ReportPeriod
{
    public const PRESETS = [
        'today' => 'Hari ini',
        'week' => 'Minggu ini',
        'month' => 'Bulan ini',
        'last_month' => 'Bulan lalu',
        'year' => 'Tahun ini',
        'custom' => 'Rentang tanggal',
    ];

    private function __construct(
        public string $preset,
        public CarbonImmutable $from,
        public CarbonImmutable $to,
    ) {}

    /**
     * Unknown presets fall back to this month; a custom range with a missing or invalid date does too,
     * and one given backwards is swapped.
     */
    public static function fromInput(?string $preset, ?string $from = null, ?string $to = null, ?CarbonInterface $now = null): self
    {
        $today = CarbonImmutable::instance($now ?? now())->startOfDay();

        if ($preset === 'custom') {
            $start = self::parseDate($from);
            $end = self::parseDate($to);

            if ($start && $end) {
                [$start, $end] = $start->lte($end) ? [$start, $end] : [$end, $start];

                return new self('custom', $start, $end->endOfDay());
            }

            $preset = 'month';
        }

        return match ($preset) {
            'today' => new self('today', $today, $today->endOfDay()),
            'week' => new self('week', $today->startOfWeek(CarbonInterface::MONDAY), $today->endOfWeek(CarbonInterface::SUNDAY)),
            'last_month' => new self('last_month', $today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()),
            'year' => new self('year', $today->startOfYear(), $today->endOfYear()),
            default => new self('month', $today->startOfMonth(), $today->endOfMonth()),
        };
    }

    /**
     * e.g. "1 Okt – 31 Okt 2026", or "25 Okt 2026" for a single day.
     */
    public function label(): string
    {
        if ($this->from->isSameDay($this->to)) {
            return $this->from->translatedFormat('j M Y');
        }

        return $this->from->translatedFormat($this->from->isSameYear($this->to) ? 'j M' : 'j M Y').' – '.$this->to->translatedFormat('j M Y');
    }

    /**
     * Charts group by day for ranges up to 31 days, by month for longer ones.
     */
    public function groupsByDay(): bool
    {
        return $this->from->diffInDays($this->to) < 31;
    }

    /**
     * The chart buckets over the range, as [key => label]; keys match {@see self::bucketKey()}.
     *
     * @return Collection<string, string>
     */
    public function buckets(): Collection
    {
        $buckets = collect();
        $cursor = $this->groupsByDay() ? $this->from : $this->from->startOfMonth();

        while ($cursor->lte($this->to)) {
            $buckets[$this->bucketKey($cursor)] = $cursor->translatedFormat($this->groupsByDay() ? 'j M' : 'M Y');
            $cursor = $this->groupsByDay() ? $cursor->addDay() : $cursor->addMonthNoOverflow();
        }

        return $buckets;
    }

    public function bucketKey(CarbonInterface $date): string
    {
        return $date->format($this->groupsByDay() ? 'Y-m-d' : 'Y-m');
    }

    /**
     * The values to put back in a URL to reach this same period.
     *
     * @return array{period: string, from?: string, to?: string}
     */
    public function query(): array
    {
        return $this->preset === 'custom'
            ? ['period' => 'custom', 'from' => $this->from->toDateString(), 'to' => $this->to->toDateString()]
            : ['period' => $this->preset];
    }

    private static function parseDate(?string $value): ?CarbonImmutable
    {
        if (blank($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
