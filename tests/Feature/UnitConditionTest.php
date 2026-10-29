<?php

use App\Enums\UnitCondition;
use App\Livewire\Categories;
use App\Livewire\Dashboard;
use App\Livewire\Rentals;
use App\Livewire\Units;
use App\Models\Category;
use App\Models\Rental;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->owner()->create();
    $this->actingAs($this->user);
});

test('new units start in good condition', function () {
    Livewire::test(Units\Form::class)
        ->set('categoryId', Category::factory()->create()->id)
        ->set('code', 'A-01')
        ->call('save')
        ->assertHasNoErrors();

    expect(Unit::query()->where('code', 'A-01')->sole()->condition)->toBe(UnitCondition::Good);
});

test('a unit condition can be changed from the edit page', function () {
    $unit = Unit::factory()->create(['code' => 'A-01']);

    Livewire::test(Units\Form::class, ['unit' => $unit])
        ->assertSet('condition', 'good')
        ->set('condition', 'under_repair')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('units.index'));

    expect($unit->fresh()->condition)->toBe(UnitCondition::UnderRepair);
});

test('an unknown condition is rejected', function () {
    $unit = Unit::factory()->create();

    Livewire::test(Units\Form::class, ['unit' => $unit])
        ->set('condition', 'broken-ish')
        ->call('save')
        ->assertHasErrors(['condition']);
});

test('taking a booked unit out of service warns about its bookings', function () {
    $unit = Unit::factory()->create(['code' => 'A-01']);
    Rental::factory()->between(4, 6)->withUnits($unit)->create();

    Livewire::test(Units\Form::class, ['unit' => $unit])
        ->set('condition', 'damaged')
        ->call('save');

    expect(session('status'))->toBe('Unit A-01 disimpan. Perhatian: unit ini rusak tapi masih ada di 1 penyewaan yang belum selesai. Cek penyewaannya dan ganti unitnya bila perlu.');
});

test('only units in good condition can be booked', function (UnitCondition $condition) {
    $good = Unit::factory()->create(['code' => 'A-01']);
    $notGood = Unit::factory()->condition($condition)->create(['code' => 'A-02']);

    $form = Livewire::test(Rentals\Form::class)
        ->set('startDate', today()->addDay()->toDateString())
        ->set('dueDate', today()->addDays(2)->toDateString());

    expect($form->instance()->availableUnits->modelKeys())->toBe([$good->id]);

    $form->set('customerMode', 'new')
        ->set('customerName', 'Budi')
        ->set('customerPhone', '0812')
        ->set('unitIds', [$notGood->id])
        ->call('save')
        ->assertHasErrors(['unit_ids']);

    expect(Rental::count())->toBe(0);
})->with([UnitCondition::UnderRepair, UnitCondition::Damaged, UnitCondition::Lost]);

test('the units list shows the condition and can filter and sort by it', function () {
    Unit::factory()->create(['code' => 'A-01']);
    Unit::factory()->condition(UnitCondition::Lost)->create(['code' => 'A-02']);
    Unit::factory()->condition(UnitCondition::UnderRepair)->create(['code' => 'A-03']);
    Unit::factory()->condition(UnitCondition::Damaged)->create(['code' => 'A-04']);

    $component = Livewire::test(Units\Index::class)
        ->assertSee('Kondisi')
        ->assertSee('Dalam perbaikan')
        ->assertSee('Tidak tersedia');

    expect($component->viewData('summary'))->toBe(['all' => 4, 'available' => 1])
        ->and($component->viewData('filters')['statuses']['counts'])->toBe(['available' => 1, 'rented' => 0, 'unusable' => 3]);

    $component->call('sortTo', 'condition', 'asc');
    expect($component->viewData('units')->pluck('code')->all())->toBe(['A-01', 'A-03', 'A-04', 'A-02']);

    $component->set('conditions', ['damaged']);
    expect($component->viewData('units')->pluck('code')->all())->toBe(['A-04']);

    $component->set('conditions', ['damaged', 'lost']);
    expect($component->viewData('units')->pluck('code')->all())->toBe(['A-04', 'A-02']);

    $component->set('conditions', []);
    expect($component->viewData('units'))->toHaveCount(4);
});

test('the kondisi column header opens a popup to sort and filter', function () {
    Unit::factory()->create(['code' => 'A-01']);

    Livewire::test(Units\Index::class)
        ->assertSee('Urutkan dan filter Kondisi')
        ->assertSeeHtml('wire:model.live="conditions"')
        ->assertSeeHtml("sortTo('condition', 'desc')")
        ->assertDontSee('Hapus filter')
        ->set('conditions', ['lost'])
        ->assertSee('Hapus filter')
        ->assertSee('1 filter aktif');
});

