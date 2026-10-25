<?php

use App\Actions\CancelRental;
use App\Enums\ChargeType;
use App\Enums\PaymentMethod;
use App\Livewire\Reports;
use App\Models\Category;
use App\Models\Rental;
use App\Models\RentalCharge;
use App\Models\RentalPayment;
use App\Models\Unit;
use App\Models\User;
use App\Support\ReportPeriod;
use App\Support\Reports\ReceivablesReport;
use App\Support\Reports\RentalsReport;
use App\Support\Reports\RevenueReport;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo('2026-10-15 10:00');
    $this->owner = User::factory()->owner()->create();
    $this->actingAs($this->owner);
});

/**
 * A rental of one unit at $rate a day between the given day offsets from today, synced totals.
 */
function reportRental(int $rate, int $startOffset, int $dueOffset, bool $returned = false): Rental
{
    $unit = Unit::factory()->for(Category::factory()->create(['daily_rate' => $rate]))->create();
    $factory = Rental::factory()->between($startOffset, $dueOffset)->withUnits($unit);

    return ($returned ? $factory->returned() : $factory)->create()->refresh();
}

function pay(Rental $rental, int $amount, string $date, PaymentMethod $method = PaymentMethod::Cash): void
{
    RentalPayment::factory()->for($rental)->create(['amount' => $amount, 'paid_at' => $date, 'method' => $method]);
    $rental->syncTotals();
}

test('periods cover whole days, and a bad custom range falls back to this month', function () {
    expect(ReportPeriod::fromInput('week'))
        ->from->toDateTimeString()->toBe('2026-10-12 00:00:00')
        ->to->toDateTimeString()->toBe('2026-10-18 23:59:59')
        ->and(ReportPeriod::fromInput('last_month')->label())->toBe('1 Sep – 30 Sep 2026')
        ->and(ReportPeriod::fromInput('custom', '2026-10-20', '2026-10-01')->label())->toBe('1 Okt – 20 Okt 2026')
        ->and(ReportPeriod::fromInput('custom', 'nonsense', '2026-10-01')->preset)->toBe('month')
        ->and(ReportPeriod::fromInput('year')->groupsByDay())->toBeFalse()
        ->and(ReportPeriod::fromInput('month')->buckets())->toHaveCount(31);
});

test('revenue shows omzet next to money received, by payment date', function () {
    // Starts in October, worth 300.000; DP in September, rest in October.
    $october = reportRental(100000, 1, 3);
    pay($october, 100000, '2026-09-28', PaymentMethod::Transfer);
    pay($october, 200000, '2026-10-16', PaymentMethod::Qris);

    // Starts in November: no omzet in October, but its DP counts as money in.
    $november = reportRental(50000, 20, 21);
    pay($november, 40000, '2026-10-10');

    $report = new RevenueReport(ReportPeriod::fromInput('month'));

    expect($report->figures())->toBe(['omzet' => 300000, 'unpaid' => 0, 'received' => 240000, 'refunded' => 0, 'net' => 240000])
        ->and($report->byMethod())->toBe(['cash' => 40000, 'transfer' => 0, 'qris' => 200000])
        ->and((new RevenueReport(ReportPeriod::fromInput('month'), [PaymentMethod::Cash]))->figures()['received'])->toBe(40000);
});

test('refunds count against money received, and a cancelled rental only adds its fee to omzet', function () {
    $rental = reportRental(100000, 5, 6); // 200.000
    pay($rental, 150000, '2026-10-01');
    app(CancelRental::class)->handle($this->owner, $rental, ['reason' => 'Batal', 'fee' => 50000]);
    pay($rental->fresh(), -100000, '2026-10-02');

    $figures = (new RevenueReport(ReportPeriod::fromInput('month')))->figures();

    expect($figures)->omzet->toBe(50000)
        ->received->toBe(150000)
        ->refunded->toBe(100000)
        ->net->toBe(50000)
        ->unpaid->toBe(0);
});

