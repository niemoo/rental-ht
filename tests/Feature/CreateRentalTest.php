<?php

use App\Livewire\Rentals\Form;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Rental;
use App\Models\Unit;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function bookingForm(array $overrides = []): Testable
{
    $test = Livewire::test(Form::class);

    foreach ($overrides as $property => $value) {
        $test->set($property, $value);
    }

    return $test;
}

test('a rental can be booked for an existing customer', function () {
    $customer = Customer::factory()->create();
    $units = Unit::factory()->count(2)->create();

    bookingForm([
        'customerId' => $customer->id,
        'startDate' => today()->addDay()->toDateString(),
        'dueDate' => today()->addDays(3)->toDateString(),
        'unitIds' => $units->modelKeys(),
        'eventName' => 'Festival Musik',
        'eventAddress' => '  ',
    ])->call('save')->assertHasNoErrors();

    $rental = Rental::query()->with('items')->sole();

    expect($rental)
        ->customer_id->toBe($customer->id)
        ->created_by->toBe($this->user->id)
        ->event_name->toBe('Festival Musik')
        ->event_address->toBeNull()
        ->start_date->toDateTimeString()->toBe(today()->addDay()->startOfDay()->toDateTimeString())
        ->due_date->toDateTimeString()->toBe(today()->addDays(3)->endOfDay()->toDateTimeString())
        ->and($rental->items->pluck('unit_id')->sort()->values()->all())->toBe($units->modelKeys());
});

test('booking redirects to the new rental', function () {
    $unit = Unit::factory()->create();

    $component = bookingForm([
        'customerMode' => 'new',
        'customerName' => 'Sinta Dewi',
        'customerPhone' => '0812-2233-8890',
        'startDate' => today()->toDateString(),
        'dueDate' => today()->toDateString(),
        'unitIds' => [$unit->id],
    ])->call('save');

    $component->assertRedirect(route('rentals.show', Rental::query()->sole()));
    expect(Customer::query()->where('name', 'Sinta Dewi')->exists())->toBeTrue();
});

test('only units free for the chosen dates are offered', function () {
    $free = Unit::factory()->create(['code' => 'FREE-1']);
    $overlapping = Unit::factory()->create(['code' => 'BUSY-1']);
    $overdue = Unit::factory()->create(['code' => 'LATE-1']);
    $laterBooking = Unit::factory()->create(['code' => 'LATER-1']);

    Rental::factory()->between(1, 4)->withUnits($overlapping)->create();
    Rental::factory()->between(-10, -2)->withUnits($overdue)->create();
    Rental::factory()->between(20, 25)->withUnits($laterBooking)->create();

    $available = bookingForm([
        'startDate' => today()->addDays(3)->toDateString(),
        'dueDate' => today()->addDays(6)->toDateString(),
    ])->instance()->availableUnits->pluck('code')->all();

    expect($available)->toBe(['FREE-1', 'LATER-1']);
});

test('returned bookings no longer block a unit', function () {
    $unit = Unit::factory()->create();
    Rental::factory()->between(-10, -2)->withUnits($unit)->returned()->create();

    $available = bookingForm([
        'startDate' => today()->toDateString(),
        'dueDate' => today()->addDay()->toDateString(),
    ])->instance()->availableUnits;

    expect($available->modelKeys())->toBe([$unit->id]);
});

test('the server rejects a unit that is already booked for overlapping dates', function () {
    $unit = Unit::factory()->create(['code' => 'A-01']);
    Rental::factory()->between(2, 5)->withUnits($unit)->create();

    bookingForm([
        'customerId' => Customer::factory()->create()->id,
        'startDate' => today()->addDays(4)->toDateString(),
        'dueDate' => today()->addDays(8)->toDateString(),
    ])
        ->set('unitIds', [$unit->id]) // bypass the UI's availability filter
        ->call('save')
        ->assertHasErrors(['unit_ids']);

    expect(Rental::query()->count())->toBe(1);
});

test('selected units are dropped when dates change and they become unavailable', function () {
    $unit = Unit::factory()->create();
    Rental::factory()->between(10, 12)->withUnits($unit)->create();

    bookingForm([
        'startDate' => today()->addDay()->toDateString(),
        'dueDate' => today()->addDays(2)->toDateString(),
        'unitIds' => [$unit->id],
    ])
        ->set('dueDate', today()->addDays(11)->toDateString())
        ->assertSet('unitIds', []);
});

test('the form starts in new-customer mode when there are no customers yet', function () {
    bookingForm()->assertSet('customerMode', 'new');
});

test('booking input is validated', function () {
    Customer::factory()->create();

    bookingForm([
        'startDate' => today()->addDays(3)->toDateString(),
        'dueDate' => today()->toDateString(),
    ])
        ->call('save')
        ->assertHasErrors(['customerId' => 'required_if', 'dueDate' => 'after_or_equal', 'unitIds' => 'required']);

    bookingForm(['customerMode' => 'new'])
        ->call('save')
        ->assertHasErrors(['customerName' => 'required_if', 'customerPhone' => 'required_if', 'startDate' => 'required']);
});

test('the summary reflects the current selections', function () {
    $customer = Customer::factory()->create(['name' => 'Andi Pratama']);
    $units = Unit::factory()->count(3)->create();

    $component = bookingForm([
        'customerId' => $customer->id,
        'startDate' => today()->addDay()->toDateString(),
        'dueDate' => today()->addDays(3)->toDateString(),
        'unitIds' => [$units[0]->id, $units[2]->id],
    ]);

    expect($component->instance()->durationInDays)->toBe(3)
        ->and($component->instance()->customerLabel)->toBe('Andi Pratama')
        ->and($component->instance()->selectedUnits->modelKeys())->toEqualCanonicalizing([$units[0]->id, $units[2]->id]);

    $component->assertSee('Ringkasan')->assertSee('3 hari')->assertSee('Andi Pratama');
});

test('available units are grouped by category', function () {
    $ht = Category::factory()->create(['name' => 'HT']);
    $mic = Category::factory()->create(['name' => 'Mic']);
    Unit::factory()->for($mic)->create(['code' => 'MIC-01']);
    Unit::factory()->for($ht)->count(2)->sequence(['code' => 'A-02'], ['code' => 'A-01'])->create();

    $groups = bookingForm([
        'startDate' => today()->addDay()->toDateString(),
        'dueDate' => today()->addDays(3)->toDateString(),
    ])->assertSeeInOrder(['HT', 'A-01', 'A-02', 'Mic', 'MIC-01'])->instance()->availableUnitsByCategory;

    expect($groups->keys()->all())->toBe(['HT', 'Mic'])
        ->and($groups['HT']->pluck('code')->all())->toBe(['A-01', 'A-02']);
});
