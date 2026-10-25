<?php

use App\Livewire\Customers\Form;
use App\Livewire\Customers\Index;
use App\Livewire\Customers\Show;
use App\Models\Customer;
use App\Models\Rental;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the add customer page opens from the customers list', function () {
    $this->get(route('customers.index'))->assertOk()->assertSee('href="'.route('customers.create').'"', false);
    $this->get(route('customers.create'))->assertOk()->assertSee('Tambah pelanggan');
});

test('staff can add a customer and return to the list', function () {
    Livewire::test(Form::class)
        ->set('name', ' Budi Santoso ')
        ->set('phone', '0812-1100-2043')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('customers.index'));

    expect(Customer::query()->where('phone', '0812-1100-2043')->sole()->name)->toBe('Budi Santoso');
});

test('name and phone are required', function () {
    Livewire::test(Form::class)
        ->call('save')
        ->assertHasErrors(['name' => 'required', 'phone' => 'required']);
});

test('active rental and unit counts only include unreturned units', function () {
    $customer = Customer::factory()->create();
    Rental::factory()->for($customer)->withUnits(Unit::factory()->count(2)->create())->create();
    Rental::factory()->for($customer)->between(-10, -5)->withUnits()->returned()->create();

    $row = Livewire::test(Index::class)->viewData('customers')->firstWhere('id', $customer->id);

    expect($row->active_rentals_count)->toBe(1)
        ->and($row->units_out_count)->toBe(2);
});

test('each customer row has a three-dot menu with detail, edit and delete', function () {
    $this->actingAs(User::factory()->owner()->create());
    $customer = Customer::factory()->create(['name' => 'Budi Santoso']);

    $this->get(route('customers.index'))
        ->assertOk()
        ->assertSee('Aksi untuk pelanggan Budi Santoso')
        ->assertSee('href="'.route('customers.show', $customer).'"', false)
        ->assertSee('href="'.route('customers.edit', $customer).'"', false)
        ->assertSee('Hapus pelanggan');
});

test('the detail page shows the customer and every rental they made', function () {
    $customer = Customer::factory()->create(['name' => 'Budi Santoso', 'phone' => '0812-1100-2043']);
    $open = Rental::factory()->for($customer)->withUnits(Unit::factory()->count(2)->create())->create(['event_name' => 'Konser Kampus']);
    $past = Rental::factory()->for($customer)->between(-10, -5)->withUnits()->returned()->create(['event_name' => 'Pernikahan']);
    $otherCustomers = Rental::factory()->withUnits()->create(['event_name' => 'Acara Lain']);

    $this->get(route('customers.show', $customer))
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertSee('0812-1100-2043')
        ->assertSee('Riwayat pemesanan')
        ->assertSee('Konser Kampus')
        ->assertSee('Pernikahan')
        ->assertDontSee('Acara Lain')
        ->assertSee('href="'.route('rentals.show', $open).'"', false);

    $component = Livewire::test(Show::class, ['customer' => $customer]);
    expect($component->viewData('rentals')->pluck('id')->all())->toBe([$open->id, $past->id])
        ->and($component->get('customer'))
        ->rentals_count->toBe(2)
        ->active_rentals_count->toBe(1)
        ->units_out_count->toBe(2);

    $component->call('sort', 'start');
    expect($component->viewData('rentals')->pluck('id')->all())->toBe([$past->id, $open->id]);
});

test('staff can edit a customer and return to their detail page', function () {
    $customer = Customer::factory()->create(['name' => 'Budi', 'phone' => '0811']);

    $this->get(route('customers.edit', $customer))->assertOk()->assertSee('Ubah pelanggan')->assertSee('Budi');

    Livewire::test(Form::class, ['customer' => $customer])
        ->assertSet('name', 'Budi')
        ->set('name', ' Budi Santoso ')
        ->set('phone', '0812-1100-2043')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('customers.show', $customer));

    expect($customer->fresh())
        ->name->toBe('Budi Santoso')
        ->phone->toBe('0812-1100-2043')
        ->and(Customer::count())->toBe(1);
});

test('staff cannot delete customers', function () {
    $customer = Customer::factory()->create();

    $this->get(route('customers.index'))->assertOk()->assertDontSee('Hapus pelanggan');

    Livewire::test(Index::class)->call('delete', $customer->id)->assertForbidden();
    Livewire::test(Show::class, ['customer' => $customer])->call('delete')->assertForbidden();

    expect($customer->fresh())->not->toBeNull();
});

test('a customer with an unfinished rental cannot be deleted', function () {
    $this->actingAs(User::factory()->owner()->create());
    $customer = Customer::factory()->create(['name' => 'Budi']);
    Rental::factory()->for($customer)->between(2, 4)->withUnits()->create();

    Livewire::test(Index::class)
        ->call('delete', $customer->id)
        ->assertSet('notice', 'Pelanggan Budi masih punya penyewaan yang belum selesai. Selesaikan penyewaannya dulu.');

    Livewire::test(Show::class, ['customer' => $customer])
        ->call('delete')
        ->assertNoRedirect();

    expect($customer->fresh())->not->toBeNull();
});

test('a customer who never rented is deleted for good', function () {
    $this->actingAs(User::factory()->owner()->create());
    $customer = Customer::factory()->create(['name' => 'Budi']);

    Livewire::test(Index::class)
        ->call('delete', $customer->id)
        ->assertSet('notice', 'Pelanggan Budi dihapus.')
        ->assertDontSee('Aksi untuk pelanggan Budi');

    expect(Customer::withTrashed()->find($customer->id))->toBeNull();
});

test('a customer with past rentals is soft-deleted and still shown in rental history', function () {
    $owner = User::factory()->owner()->create();
    $this->actingAs($owner);
    $customer = Customer::factory()->create(['name' => 'Budi Santoso']);
    $rental = Rental::factory()->for($customer)->between(-10, -5)->withUnits()->returned()->create();

    Livewire::test(Show::class, ['customer' => $customer])
        ->call('delete')
        ->assertRedirect(route('customers.index'));

    expect(Customer::withTrashed()->find($customer->id))
        ->trashed()->toBeTrue()
        ->deleted_by->toBe($owner->id);

    $this->get(route('customers.show', $customer))->assertNotFound();
    $this->get(route('rentals.show', $rental))->assertOk()->assertSee('Budi Santoso');
});
