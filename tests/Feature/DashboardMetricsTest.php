<?php

use App\Enums\UnitCondition;
use App\Models\Rental;
use App\Models\Unit;
use App\Support\DashboardMetrics;

test('fleet status partitions units into available, on rent, overdue and out of service', function () {
    Unit::factory()->count(5)->create();
    Unit::factory()->condition(UnitCondition::UnderRepair)->create();
    Unit::factory()->condition(UnitCondition::Lost)->create();
    Rental::factory()->between(-2, 3)->withUnits(Unit::factory()->count(2)->create())->create();
    Rental::factory()->between(-6, -1)->withUnits()->create(); // overdue
    Rental::factory()->between(3, 5)->withUnits()->create(); // upcoming — not out yet

    expect((new DashboardMetrics)->fleet())->toBe([
        'total' => 11,
        'out_now' => 3,
        'overdue' => 1,
        'available' => 6,
        'on_rent' => 2,
        'out_of_service' => 2,
    ]);
});

test('monthly rental stat compares against last month', function () {
    $this->travelTo(now()->setDate(2026, 10, 15));

    Rental::factory()->count(3)->create(['start_date' => '2026-10-02']);
    Rental::factory()->count(2)->create(['start_date' => '2026-09-20']);

    expect((new DashboardMetrics)->rentalsThisMonth())->toBe(['count' => 3, 'change_pct' => 50]);
});

test('rentals per month chart covers the last twelve months', function () {
    $this->travelTo(now()->setDate(2026, 10, 15));

    Rental::factory()->withUnits(Unit::factory()->count(2)->create())->create(['start_date' => '2026-10-02']);
    Rental::factory()->withUnits()->returned()->create(['start_date' => '2026-08-01', 'due_date' => '2026-09-03']);

    $perMonth = (new DashboardMetrics)->rentalsPerMonth();

    expect($perMonth['categories'])->toHaveCount(12)
        ->and($perMonth['categories'][11])->toBe('Okt')
        ->and($perMonth['data'][11])->toBe(1)
        ->and($perMonth['data'][9])->toBe(1);
});

test('rental activity chart covers the last four weeks starting on monday', function () {
    $this->travelTo(now()->setDate(2026, 10, 24)); // Saturday; current week is Mon 19 – Sun 25 Oct

    Rental::factory()->withUnits(Unit::factory()->count(2)->create())->create(['start_date' => '2026-10-19']);
    Rental::factory()->withUnits()->returned()->create(['start_date' => '2026-09-20', 'due_date' => '2026-10-04']);
    Rental::factory()->withUnits()->create(['start_date' => '2026-09-27']); // before the window

    $activity = (new DashboardMetrics)->rentalActivity();

    expect($activity['categories'])->toBe(['28 Sep–4 Okt', '5–11 Okt', '12–18 Okt', '19–25 Okt'])
        ->and($activity['out'])->toBe([0, 0, 0, 2])
        ->and($activity['returned'])->toBe([1, 0, 0, 0]);
});