test('receivables are grouped by how long they have been owed', function () {
    $upcoming = reportRental(10000, 3, 4);
    $running = reportRental(20000, -1, 2);
    $recent = reportRental(30000, -8, -5, returned: true);
    $old = reportRental(40000, -60, -40, returned: true);
    $paid = reportRental(50000, -10, -9, returned: true);
    pay($paid, 100000, '2026-10-01');
    $overpaid = reportRental(10000, -3, -3, returned: true);
    pay($overpaid, 15000, '2026-10-01');

    $report = new ReceivablesReport;
    $figures = $report->figures();

    expect($figures['count'])->toBe(4)
        ->and($figures['total'])->toBe(20000 + 80000 + 120000 + 840000)
        ->and(collect($figures['by_aging'])->map->count->all())->toBe(['upcoming' => 1, 'running' => 1, 'days_0_7' => 1, 'days_8_30' => 0, 'days_over_30' => 1])
        ->and($figures['overpaid_total'])->toBe(5000)
        ->and($report->overpaidQuery()->pluck('id')->all())->toBe([$overpaid->id]);

    $filtered = new ReceivablesReport(['days_over_30']);
    $row = $filtered->tableQuery()->sole();

    expect($row->id)->toBe($old->id)
        ->and($filtered->daysOwed($row))->toBe(40);
});

test('the rentals report counts cancellations, averages, discounts and charges', function () {
    $kept = reportRental(100000, 1, 2); // 200.000
    $kept->update(['discount_type' => 'percent', 'discount_value' => 10]);
    $kept->syncTotals(); // 180.000
    RentalCharge::factory()->for($kept)->create(['type' => ChargeType::Delivery, 'amount' => 20000]);
    $kept->syncTotals(); // 200.000

    $other = reportRental(50000, -4, -1, returned: true); // 4 days, 200.000
    $cancelled = reportRental(70000, 5, 5);
    app(CancelRental::class)->handle($this->owner, $cancelled, ['reason' => 'Batal', 'fee' => 0]);
    reportRental(10000, 40, 41); // November, left out

    $report = new RentalsReport(ReportPeriod::fromInput('month'));
    $figures = $report->figures();

    expect($figures)->count->toBe(3)
        ->cancelled->toBe(1)
        ->cancelled_pct->toBe(33)
        ->omzet->toBe(400000)
        ->average_value->toBe(200000)
        ->average_days->toBe(3.0)
        ->discount->toBe(20000)
        ->and($figures['charges']['delivery'])->toBe(20000)
        ->and((new RentalsReport(ReportPeriod::fromInput('month'), ['cancelled']))->tableQuery()->pluck('rentals.id')->all())->toBe([$cancelled->id]);
});

test('the report pages filter and keep the period in the URL', function () {
    $rental = reportRental(100000, 1, 1);
    pay($rental, 100000, '2026-10-14', PaymentMethod::Transfer);
    pay(reportRental(30000, 2, 2), 30000, '2026-10-14');

    Livewire::withQueryParams(['period' => 'week'])
        ->test(Reports\Revenue::class)
        ->assertSee('Minggu ini')
        ->assertSee($rental->invoice_number)
        ->set('methods', ['cash'])
        ->assertDontSee($rental->invoice_number)
        ->assertSee(route('reports.download', ['report' => 'revenue', 'format' => 'csv', 'period' => 'week', 'method' => ['cash']]));

    Livewire::test(Reports\Receivables::class)->assertOk()->assertSee('Umur piutang');
    Livewire::test(Reports\Rentals::class)->set('period', 'custom')->assertSet('from', '2026-10-01')->assertSet('to', '2026-10-31')->assertOk();
});

test('reports print and download as CSV and Excel', function () {
    $rental = reportRental(100000, 1, 1);
    pay($rental, 60000, '2026-10-14');

    $this->get(route('reports.print', ['report' => 'revenue']))->assertOk()->assertSee('Laporan Pendapatan')->assertSee('Rp60.000');

    $csv = $this->get(route('reports.download', ['report' => 'rentals', 'format' => 'csv']));
    $csv->assertOk()->assertDownload('laporan-penyewaan-2026-10-01-2026-10-31.csv');
    expect($csv->streamedContent())->toContain('"No. invoice";Pelanggan')->toContain($rental->invoice_number.';');

    $xlsx = $this->get(route('reports.download', ['report' => 'receivables', 'format' => 'xlsx']));
    $xlsx->assertOk()->assertDownload('laporan-piutang-2026-10-15.xlsx');
    expect(substr($xlsx->streamedContent(), 0, 2))->toBe('PK');
});

test('staff cannot see reports by default', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('reports.revenue'))->assertForbidden();
    $this->get(route('reports.download', ['report' => 'revenue', 'format' => 'csv']))->assertForbidden();
    $this->get(route('dashboard'))->assertDontSee(route('reports.revenue'));
});
