<?php

use App\Livewire\Dashboard;
use App\Livewire\Rentals\Show;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('returning a rental marks every open unit as returned by the current user', function () {
    $rental = Rental::factory()->withUnits(Unit::factory()->count(3)->create())->create();

    Livewire::test(Show::class, ['rental' => $rental])
        ->assertSee('Belum kembali')
        ->call('returnRental', $rental->id)
        ->assertSee('Selesai')
        ->assertDontSee('Belum kembali');

    $rental->items->each(function (RentalItem $item) {
        $item->refresh();
        expect($item->returned_at)->not->toBeNull()
            ->and($item->updated_by)->toBe($this->user->id);
    });
});

test('returning from the dashboard removes the rental from the out-now list', function () {
    $rental = Rental::factory()->withUnits()->create();

    Livewire::test(Dashboard::class)
        ->assertSee($rental->customer->name)
        ->call('returnRental', $rental->id)
        ->assertDontSee($rental->customer->name);

    expect($rental->fresh()->isCompleted())->toBeTrue();
});

test('returning an already returned rental keeps the original return date', function () {
    $rental = Rental::factory()->between(-10, -5)->withUnits()->returned()->create();
    $originalReturn = $rental->items()->first()->returned_at;

    Livewire::test(Show::class, ['rental' => $rental])->call('returnRental', $rental->id);

    expect($rental->items()->first()->returned_at->equalTo($originalReturn))->toBeTrue();
});

test('the out now table is paginated and stays on a page that still has rows after a return', function () {
    Rental::factory()->count(11)->withUnits()->create();

    $component = Livewire::test(Dashboard::class);
    expect($component->viewData('outNow'))->toHaveCount(10)
        ->and($component->viewData('outNow')->total())->toBe(11);

    $component->call('nextPage');
    $lastOnSecondPage = $component->viewData('outNow')->first();

    $component->call('returnRental', $lastOnSecondPage->id);
    expect($component->viewData('outNow')->currentPage())->toBe(1)
        ->and($component->viewData('outNow'))->toHaveCount(10);
});
