<?php

use App\Enums\UnitCondition;
use App\Livewire\Units\Form;
use App\Livewire\Units\Index;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Rental;
use App\Models\Unit;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->owner()->create();
    $this->actingAs($this->user);
});

test('the add unit page opens from the units list', function () {
    $this->get(route('units.index'))->assertOk()->assertSee('href="'.route('units.create').'"', false);
    $this->get(route('units.create'))->assertOk()->assertSee('Tambah unit');
});

test('owners can add a unit and return to the list', function () {
    Category::factory()->create(['name' => 'HT']);
    $mic = Category::factory()->create(['name' => 'Mic']);

    Livewire::test(Form::class)
        ->set('categoryId', $mic->id)
        ->set('code', '  B-01 ')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('units.index'));

    expect(Unit::query()->where('code', 'B-01')->first())
        ->not->toBeNull()
        ->category_id->toBe($mic->id)
        ->created_by->toBe($this->user->id);
});

test('save and add another keeps the category and clears the code', function () {
    $mic = Category::factory()->create(['name' => 'Mic']);

    Livewire::test(Form::class)
        ->set('categoryId', $mic->id)
        ->set('code', 'MIC-01')
        ->call('save', true)
        ->assertHasNoErrors()
        ->assertNoRedirect()
        ->assertSet('code', '')
        ->assertSet('categoryId', $mic->id)
        ->assertSet('notice', 'Unit MIC-01 ditambahkan.');
});

test('the add unit page preselects the category passed in the link', function () {
    Category::factory()->create(['name' => 'HT']);
    $toa = Category::factory()->create(['name' => 'TOA']);

    Livewire::withQueryParams(['category' => $toa->id])
        ->test(Form::class)
        ->assertSet('categoryId', $toa->id);
});

test('a unit needs a category', function () {
    Livewire::test(Form::class)
        ->set('code', 'B-01')
        ->call('save')
        ->assertHasErrors(['categoryId' => 'required']);
});

test('unit codes must be unique', function () {
    Unit::factory()->create(['code' => 'B-01']);

    Livewire::test(Form::class)
        ->set('categoryId', Category::factory()->create()->id)
        ->set('code', 'B-01')
        ->call('save')
        ->assertHasErrors(['code' => 'unique']);
});

/**
 * Codes on the current page of the units table.
 *
 * @return list<string>
 */
function unitCodes(Testable $component): array
{
    return $component->viewData('units')->pluck('code')->all();
}

test('units can be filtered by status and searched by renter', function () {
    $rented = Unit::factory()->create(['code' => 'R-01']);
    Unit::factory()->create(['code' => 'F-01']);
    Unit::factory()->condition(UnitCondition::Damaged)->create(['code' => 'X-01']);
    Rental::factory()->withUnits($rented)->create(['customer_id' => Customer::factory()->create(['name' => 'Sinta'])->id]);

    $component = Livewire::test(Index::class);

    expect(unitCodes($component->set('statuses', ['rented'])))->toBe(['R-01'])
        ->and(unitCodes($component->set('statuses', ['available'])))->toBe(['F-01'])
        ->and(unitCodes($component->set('statuses', ['unusable'])))->toBe(['X-01'])
        ->and(unitCodes($component->set('statuses', ['available', 'unusable'])))->toBe(['F-01', 'X-01'])
        ->and(unitCodes($component->set('statuses', [])->set('search', 'sinta')))->toBe(['R-01']);
});

