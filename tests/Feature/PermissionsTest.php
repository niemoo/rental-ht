<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Livewire\Customers;
use App\Livewire\Rentals;
use App\Livewire\Units;
use App\Models\Rental;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as RoleModel;

test('built-in roles start with their default permissions', function (Role $role) {
    $granted = RoleModel::findByName($role->value)->permissions->pluck('name')->sort()->values()->all();
    $expected = collect($role->defaultPermissions())->map(fn (Permission $permission) => $permission->value)->sort()->values()->all();

    expect($granted)->toBe($expected);
})->with(Role::cases());

test('the roles seeder is idempotent', function () {
    (new RolesAndPermissionsSeeder)->run();
    (new RolesAndPermissionsSeeder)->run();

    expect(RoleModel::count())->toBe(count(Role::cases()))
        ->and(Spatie\Permission\Models\Permission::count())->toBe(count(Permission::cases()));
});

test('new users are staff by default', function () {
    $user = User::factory()->create();

    expect($user->hasRole(Role::Staff))->toBeTrue()
        ->and($user->isOwner())->toBeFalse()
        ->and(User::factory()->owner()->create()->roleName())->toBe(Role::Owner->value);
});

test('staff cannot add units', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('units.index'))->assertOk()->assertDontSee('Tambah unit');
    $this->get(route('units.create'))->assertForbidden();

    Livewire::test(Units\Form::class)->set('code', 'X-01')->call('save')->assertForbidden();

    expect(Unit::query()->where('code', 'X-01')->exists())->toBeFalse();
});

test('owners see the add unit button', function () {
    $this->actingAs(User::factory()->owner()->create())
        ->get(route('units.index'))
        ->assertOk()
        ->assertSee('Tambah unit');
});

test('a user without a role cannot open any protected page', function (string $route) {
    $user = User::factory()->create();
    $user->syncRoles([]);

    $this->actingAs($user)->get(route($route))->assertForbidden();
})->with(['units.index', 'customers.index', 'rentals.index', 'rentals.create']);

test('a user without a role sees no actions and cannot call them', function () {
    $user = User::factory()->create();
    $user->syncRoles([]);
    $rental = Rental::factory()->withUnits()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Penyewaan baru')
        ->assertDontSee('Kembalikan')
        ->assertDontSee(route('units.index'));

    $this->get(route('rentals.return', $rental))->assertForbidden();
    Livewire::test(Rentals\ReturnUnits::class, ['rental' => $rental])->call('save')->assertForbidden();
    Livewire::test(Customers\Form::class)->set('name', 'A')->set('phone', '1')->call('save')->assertForbidden();
    Livewire::test(Rentals\Form::class)->call('save')->assertForbidden();

    expect($rental->fresh()->isCompleted())->toBeFalse();
});
