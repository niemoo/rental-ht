<?php

use App\Actions\CancelRental;
use App\Enums\ChargeType;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Livewire\Charges;
use App\Livewire\Payments;
use App\Livewire\Rentals;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Rental;
use App\Models\RentalCharge;
use App\Models\RentalPayment;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

/**
 * A rental of $count units at $rate a day over $days days, starting $startOffset days from today.
 */
function rentalAt(int $rate, int $count = 1, int $days = 3, int $startOffset = 2): Rental
{
    $units = Unit::factory()->count($count)->for(Category::factory()->create(['daily_rate' => $rate]))->create();

    return Rental::factory()->between($startOffset, $startOffset + $days - 1)->withUnits($units)->create()->refresh();
}

test('invoice numbers run from 1 again each month', function () {
    $this->travelTo('2026-10-30');
    $first = Rental::factory()->create();
    $second = Rental::factory()->create();

    $this->travelTo('2026-11-02');
    $november = Rental::factory()->create();

    expect($first->invoice_number)->toBe('INV/2026/10/0001')
        ->and($second->invoice_number)->toBe('INV/2026/10/0002')
        ->and($november->invoice_number)->toBe('INV/2026/11/0001');
});

test('booking copies each unit\'s category rate, so a later price change leaves the bill alone', function () {
    $category = Category::factory()->create(['daily_rate' => 25000]);
    $units = Unit::factory()->count(2)->for($category)->create();
    $customer = Customer::factory()->create();

    Livewire::test(Rentals\Form::class)
        ->set('customerId', $customer->id)
        ->set('startDate', today()->addDay()->toDateString())
        ->set('dueDate', today()->addDays(3)->toDateString())
        ->set('unitIds', $units->modelKeys())
        ->call('save')
        ->assertHasNoErrors();

    $rental = Rental::query()->latest('id')->firstOrFail();
    $category->update(['daily_rate' => 40000]);

    expect($rental->items()->pluck('daily_rate')->all())->toBe([25000, 25000])
        ->and($rental->bill())
        ->days->toBe(3)
        ->subtotal->toBe(150000)
        ->total->toBe(150000)
        ->and($rental->total_amount)->toBe(150000);
});

test('a discount can be given in rupiah or percent and is shown in the estimate', function () {
    $rental = rentalAt(rate: 50000, count: 2, days: 2); // 200.000

    $form = Livewire::test(Rentals\Form::class, ['rental' => $rental])
        ->set('discountType', 'percent')
        ->set('discountValue', '10')
        ->assertSet('estimate.discount', 20000)
        ->assertSee('Rp180.000')
        ->set('discountNote', 'Langganan')
        ->call('save')
        ->assertHasNoErrors();

    expect($rental->fresh())->total_amount->toBe(180000)->discount_note->toBe('Langganan');

    $form = Livewire::test(Rentals\Form::class, ['rental' => $rental->fresh()])
        ->assertSet('discountType', 'percent')
        ->set('discountType', 'amount')
        ->set('discountValue', '500000')
        ->call('save')
        ->assertHasNoErrors();

    expect($rental->fresh()->bill())->discount->toBe(200000)->total->toBe(0);
});

test('a percent discount above 100 is rejected', function () {
    Livewire::test(Rentals\Form::class, ['rental' => rentalAt(rate: 50000)])
        ->set('discountType', 'percent')
        ->set('discountValue', '150')
        ->call('save')
        ->assertHasErrors(['discountValue' => 'max']);
});

test('without the discount permission the discount is hidden and kept as it was', function () {
    $rental = rentalAt(rate: 50000);
    $rental->update(['discount_type' => 'amount', 'discount_value' => 10000]);
    $this->user->roles->first()->revokePermissionTo(Permission::GiveDiscounts->value);

    Livewire::test(Rentals\Form::class, ['rental' => $rental->fresh()])
        ->assertDontSee('Diskon (opsional)')
        ->set('discountValue', '0')
        ->set('eventName', 'Bazar')
        ->call('save')
        ->assertHasNoErrors();

    expect($rental->fresh())->discount_value->toBe(10000)->event_name->toBe('Bazar');
});

test('payment status follows the payments recorded', function () {
    $rental = rentalAt(rate: 100000, days: 3); // 300.000
    $status = fn (): PaymentStatus => $rental->fresh()->bill()->status();

    expect($status())->toBe(PaymentStatus::Unpaid);

    Livewire::test(Payments\Form::class, ['rental' => $rental])
        ->assertSet('amount', '300000')
        ->set('amount', '100000')
        ->set('method', 'transfer')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('rentals.show', $rental));

    expect($status())->toBe(PaymentStatus::Partial)
        ->and($rental->fresh()->paid_amount)->toBe(100000);

    Livewire::test(Payments\Form::class, ['rental' => $rental->fresh()])->assertSet('amount', '200000')->set('method', 'qris')->call('save');
    expect($status())->toBe(PaymentStatus::Paid);

    Livewire::test(Payments\Form::class, ['rental' => $rental->fresh()])->set('amount', '50000')->call('save');
    expect($status())->toBe(PaymentStatus::Overpaid);
});

test('an overpayment can be refunded, but not more than was overpaid', function () {
    $rental = rentalAt(rate: 100000, days: 1);
    RentalPayment::factory()->for($rental)->create(['amount' => 150000]);
    $rental->syncTotals();

    Livewire::test(Payments\Form::class, ['rental' => $rental])
        ->assertSet('kind', 'refund')
        ->assertSet('amount', '50000')
        ->set('amount', '60000')
        ->call('save')
        ->assertHasErrors('amount')
        ->set('amount', '50000')
        ->call('save')
        ->assertHasNoErrors();

    expect($rental->payments()->latest('id')->first()->amount)->toBe(-50000)
        ->and($rental->fresh()->bill()->status())->toBe(PaymentStatus::Paid);
});