test('units can be filtered by one or more categories', function () {
    Unit::factory()->for(Category::factory()->create(['name' => 'HT']))->create(['code' => 'A-01']);
    $mic = Category::factory()->create(['name' => 'Mic']);
    $toa = Category::factory()->create(['name' => 'TOA']);
    Unit::factory()->for($mic)->create(['code' => 'MIC-01']);
    Unit::factory()->for($toa)->create(['code' => 'TOA-01']);

    $component = Livewire::test(Index::class);

    expect(unitCodes($component))->toBe(['A-01', 'MIC-01', 'TOA-01'])
        ->and(unitCodes($component->set('categoryIds', [(string) $mic->id])))->toBe(['MIC-01'])
        ->and(unitCodes($component->set('categoryIds', [(string) $mic->id, (string) $toa->id])))->toBe(['MIC-01', 'TOA-01'])
        ->and(unitCodes($component->set('categoryIds', [])->set('search', 'mic')))->toBe(['MIC-01']);
});

test('the category detail link opens the units table filtered to that category', function () {
    Unit::factory()->create(['code' => 'A-01']);
    $mic = Category::factory()->create(['name' => 'Mic']);
    Unit::factory()->for($mic)->create(['code' => 'MIC-01']);

    $component = Livewire::withQueryParams(['categories' => [$mic->id]])->test(Index::class);

    expect(unitCodes($component))->toBe(['MIC-01']);
    $component->assertSeeHtml('href="'.route('units.create', ['category' => $mic->id]).'"');
});

test('units can be filtered by whether they are past their due date', function () {
    [$late, $onTime] = Unit::factory()->count(2)->sequence(['code' => 'L-01'], ['code' => 'T-01'])->create();
    Unit::factory()->create(['code' => 'F-01']);
    Rental::factory()->between(-6, -1)->withUnits($late)->create();
    Rental::factory()->between(-1, 4)->withUnits($onTime)->create();

    $component = Livewire::test(Index::class);

    expect(unitCodes($component->set('dueStates', ['overdue'])))->toBe(['L-01'])
        ->and(unitCodes($component->set('dueStates', ['on_time'])))->toBe(['T-01'])
        ->and(unitCodes($component->set('dueStates', ['overdue', 'on_time'])))->toBe(['L-01', 'T-01']);
});

test('each column filter counts its options within the other active filters', function () {
    $ht = Category::factory()->create(['name' => 'HT']);
    $mic = Category::factory()->create(['name' => 'Mic']);
    $rented = Unit::factory()->for($ht)->create();
    Unit::factory()->for($ht)->create();
    Unit::factory()->for($ht)->condition(UnitCondition::Damaged)->create();
    Unit::factory()->for($mic)->create();
    Rental::factory()->between(-6, -1)->withUnits($rented)->create();

    $filters = Livewire::test(Index::class)->set('categoryIds', [(string) $ht->id])->viewData('filters');

    expect($filters['categoryIds']['counts'])->toEqual([$ht->id => 3, $mic->id => 1])
        ->and($filters['statuses']['counts'])->toBe(['available' => 1, 'rented' => 1, 'unusable' => 1])
        ->and($filters['conditions']['counts'])->toEqual(['good' => 2, 'damaged' => 1])
        ->and($filters['dueStates']['counts'])->toBe(['overdue' => 1, 'on_time' => 0]);
});

test('invalid filter values from the url are ignored', function () {
    Unit::factory()->create(['code' => 'A-01']);

    $component = Livewire::withQueryParams([
        'categories' => ['abc', '1; drop'],
        'status' => ['nope'],
        'due' => ['later'],
    ])->test(Index::class);

    expect(unitCodes($component))->toBe(['A-01']);
});

test('the filterable unit columns open a sort and filter popup', function (string $label, string $model) {
    Unit::factory()->create();

    Livewire::test(Index::class)
        ->assertSee("Urutkan dan filter {$label}")
        ->assertSeeHtml('wire:model.live="'.$model.'"');
})->with([
    ['Kategori', 'categoryIds'],
    ['Kondisi', 'conditions'],
    ['Status', 'statuses'],
    ['Selesai', 'dueStates'],
]);

