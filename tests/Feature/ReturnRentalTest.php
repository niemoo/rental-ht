<?php

use App\Livewire\Dashboard;
use App\Livewire\Rentals\ReturnUnits;
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

test('the rental page links to the return page', function () {
    $rental = Rental::factory()->withUnits()->create();

    $this->get(route('rentals.show', $rental))->assertOk()->assertSee(route('rentals.return', $rental), false);
});

test('returning a rental marks every open unit as returned by the current user', function () {
    $rental = Rental::factory()->withUnits(Unit::factory()->count(3)->create())->create();

    Livewire::test(ReturnUnits::class, ['rental' => $rental])
        ->assertSee('Kembalikan 3 unit')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('rentals.show', $rental));

    $rental->items->each(function (RentalItem $item) {
        $item->refresh();
        expect($item->returned_at)->not->toBeNull()
            ->and($item->updated_by)->toBe($this->user->id);
    });

    Livewire::test(Show::class, ['rental' => $rental])
        ->assertSee('Selesai')
        ->assertDontSee('Belum kembali');
});

test('a returned rental leaves the dashboard out-now list', function () {
    $rental = Rental::factory()->withUnits()->create();

    Livewire::test(Dashboard::class)->assertSee($rental->customer->name);
    Livewire::test(ReturnUnits::class, ['rental' => $rental])->call('save');

    Livewire::test(Dashboard::class)->assertDontSee($rental->customer->name);
    expect($rental->fresh()->isCompleted())->toBeTrue();
});

test('the return page of a rental already returned goes back to the rental and changes nothing', function () {
    $rental = Rental::factory()->between(-10, -5)->withUnits()->returned()->create();
    $originalReturn = $rental->items()->first()->returned_at;

    Livewire::test(ReturnUnits::class, ['rental' => $rental])->assertRedirect(route('rentals.show', $rental));

    expect($rental->items()->first()->returned_at->equalTo($originalReturn))->toBeTrue();
});

test('the out now table is paginated and stays on a page that still has rows after a return', function () {
    Rental::factory()->count(11)->withUnits()->create();

    $component = Livewire::test(Dashboard::class);
    expect($component->viewData('outNow'))->toHaveCount(10)
        ->and($component->viewData('outNow')->total())->toBe(11);

    $component->call('nextPage');
    $lastOnSecondPage = $component->viewData('outNow')->first();

    Livewire::test(ReturnUnits::class, ['rental' => $lastOnSecondPage])->call('save');

    $component->call('$refresh');
    expect($component->viewData('outNow')->currentPage())->toBe(1)
        ->and($component->viewData('outNow'))->toHaveCount(10);
});
