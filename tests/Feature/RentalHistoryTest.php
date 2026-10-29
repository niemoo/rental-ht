<?php

use App\Enums\RentalStatus;
use App\Livewire\Rentals\Index;
use App\Models\Customer;
use App\Models\Rental;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('history can be filtered by status with a count for each', function () {
    Rental::factory()->withUnits()->create(['customer_id' => Customer::factory()->create(['name' => 'Aktif'])->id]);
    Rental::factory()->between(3, 5)->withUnits()->create(['customer_id' => Customer::factory()->create(['name' => 'Nanti'])->id]);
    Rental::factory()->between(-9, -6)->withUnits()->returned()->create(['customer_id' => Customer::factory()->create(['name' => 'Beres'])->id]);

    $component = Livewire::test(Index::class);
    $names = fn (): array => $component->viewData('rentals')->pluck('customer.name')->sort()->values()->all();

    expect($component->viewData('total'))->toBe(3)
        ->and($component->viewData('statusFilter')['counts'])->toBe(['active' => 1, 'upcoming' => 1, 'completed' => 1, 'cancelled' => 0]);

    $component->set('statuses', ['upcoming']);
    expect($names())->toBe(['Nanti']);

    $component->set('statuses', ['completed', 'active']);
    expect($names())->toBe(['Aktif', 'Beres']);

    $component->set('statuses', ['bogus']);
    expect($names())->toBe(['Aktif', 'Beres', 'Nanti']);
});

test('the status tabs pick one status and show which one is picked', function () {
    Rental::factory()->withUnits()->create();
    Rental::factory()->between(3, 5)->withUnits()->create();

    $component = Livewire::test(Index::class)
        ->assertSeeHtml("wire:click=\"\$set('statuses', [&#039;active&#039;])\"")
        ->assertSeeHtml('aria-selected="true"');

    expect(substr_count($component->html(), 'aria-selected="true"'))->toBe(1);

    $component->set('statuses', ['active']);
    expect($component->viewData('rentals'))->toHaveCount(1)
        ->and(preg_match('/aria-selected="true"[^>]*>\s*Aktif/', $component->html()))->toBe(1);

    $component->set('statuses', ['active', 'upcoming']);
    expect(substr_count($component->html(), 'aria-selected="true"'))->toBe(0);
});

test('history can be sorted by status', function () {
    Rental::factory()->between(-9, -6)->withUnits()->returned()->create(['customer_id' => Customer::factory()->create(['name' => 'Beres'])->id]);
    Rental::factory()->between(3, 5)->withUnits()->create(['customer_id' => Customer::factory()->create(['name' => 'Nanti'])->id]);
    Rental::factory()->withUnits()->create(['customer_id' => Customer::factory()->create(['name' => 'Aktif'])->id]);

    $names = Livewire::test(Index::class)->call('sortTo', 'status', 'asc')->viewData('rentals')->pluck('customer.name')->all();

    expect($names)->toBe(['Aktif', 'Nanti', 'Beres']);
});

test('each row has a menu with detail, and edit only while the rental is not completed', function () {
    $open = Rental::factory()->withUnits()->create();
    $completed = Rental::factory()->between(-9, -6)->withUnits()->returned()->create();

    $this->get(route('rentals.index'))
        ->assertOk()
        ->assertSee(route('rentals.show', $open))
        ->assertSee(route('rentals.edit', $open))
        ->assertSee(route('rentals.show', $completed))
        ->assertDontSee(route('rentals.edit', $completed));
});

test('history is newest first and can be sorted by customer or unit count', function () {
    Rental::factory()->between(-5, -1)->withUnits(Unit::factory()->count(3)->create())->create(['customer_id' => Customer::factory()->create(['name' => 'Budi'])->id]);
    Rental::factory()->between(-1, 2)->withUnits()->create(['customer_id' => Customer::factory()->create(['name' => 'Ayu'])->id]);

    $names = fn ($component): array => $component->viewData('rentals')->pluck('customer.name')->all();
    $component = Livewire::test(Index::class);

    expect($names($component))->toBe(['Ayu', 'Budi']);
    expect($names($component->call('sort', 'customer')))->toBe(['Ayu', 'Budi']);
    expect($names($component->call('sort', 'customer')))->toBe(['Budi', 'Ayu']);
    expect($names($component->call('sortTo', 'units', 'asc')))->toBe(['Ayu', 'Budi']);
});

test('history can be searched by unit code', function () {
    Rental::factory()->withUnits(Unit::factory()->create(['code' => 'MIC-07']))->create(['customer_id' => Customer::factory()->create(['name' => 'Sinta'])->id]);
    Rental::factory()->withUnits()->create(['customer_id' => Customer::factory()->create(['name' => 'Andi'])->id]);

    Livewire::test(Index::class)
        ->set('search', 'mic-07')
        ->assertSee('Sinta')
        ->assertDontSee('Andi');
});

test('completed rentals show whether they came back on time or late', function () {
    $onTime = Rental::factory()->between(-10, -6)->withUnits()->returned()->create();
    $late = Rental::factory()->between(-10, -6)->withUnits()->returned()->create();
    $late->items()->update(['returned_at' => $late->due_date->copy()->addDays(2)]);

    $rentals = Livewire::test(Index::class)->viewData('rentals');

    expect($rentals->firstWhere('id', $onTime->id)->timing())
        ->status->toBe(RentalStatus::Completed)
        ->pill->toBe('Selesai')
        ->and($rentals->firstWhere('id', $late->id)->timing())
        ->status->toBe(RentalStatus::CompletedLate)
        ->pill->toBe('Selesai, telat 2h');

    $this->get(route('rentals.index'))->assertOk()->assertSee('Selesai, telat 2h');
});