test('units can be sorted by each column', function (string $column, string $first, string $last) {
    $rentedLater = Unit::factory()->for(Category::factory()->create(['name' => 'Mic']))->create(['code' => 'A-01']);
    $rentedSooner = Unit::factory()->for(Category::factory()->create(['name' => 'HT']))->create(['code' => 'B-01']);
    Rental::factory()->between(-1, 9)->withUnits($rentedLater)->create(['customer_id' => Customer::factory()->create(['name' => 'Zaki'])->id]);
    Rental::factory()->between(-1, 2)->withUnits($rentedSooner)->create(['customer_id' => Customer::factory()->create(['name' => 'Ayu'])->id]);
    Unit::factory()->for(Category::factory()->create(['name' => 'TOA']))->create(['code' => 'C-01']);

    $component = Livewire::test(Index::class)->call('sortTo', $column, 'asc');
    expect($component->viewData('units')->pluck('code')->all())->toBe(sortedCodes($first, $last));

    $component->call('sort', $column);
    expect($component->viewData('units')->pluck('code')->all())->toBe(array_reverse(sortedCodes($first, $last)))
        ->and($component->get('sortDirection'))->toBe('desc');
})->with([
    'kode' => ['code', 'A-01', 'C-01'],
    'kategori' => ['category', 'B-01', 'C-01'],
    'penyewa' => ['renter', 'C-01', 'A-01'],
    'jatuh tempo' => ['due', 'C-01', 'A-01'],
]);

/**
 * The three codes from the sort test, with $first first, $last last and the remaining one between.
 *
 * @return list<string>
 */
function sortedCodes(string $first, string $last): array
{
    return [$first, ...array_values(array_diff(['A-01', 'B-01', 'C-01'], [$first, $last])), $last];
}

test('an unknown sort column from the url is ignored', function () {
    Unit::factory()->create(['code' => 'B-01']);
    Unit::factory()->create(['code' => 'A-01']);

    Livewire::withQueryParams(['sort' => 'code; drop table units'])
        ->test(Index::class)
        ->assertOk()
        ->assertSeeInOrder(['A-01', 'B-01'])
        ->call('sort', 'nope')
        ->assertSet('sortBy', 'code; drop table units');
});

test('the units list is paginated and searching goes back to page one', function () {
    Unit::factory()->count(11)->sequence(fn ($sequence) => ['code' => sprintf('U-%02d', $sequence->index + 1)])->create();

    Livewire::test(Index::class)
        ->assertSee('U-10')
        ->assertDontSee('U-11')
        ->call('nextPage')
        ->assertSee('U-11')
        ->assertDontSee('U-10')
        ->set('search', 'U-1')
        ->assertSet('paginators.page', 1);
});

test('the rows per page picker changes the page size and goes back to page one', function () {
    Unit::factory()->count(25)->sequence(fn ($sequence) => ['code' => sprintf('U-%02d', $sequence->index + 1)])->create();

    Livewire::test(Index::class)
        ->assertSeeHtml('wire:model.live="perPage"')
        ->call('nextPage')
        ->set('perPage', '20')
        ->assertSet('paginators.page', 1)
        ->assertSee('U-20')
        ->assertDontSee('U-21');
});

test('a long pagination shows the pages around the current one instead of every page', function () {
    Unit::factory()->count(101)->create(); // 11 pages of 10

    Livewire::test(Index::class)
        ->assertSeeHtml("gotoPage(6, 'page')")
        ->assertDontSeeHtml("gotoPage(7, 'page')")
        ->assertSeeHtml("gotoPage(11, 'page')")
        ->call('gotoPage', 6)
        ->assertSeeHtml("gotoPage(5, 'page')")
        ->assertSeeHtml("gotoPage(7, 'page')")
        ->assertDontSeeHtml("gotoPage(4, 'page')")
        ->assertDontSeeHtml("gotoPage(8, 'page')");
});

test('a rows per page value that is not one of the choices falls back to ten', function () {
    Unit::factory()->count(12)->sequence(fn ($sequence) => ['code' => sprintf('U-%02d', $sequence->index + 1)])->create();

    Livewire::withQueryParams(['per_page' => '7'])
        ->test(Index::class)
        ->assertSee('U-10')
        ->assertDontSee('U-11');
});

