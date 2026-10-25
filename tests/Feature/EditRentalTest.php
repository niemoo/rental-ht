<?php

use App\Enums\Permission;
use App\Enums\UnitCondition;
use App\Livewire\Rentals\Form;
use App\Models\Customer;
use App\Models\Rental;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('the edit form starts with the rental as it is', function () {
    $units = Unit::factory()->count(2)->create();
    $rental = Rental::factory()->between(2, 4)->withUnits($units)->create(['event_name' => 'Pernikahan']);

    Livewire::test(Form::class, ['rental' => $rental])
        ->assertSet('customerId', $rental->customer_id)
        ->assertSet('startDate', $rental->start_date->toDateString())
        ->assertSet('dueDate', $rental->due_date->toDateString())
        ->assertSet('unitIds', $units->modelKeys())
        ->assertSet('eventName', 'Pernikahan')
        ->assertSee('Simpan perubahan');
});

test('a rental can be changed to another customer, dates, units and event', function () {
    $kept = Unit::factory()->create();
    $dropped = Unit::factory()->create();
    $added = Unit::factory()->create();
    $rental = Rental::factory()->between(2, 4)->withUnits([$kept, $dropped])->create();
    $customer = Customer::factory()->create();

    Livewire::test(Form::class, ['rental' => $rental])
        ->set('customerId', $customer->id)
        ->set('startDate', today()->addDays(5)->toDateString())
        ->set('dueDate', today()->addDays(7)->toDateString())
        ->set('unitIds', [$kept->id, $added->id])
        ->set('eventName', 'Festival')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('rentals.show', $rental));

    $rental->refresh();

    expect($rental->customer_id)->toBe($customer->id)
        ->and($rental->start_date->toDateString())->toBe(today()->addDays(5)->toDateString())
        ->and($rental->due_date->toDateString())->toBe(today()->addDays(7)->toDateString())
        ->and($rental->event_name)->toBe('Festival')
        ->and($rental->items()->pluck('unit_id')->sort()->values()->all())->toBe([$kept->id, $added->id]);
});

test('a unit booked by another rental for the new dates cannot be added', function () {
    $taken = Unit::factory()->create(['code' => 'SPK-01']);
    Rental::factory()->between(5, 7)->withUnits($taken)->create();
    $rental = Rental::factory()->between(1, 2)->withUnits()->create();

    Livewire::test(Form::class, ['rental' => $rental])
        ->set('startDate', today()->addDays(6)->toDateString())
        ->set('dueDate', today()->addDays(8)->toDateString())
        ->assertDontSee('SPK-01')
        ->set('unitIds', [...$rental->items()->pluck('unit_id')->all(), $taken->id])
        ->call('save')
        ->assertHasErrors('unit_ids');

    expect($rental->items()->count())->toBe(1);
});

test('a booked unit whose condition changed can stay on the rental', function () {
    $unit = Unit::factory()->create();
    $rental = Rental::factory()->between(-1, 2)->withUnits($unit)->create();
    $unit->update(['condition' => UnitCondition::UnderRepair]);

    Livewire::test(Form::class, ['rental' => $rental])
        ->set('eventName', 'Bazar')
        ->call('save')
        ->assertHasNoErrors();

    expect($rental->fresh()->event_name)->toBe('Bazar')
        ->and($rental->items()->pluck('unit_id')->all())->toBe([$unit->id]);
});

test('returned units stay on the rental and are not required to be ticked', function () {
    $returned = Unit::factory()->create(['code' => 'MIC-01']);
    $open = Unit::factory()->create();
    $rental = Rental::factory()->between(-2, 2)->withUnits([$returned, $open])->create();
    $rental->items()->where('unit_id', $returned->id)->update(['returned_at' => now()]);

    Livewire::test(Form::class, ['rental' => $rental])
        ->assertSet('unitIds', [$open->id])
        ->assertSee('MIC-01')
        ->set('unitIds', [])
        ->call('save')
        ->assertHasNoErrors();

    expect($rental->items()->pluck('unit_id')->all())->toBe([$returned->id])
        ->and($rental->fresh()->isCompleted())->toBeTrue();
});

test('a completed rental cannot be edited', function () {
    $rental = Rental::factory()->between(-5, -2)->withUnits()->returned()->create();

    Livewire::test(Form::class, ['rental' => $rental])->assertRedirect(route('rentals.show', $rental));
});

test('editing needs the edit permission', function () {
    $this->user->roles->first()->revokePermissionTo(Permission::EditRentals->value);
    $rental = Rental::factory()->withUnits()->create();

    $this->get(route('rentals.edit', $rental))->assertForbidden();
    Livewire::test(Form::class, ['rental' => $rental])->call('save')->assertForbidden();
});
