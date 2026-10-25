<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Livewire\Roles\Index;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    $this->actingAs(User::factory()->owner()->create());
});

test('only users with permission can open the roles page', function () {
    $this->get(route('roles.index'))->assertOk()->assertSee('Superadmin')->assertSee('Pemilik')->assertSee('Staf');
    $this->actingAs(User::factory()->create())->get(route('roles.index'))->assertForbidden();
});

test('owners can create a custom role and its permissions take effect', function () {
    Livewire::test(Index::class)
        ->call('create')
        ->set('name', 'Kasir')
        ->set('permissions', [Permission::ViewRentals->value, Permission::ReturnRentals->value])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Kasir');

    $cashier = User::factory()->create();
    $cashier->syncRoles('Kasir');

    expect($cashier->can(Permission::ReturnRentals))->toBeTrue()
        ->and($cashier->can(Permission::CreateRentals))->toBeFalse();

    $this->actingAs($cashier)->get(route('rentals.index'))->assertOk();
    $this->actingAs($cashier)->get(route('rentals.create'))->assertForbidden();
});

test('editing a custom role renames it and replaces its permissions', function () {
    $role = RoleModel::create(['name' => 'Gudang', 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::ViewUnits->value);

    Livewire::test(Index::class)
        ->call('edit', $role->id)
        ->assertSet('permissions', [Permission::ViewUnits->value])
        ->set('name', 'Gudang Utama')
        ->set('permissions', [Permission::CreateUnits->value])
        ->call('save')
        ->assertHasNoErrors();

    $role->refresh();
    expect($role->name)->toBe('Gudang Utama')
        ->and($role->permissions->pluck('name')->all())->toBe([Permission::CreateUnits->value]);
});

test('role names must be unique and permissions must exist', function () {
    Livewire::test(Index::class)
        ->call('create')
        ->set('name', 'staff')
        ->set('permissions', ['not.a.permission'])
        ->call('save')
        ->assertHasErrors(['name' => 'unique', 'permissions.0']);
});

test('the owner role can have permissions removed, and re-seeding keeps the change', function () {
    $owner = RoleModel::findByName(Role::Owner->value);
    $withoutUnitCreation = collect(Permission::cases())->reject(fn ($p) => $p === Permission::CreateUnits)->map->value->values()->all();

    Livewire::test(Index::class)
        ->call('edit', $owner->id)
        ->set('permissions', $withoutUnitCreation)
        ->call('save')
        ->assertHasNoErrors();

    (new RolesAndPermissionsSeeder)->run();

    $owner->refresh();
    expect($owner->name)->toBe(Role::Owner->value)
        ->and($owner->hasPermissionTo(Permission::CreateUnits->value))->toBeFalse()
        ->and(User::role(Role::Owner->value)->first()->can(Permission::CreateUnits))->toBeFalse();
});

test('staff permissions can be changed and survive re-seeding', function () {
    $staff = RoleModel::findByName(Role::Staff->value);

    Livewire::test(Index::class)
        ->call('edit', $staff->id)
        ->set('name', 'renamed')
        ->set('permissions', [Permission::ViewRentals->value])
        ->call('save')
        ->assertHasNoErrors();

    (new RolesAndPermissionsSeeder)->run();

    $staff->refresh();
    expect($staff->name)->toBe(Role::Staff->value)
        ->and($staff->permissions->pluck('name')->all())->toBe([Permission::ViewRentals->value]);
});

test('the superadmin role cannot be edited by anyone and always has every permission', function () {
    $superadminRole = RoleModel::findByName(Role::Superadmin->value);

    Livewire::test(Index::class)->call('edit', $superadminRole->id)->assertForbidden();

    $this->actingAs(User::factory()->superadmin()->create());
    Livewire::test(Index::class)->call('edit', $superadminRole->id)->assertForbidden();

    $superadminRole->syncPermissions([]); // even if the database is tampered with…
    (new RolesAndPermissionsSeeder)->run(); // …the seeder restores it
    expect($superadminRole->refresh()->permissions)->toHaveCount(count(Permission::cases()));
});

test('superadmins pass every permission check even if their role is missing one', function () {
    $superadmin = User::factory()->superadmin()->create();
    RoleModel::findByName(Role::Superadmin->value)->syncPermissions([]);

    expect(collect(Permission::cases())->every(fn (Permission $p) => $superadmin->fresh()->can($p)))->toBeTrue();
});

test('an owner missing some permissions can still edit staff', function () {
    $owner = RoleModel::findByName(Role::Owner->value);
    $owner->revokePermissionTo(Permission::ViewUnits->value); // staff still has it
    $staff = RoleModel::findByName(Role::Staff->value);

    $this->get(route('roles.index'))->assertSee('wire:click="edit('.$staff->id.')"', false);

    Livewire::test(Index::class)
        ->call('edit', $staff->id)
        ->set('permissions', [Permission::ViewUnits->value, Permission::CreateUnits->value])
        ->call('save')
        ->assertHasNoErrors();

    expect($staff->refresh()->permissions->pluck('name')->sort()->values()->all())
        ->toBe([Permission::CreateUnits->value, Permission::ViewUnits->value]);
});

test('anyone who can manage roles can edit every role except superadmin', function () {
    $limited = RoleModel::create(['name' => 'Admin Roles', 'guard_name' => 'web']);
    $limited->syncPermissions([Permission::ManageRoles->value]);
    $this->actingAs(tap(User::factory()->create())->syncRoles($limited));

    Livewire::test(Index::class)
        ->call('create')
        ->set('name', 'Gudang')
        ->set('permissions', [Permission::ViewUnits->value, Permission::CreateUnits->value])
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(Index::class)
        ->call('edit', RoleModel::findByName(Role::Owner->value)->id)
        ->assertHasNoErrors()
        ->assertSet('showFormModal', true);

    Livewire::test(Index::class)->call('edit', RoleModel::findByName(Role::Superadmin->value)->id)->assertForbidden();
});

test('built-in roles and roles in use cannot be deleted', function () {
    $staff = RoleModel::findByName(Role::Staff->value);
    $inUse = RoleModel::create(['name' => 'Kasir', 'guard_name' => 'web']);
    User::factory()->create()->syncRoles('Kasir');
    $unused = RoleModel::create(['name' => 'Lama', 'guard_name' => 'web']);

    Livewire::test(Index::class)
        ->call('delete', $staff->id)->assertSet('notice', 'Role bawaan tidak bisa dihapus.')
        ->call('delete', $inUse->id)->assertSet('notice', 'Role Kasir masih dipakai oleh 1 pengguna. Pindahkan mereka ke role lain dulu.')
        ->call('delete', $unused->id)->assertSet('notice', 'Role Lama dihapus.');

    expect(RoleModel::query()->pluck('name')->sort()->values()->all())->toBe(['Kasir', 'owner', 'staff', 'superadmin']);
});

test('staff cannot call role management actions directly', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Index::class)->call('create')->assertForbidden();
    Livewire::test(Index::class)->set('name', 'Hack')->call('save')->assertForbidden();
});