test('each unit row has a three-dot menu with edit and delete', function () {
    $unit = Unit::factory()->create(['code' => 'A-01']);

    $this->get(route('units.index'))
        ->assertOk()
        ->assertSee('Aksi untuk unit A-01')
        ->assertSee('href="'.route('units.edit', $unit).'"', false)
        ->assertSee('Hapus unit');
});

test('owners can change a unit category and code', function () {
    $unit = Unit::factory()->create(['code' => 'A-01']);
    $mic = Category::factory()->create(['name' => 'Mic']);

    $this->get(route('units.edit', $unit))->assertOk()->assertSee('Ubah unit A-01');

    Livewire::test(Form::class, ['unit' => $unit])
        ->assertSet('code', 'A-01')
        ->assertSet('categoryId', $unit->category_id)
        ->set('categoryId', $mic->id)
        ->set('code', 'MIC-01')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('units.index'));

    expect($unit->fresh())
        ->code->toBe('MIC-01')
        ->category_id->toBe($mic->id)
        ->updated_by->toBe($this->user->id);
});

test('a unit keeps its own code when edited without changing it', function () {
    $unit = Unit::factory()->create(['code' => 'A-01']);

    Livewire::test(Form::class, ['unit' => $unit])->call('save')->assertHasNoErrors();
});

test('a unit that is out or booked cannot be deleted', function (int $startOffset) {
    $unit = Unit::factory()->create(['code' => 'A-01']);
    Rental::factory()->between($startOffset, $startOffset + 3)->withUnits($unit)->create();

    Livewire::test(Index::class)
        ->call('delete', $unit->id)
        ->assertSet('notice', 'Unit A-01 masih disewa atau sudah dipesan. Selesaikan penyewaannya dulu sebelum menghapus.');

    expect($unit->fresh())->not->toBeNull();
})->with(['out now' => -1, 'booked' => 5]);

test('a unit that was never rented is deleted for good so its code can be used again', function () {
    $unit = Unit::factory()->create(['code' => 'A-01']);

    Livewire::test(Index::class)
        ->call('delete', $unit->id)
        ->assertSet('notice', 'Unit A-01 dihapus.')
        ->assertDontSee('Aksi untuk unit A-01');

    expect(Unit::withTrashed()->find($unit->id))->toBeNull();

    Livewire::test(Form::class)
        ->set('categoryId', Category::factory()->create()->id)
        ->set('code', 'A-01')
        ->call('save')
        ->assertHasNoErrors();
});

test('a unit with rental history is soft-deleted and still shows on past rentals', function () {
    $unit = Unit::factory()->create(['code' => 'A-01']);
    $rental = Rental::factory()->between(-9, -5)->withUnits($unit)->returned()->create();

    Livewire::test(Index::class)->call('delete', $unit->id)->assertSet('notice', 'Unit A-01 dihapus.');

    expect(Unit::withTrashed()->find($unit->id))
        ->trashed()->toBeTrue()
        ->deleted_by->toBe($this->user->id);

    $this->get(route('units.index'))->assertDontSee('Aksi untuk unit A-01');
    $this->get(route('rentals.show', $rental))->assertOk()->assertSee('A-01');
});

test('staff cannot edit or delete units', function () {
    $unit = Unit::factory()->create(['code' => 'A-01']);
    $this->actingAs(User::factory()->create());

    $this->get(route('units.index'))->assertOk()->assertDontSee('Aksi untuk unit A-01');
    $this->get(route('units.edit', $unit))->assertForbidden();

    Livewire::test(Form::class, ['unit' => $unit])->set('code', 'X-01')->call('save')->assertForbidden();
    Livewire::test(Index::class)->call('delete', $unit->id)->assertForbidden();

    expect($unit->fresh()->code)->toBe('A-01');
});