test('a payment can be deleted only with the permission, and the deletion is recorded', function () {
    $rental = rentalAt(rate: 100000, days: 1);
    $payment = RentalPayment::factory()->for($rental)->create(['amount' => 100000]);
    $rental->syncTotals();

    Livewire::test(Rentals\Show::class, ['rental' => $rental])->call('deletePayment', $payment->id)->assertForbidden();

    $owner = User::factory()->owner()->create();
    $this->actingAs($owner);

    Livewire::test(Rentals\Show::class, ['rental' => $rental])
        ->call('deletePayment', $payment->id)
        ->assertSet('notice', 'Pembayaran Rp100.000 dihapus.');

    expect(RentalPayment::withTrashed()->find($payment->id)->deleted_by)->toBe($owner->id)
        ->and($rental->fresh()->paid_amount)->toBe(0);
});

test('extra charges add to the total, and a late fee is suggested from the days late', function () {
    $units = Unit::factory()->count(2)->for(Category::factory()->create(['daily_rate' => 20000]))->create();
    $rental = Rental::factory()->between(-6, -3)->withUnits($units)->create(); // 4 days, 3 days late

    Livewire::test(Charges\Form::class, ['rental' => $rental])
        ->assertSet('type', ChargeType::LateFee->value)
        ->assertSet('amount', '120000') // 2 units × 20.000 × 3 days
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(Charges\Form::class, ['rental' => $rental])
        ->set('type', ChargeType::Damage->value)
        ->set('amount', '75000')
        ->set('description', 'Antena patah')
        ->call('save');

    expect($rental->fresh()->bill())
        ->subtotal->toBe(160000)
        ->charges->toBe(195000)
        ->total->toBe(355000);

    Livewire::test(Rentals\Show::class, ['rental' => $rental])
        ->call('deleteCharge', RentalCharge::query()->where('type', 'damage')->value('id'));

    expect($rental->fresh()->total_amount)->toBe(280000);
});

test('an upcoming rental can be cancelled: its units free up and only the fee is owed', function () {
    $owner = User::factory()->owner()->create();
    $this->actingAs($owner);
    $rental = rentalAt(rate: 100000, days: 2, startOffset: 3);
    $unit = $rental->items()->first()->unit;
    RentalPayment::factory()->for($rental)->create(['amount' => 150000]);
    $rental->syncTotals();

    Livewire::test(Rentals\Cancel::class, ['rental' => $rental])
        ->set('reason', 'Acara diundur')
        ->set('fee', '50000')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('rentals.show', $rental));

    $rental->refresh();

    expect($rental->isCancelled())->toBeTrue()
        ->and($rental->cancelled_by)->toBe($owner->id)
        ->and($rental->timing()->pill)->toBe('Dibatalkan')
        ->and($rental->bill())->total->toBe(50000)
        ->and($rental->bill()->status())->toBe(PaymentStatus::Overpaid)
        ->and(Unit::query()->availableBetween(now()->addDays(3), now()->addDays(4))->whereKey($unit->id)->exists())->toBeTrue();

    Livewire::test(Rentals\Index::class)
        ->set('statuses', ['cancelled'])
        ->assertSee($rental->customer->name);
});

test('a rental that has started cannot be cancelled', function () {
    $this->actingAs(User::factory()->owner()->create());
    $rental = Rental::factory()->withUnits()->create();

    Livewire::test(Rentals\Cancel::class, ['rental' => $rental])->assertRedirect(route('rentals.show', $rental));

    expect(fn () => app(CancelRental::class)->handle(auth()->user(), $rental, ['reason' => 'x', 'fee' => 0]))
        ->toThrow(ValidationException::class);
});

test('history can be filtered by payment status', function () {
    $unpaid = rentalAt(rate: 100000);
    $paid = rentalAt(rate: 100000, days: 1);
    RentalPayment::factory()->for($paid)->create(['amount' => 100000]);
    $paid->syncTotals();

    $names = fn ($component): array => $component->viewData('rentals')->pluck('id')->all();
    $component = Livewire::test(Rentals\Index::class);

    expect($component->viewData('paymentFilter')['counts'])->toBe(['unpaid' => 1, 'partial' => 0, 'paid' => 1, 'overpaid' => 0])
        ->and($names($component->set('paymentStatuses', ['paid'])))->toBe([$paid->id])
        ->and($names($component->set('paymentStatuses', ['unpaid'])))->toBe([$unpaid->id]);
});

test('the invoice shows the lines, discount, charges and what is left to pay', function () {
    $rental = rentalAt(rate: 50000, count: 2, days: 2); // 200.000
    $rental->update(['discount_type' => 'percent', 'discount_value' => 10]);
    RentalCharge::factory()->for($rental)->create(['type' => ChargeType::Delivery, 'amount' => 30000]);
    RentalPayment::factory()->for($rental)->create(['amount' => 100000]);
    $rental->syncTotals();

    $this->get(route('rentals.invoice', $rental))
        ->assertOk()
        ->assertSee($rental->invoice_number)
        ->assertSee('Rp200.000')
        ->assertSee('Diskon 10%')
        ->assertSee('Ongkos antar')
        ->assertSee('Rp210.000')
        ->assertSee('Rp110.000');
});
