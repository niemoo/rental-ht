<?php

use App\Livewire\Categories\Form;
use App\Livewire\Categories\Index;
use App\Models\Category;
use App\Models\Rental;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->owner()->create();
    $this->actingAs($this->user);
});

test('the add and edit category pages open', function () {
    $category = Category::factory()->create(['name' => 'Mic']);

    $this->get(route('categories.index'))
        ->assertOk()
        ->assertSee('href="'.route('categories.create').'"', false)
        ->assertSee('href="'.route('categories.edit', $category).'"', false);

    $this->get(route('categories.create'))->assertOk()->assertSee('Tambah kategori')
        ->assertSee('placeholder="25.000"', false)
        ->assertSee('<input type="hidden" x-ref="field" wire:model="dailyRate">', false);
    $this->get(route('categories.edit', $category))->assertOk()->assertSee('Ubah kategori')->assertSee('Mic');
});

test('owners can add a category', function () {
    Livewire::test(Form::class)
        ->set('name', '  Mic ')
        ->set('dailyRate', '50000')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('categories.index'));

    expect(Category::query()->where('name', 'Mic')->first())
        ->not->toBeNull()
        ->daily_rate->toBe(50000)
        ->created_by->toBe($this->user->id);
});

test('owners can rename a category', function () {
    $category = Category::factory()->create(['name' => 'Mik']);

    Livewire::test(Form::class, ['category' => $category])
        ->assertSet('name', 'Mik')
        ->set('name', 'Mic')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('categories.index'));

    expect($category->fresh()->name)->toBe('Mic')
        ->and(Category::count())->toBe(1);
});

test('category names must be unique', function () {
    Category::factory()->create(['name' => 'HT']);
    $mic = Category::factory()->create(['name' => 'Mic']);

    Livewire::test(Form::class)
        ->set('name', 'HT')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);

    Livewire::test(Form::class, ['category' => $mic])
        ->call('save')
        ->assertHasNoErrors();
});

test('categories list how many of their units are rented and available', function () {
    $category = Category::factory()->create(['name' => 'TOA']);
    $rented = Unit::factory()->for($category)->create();
    Unit::factory()->for($category)->count(2)->create();
    Rental::factory()->withUnits($rented)->create();

    $row = Livewire::test(Index::class)->viewData('categories')->first();

    expect($row)
        ->units_count->toBe(3)
        ->out_now_count->toBe(1)
        ->available_count->toBe(2);
});

test('categories can be searched and sorted', function () {
    Unit::factory()->count(2)->for(Category::factory()->create(['name' => 'HT']))->create();
    Unit::factory()->for(Category::factory()->create(['name' => 'Mic']))->create();
    Category::factory()->create(['name' => 'TOA']);

    $component = Livewire::test(Index::class);
    expect($component->viewData('categories')->pluck('name')->all())->toBe(['HT', 'Mic', 'TOA']);

    $component->call('sort', 'units')->call('sort', 'units');
    expect($component->viewData('categories')->pluck('name')->all())->toBe(['HT', 'Mic', 'TOA']);

    $component->call('sort', 'units');
    expect($component->viewData('categories')->pluck('name')->all())->toBe(['TOA', 'Mic', 'HT']);

    $component->set('search', 'mi');
    expect($component->viewData('categories')->pluck('name')->all())->toBe(['Mic']);
});

test('staff can see categories but not add, edit or delete them', function () {
    $this->actingAs(User::factory()->create());
    $category = Category::factory()->create(['name' => 'Mic']);

    $this->get(route('categories.index'))
        ->assertOk()
        ->assertSee('Mic')
        ->assertDontSee('Tambah kategori')
        ->assertSee('href="'.route('units.index', ['categories' => [$category->id]]).'"', false)
        ->assertDontSee('href="'.route('categories.edit', $category).'"', false)
        ->assertDontSee('Hapus kategori');
    $this->get(route('categories.create'))->assertForbidden();
    $this->get(route('categories.edit', $category))->assertForbidden();

    Livewire::test(Form::class)->set('name', 'TOA')->call('save')->assertForbidden();
    Livewire::test(Form::class, ['category' => $category])->set('name', 'Mik')->call('save')->assertForbidden();
    Livewire::test(Index::class)->call('delete', $category->id)->assertForbidden();

    expect(Category::query()->where('name', 'TOA')->exists())->toBeFalse()
        ->and($category->fresh()->name)->toBe('Mic');
});

test('each category row has a three-dot menu with detail, edit and delete', function () {
    $category = Category::factory()->create(['name' => 'Mic']);

    $this->get(route('categories.index'))
        ->assertOk()
        ->assertSee('Aksi untuk kategori Mic')
        ->assertSee('Detail')
        ->assertSee('href="'.route('units.index', ['categories' => [$category->id]]).'"', false)
        ->assertDontSee('Lihat unit')
        ->assertSee('href="'.route('categories.edit', $category).'"', false)
        ->assertSee('Hapus kategori');
});

test('a category that still has units cannot be deleted', function () {
    $category = Category::factory()->create(['name' => 'HT']);
    Unit::factory()->for($category)->count(2)->create();

    Livewire::test(Index::class)
        ->call('delete', $category->id)
        ->assertSet('notice', 'Kategori HT masih punya 2 unit. Pindahkan atau hapus unitnya dulu.');

    expect($category->fresh())->not->toBeNull();
});

test('an empty category is deleted for good so its name can be used again', function () {
    $category = Category::factory()->create(['name' => 'Mic']);

    Livewire::test(Index::class)
        ->call('delete', $category->id)
        ->assertSet('notice', 'Kategori Mic dihapus.')
        ->assertDontSee('Aksi untuk kategori Mic');

    expect(Category::withTrashed()->find($category->id))->toBeNull();

    Livewire::test(Form::class)->set('name', 'Mic')->set('dailyRate', '50000')->call('save')->assertHasNoErrors();
});

test('a category whose deleted units are still in history is soft-deleted', function () {
    $category = Category::factory()->create(['name' => 'TOA']);
    $unit = Unit::factory()->for($category)->create();
    Rental::factory()->between(-9, -5)->withUnits($unit)->returned()->create();
    $unit->remove();

    Livewire::test(Index::class)->call('delete', $category->id)->assertSet('notice', 'Kategori TOA dihapus.');

    expect(Category::withTrashed()->find($category->id))
        ->trashed()->toBeTrue()
        ->deleted_by->toBe($this->user->id);
});