test('the condition filter is read from the url and ignores unknown values', function () {
    Unit::factory()->create(['code' => 'A-01']);
    Unit::factory()->condition(UnitCondition::Damaged)->create(['code' => 'A-02']);

    $component = Livewire::withQueryParams(['conditions' => ['damaged', 'nonsense']])->test(Units\Index::class);

    expect($component->viewData('units')->pluck('code')->all())->toBe(['A-02']);
});

test('changing the condition filter goes back to page one', function () {
    Unit::factory()->count(16)->create();

    Livewire::test(Units\Index::class)
        ->call('nextPage')
        ->assertSet('paginators.page', 2)
        ->set('conditions', ['good'])
        ->assertSet('paginators.page', 1);
});

test('category and dashboard availability leave out units that cannot be used', function () {
    $category = Category::factory()->create(['name' => 'HT']);
    Unit::factory()->for($category)->count(2)->create();
    Unit::factory()->for($category)->condition(UnitCondition::Damaged)->create();

    $row = Livewire::test(Categories\Index::class)->viewData('categories')->first();
    expect($row->units_count)->toBe(3)
        ->and($row->available_count)->toBe(2);

    expect(Livewire::test(Dashboard::class)->viewData('fleet'))->toMatchArray(['available' => 2, 'out_of_service' => 1]);
});

test('returning a rental records the condition each unit came back in', function () {
    [$fine, $broken, $missing] = Unit::factory()->count(3)->sequence(['code' => 'A-01'], ['code' => 'A-02'], ['code' => 'A-03'])->create();
    $rental = Rental::factory()->withUnits([$fine, $broken, $missing])->create();
    $items = $rental->items()->get()->keyBy('unit_id');

    Livewire::test(Rentals\ReturnUnits::class, ['rental' => $rental])
        ->set("conditions.{$items[$broken->id]->id}", 'damaged')
        ->set("conditions.{$items[$missing->id]->id}", 'lost')
        ->assertSee('2 unit tidak bisa disewakan lagi')
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(Rentals\Show::class, ['rental' => $rental])
        ->assertSee('Rusak')
        ->assertSee('Dicatat hilang');

    expect($items[$broken->id]->fresh()->return_condition)->toBe(UnitCondition::Damaged)
        ->and($items[$fine->id]->fresh()->return_condition)->toBe(UnitCondition::Good)
        ->and($broken->fresh()->condition)->toBe(UnitCondition::Damaged)
        ->and($missing->fresh()->condition)->toBe(UnitCondition::Lost)
        ->and($fine->fresh()->condition)->toBe(UnitCondition::Good)
        ->and($rental->fresh()->isCompleted())->toBeTrue();
});

test('each unit on the return page starts at the condition it has now', function () {
    $unit = Unit::factory()->create();
    $rental = Rental::factory()->withUnits($unit)->create();
    $unit->update(['condition' => UnitCondition::UnderRepair]);

    Livewire::test(Rentals\ReturnUnits::class, ['rental' => $rental])
        ->assertSet('conditions', [$rental->items()->sole()->id => 'under_repair'])
        ->call('save');

    expect($unit->fresh()->condition)->toBe(UnitCondition::UnderRepair)
        ->and($rental->items()->sole()->return_condition)->toBe(UnitCondition::UnderRepair);
});

test('an unknown return condition is rejected and nothing is returned', function () {
    $rental = Rental::factory()->withUnits()->create();

    $itemId = $rental->items()->sole()->id;

    Livewire::test(Rentals\ReturnUnits::class, ['rental' => $rental])
        ->set("conditions.{$itemId}", 'meh')
        ->call('save')
        ->assertHasErrors(["conditions.{$itemId}"]);

    expect($rental->fresh()->isCompleted())->toBeFalse();
});

test('the return page lists each unit with a condition picker', function () {
    $rental = Rental::factory()->withUnits(Unit::factory()->create(['code' => 'A-07']))->create();

    $this->get(route('rentals.return', $rental))
        ->assertOk()
        ->assertSee('Kondisi A-07')
        ->assertSee('Dalam perbaikan');
});

test('every unit on the return page can be given the same condition at once', function () {
    $rental = Rental::factory()->withUnits(Unit::factory()->count(3)->create())->create();

    $component = Livewire::test(Rentals\ReturnUnits::class, ['rental' => $rental])
        ->call('setAllConditions', 'damaged')
        ->assertSee('3 unit tidak bisa disewakan lagi');

    expect(array_values(array_unique($component->get('conditions'))))->toBe(['damaged']);

    $component->call('setAllConditions', 'meh');
    expect(array_values(array_unique($component->get('conditions'))))->toBe(['damaged']);
});
