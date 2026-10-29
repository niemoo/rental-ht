<?php

namespace App\Support;

use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Read-only aggregates behind the dashboard tiles and charts.
 */
class DashboardMetrics
{
    private CarbonImmutable $now;

    public function __construct(?CarbonInterface $now = null)
    {
        $this->now = CarbonImmutable::instance($now ?? now());
    }

    /**
     * Every unit falls in exactly one of available, on_rent, overdue and out_of_service
     * (on the shelf but under repair, damaged or lost).
     *
     * @return array{total: int, out_now: int, overdue: int, available: int, on_rent: int, out_of_service: int}
     */
    public function fleet(): array
    {
        $total = Unit::query()->count();
        $outNow = Unit::query()->outNow($this->now)->count();
        $overdue = Unit::query()->overdue($this->now)->count();
        $outOfService = Unit::query()->outOfService($this->now)->count();

        return [
            'total' => $total,
            'out_now' => $outNow,
            'overdue' => $overdue,
            'available' => $total - $outNow - $outOfService,
            'on_rent' => $outNow - $overdue,
            'out_of_service' => $outOfService,
        ];
    }

    /**
     * Rentals starting in this calendar month, with the change versus last month (null when last month had none).
     * Bookings that start next month or later don't count yet, so this matches this month's bar in rentalsPerMonth().
     *
     * @return array{count: int, change_pct: int|null}
     */
    public function rentalsThisMonth(): array
    {
        $startOfThisMonth = $this->now->startOfMonth();
        $startOfLastMonth = $startOfThisMonth->subMonthNoOverflow();

        $thisMonth = Rental::query()->notCancelled()->whereBetween('start_date', [$startOfThisMonth, $startOfThisMonth->endOfMonth()])->count();
        $lastMonth = Rental::query()->notCancelled()->whereBetween('start_date', [$startOfLastMonth, $startOfThisMonth->subSecond()])->count();

        return [
            'count' => $thisMonth,
            'change_pct' => $lastMonth === 0 ? null : (int) round(($thisMonth - $lastMonth) / $lastMonth * 100),
        ];
    }

    /**
     * Bar chart: rentals per month by start date, last 12 months.
     *
     * @return array{categories: list<string>, data: list<int>}
     */
    public function rentalsPerMonth(): array
    {
        $months = $this->lastTwelveMonths();

        $counts = Rental::query()
            ->notCancelled()
            ->where('start_date', '>=', $months->first())
            ->pluck('start_date')
            ->countBy(fn (CarbonInterface $date): string => $date->format('Y-m'));

        return [
            'categories' => $this->monthLabels($months),
            'data' => $months->map(fn (CarbonImmutable $month): int => $counts->get($month->format('Y-m'), 0))->all(),
        ];
    }

    /**
     * Area chart: units going out (by rental start) vs units coming back (by return date), weekly
     * (Monday to Sunday) over the last four weeks including the current one.
     *
     * @return array{categories: list<string>, out: list<int>, returned: list<int>}
     */
    public function rentalActivity(): array
    {
        $weeks = $this->lastFourWeeks();
        $since = $weeks->first();

        // Cancelled rentals are left out: their units never went out, even though cancelling closed them.
        $items = RentalItem::query()
            ->whereHas('rental', fn (Builder $rental) => $rental->notCancelled())
            ->where(fn (Builder $query) => $query
                ->whereHas('rental', fn (Builder $rental) => $rental->where('start_date', '>=', $since))
                ->orWhere('returned_at', '>=', $since))
            ->with('rental:id,start_date')
            ->get(['id', 'rental_id', 'returned_at']);

        $out = $items
            ->filter(fn (RentalItem $item): bool => $item->rental->start_date->gte($since))
            ->countBy(fn (RentalItem $item): string => $this->weekKey($item->rental->start_date));

        $returned = $items
            ->filter(fn (RentalItem $item): bool => $item->returned_at?->gte($since) ?? false)
            ->countBy(fn (RentalItem $item): string => $this->weekKey($item->returned_at));

        return [
            'categories' => $this->weekLabels($weeks),
            'out' => $weeks->map(fn (CarbonImmutable $week): int => $out->get($this->weekKey($week), 0))->all(),
            'returned' => $weeks->map(fn (CarbonImmutable $week): int => $returned->get($this->weekKey($week), 0))->all(),
        ];
    }

    /**
     * Mondays of the current week and the three weeks before it.
     *
     * @return Collection<int, CarbonImmutable>
     */
    private function lastFourWeeks(): Collection
    {
        $first = $this->now->startOfWeek(CarbonInterface::MONDAY)->subWeeks(3);

        return collect(range(0, 3))->map(fn (int $offset): CarbonImmutable => $first->addWeeks($offset));
    }

    private function weekKey(CarbonInterface $date): string
    {
        return CarbonImmutable::instance($date)->startOfWeek(CarbonInterface::MONDAY)->format('Y-m-d');
    }

    /**
     * Labels such as "19–25 Okt", or "29 Sep–5 Okt" when the week spans two months.
     *
     * @param  Collection<int, CarbonImmutable>  $weeks
     * @return list<string>
     */
    private function weekLabels(Collection $weeks): array
    {
        return $weeks->map(function (CarbonImmutable $monday): string {
            $sunday = $monday->addDays(6);

            return $monday->isSameMonth($sunday)
                ? $monday->format('j').'–'.$sunday->translatedFormat('j M')
                : $monday->translatedFormat('j M').'–'.$sunday->translatedFormat('j M');
        })->all();
    }

    /**
     * @return Collection<int, CarbonImmutable>
     */
    private function lastTwelveMonths(): Collection
    {
        $first = $this->now->startOfMonth()->subMonthsNoOverflow(11);

        return collect(range(0, 11))->map(fn (int $offset): CarbonImmutable => $first->addMonthsNoOverflow($offset));
    }

    /**
     * @param  Collection<int, CarbonImmutable>  $months
     * @return list<string>
     */
    private function monthLabels(Collection $months): array
    {
        return $months->map(fn (CarbonImmutable $month): string => $month->translatedFormat('M'))->all();
    }
}
