<?php

use App\Enums\Permission;
use App\Models\Rental;
use App\Models\RentalPayment;
use App\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * For each permission: where its links/buttons appear, and which pages it guards.
 * The match has no default arm, so adding a Permission case without a row here fails the suite.
 *
 * @return array{visible: list<array{0: string, 1: array<int, mixed>, 2: string}>, forbidden: list<array{0: string, 1: array<int, mixed>}>}
 */
function gatedBy(Permission $permission, Rental $rental): array
{
    $link = fn (string $route, array $params = []): string => 'href="'.route($route, $params).'"';
    $unit = $rental->items()->firstOrFail()->unit;
    $category = $unit->category;
    $customer = $rental->customer;

    return match ($permission) {
        Permission::ViewUnits => [
            'visible' => [['dashboard', [], $link('units.index')]],
            'forbidden' => [['units.index', []]],
        ],
        Permission::CreateUnits => [
            'visible' => [['units.index', [], $link('units.create')]],
            'forbidden' => [['units.create', []]],
        ],
        Permission::EditUnits => [
            'visible' => [['units.index', [], $link('units.edit', [$unit])]],
            'forbidden' => [['units.edit', [$unit]]],
        ],
        Permission::DeleteUnits => [
            'visible' => [['units.index', [], 'Hapus unit']],
            'forbidden' => [],
        ],
        Permission::ViewCategories => [
            'visible' => [['dashboard', [], $link('categories.index')]],
            'forbidden' => [['categories.index', []]],
        ],
        Permission::CreateCategories => [
            'visible' => [['categories.index', [], $link('categories.create')]],
            'forbidden' => [['categories.create', []]],
        ],
        Permission::EditCategories => [
            'visible' => [['categories.index', [], $link('categories.edit', [$category])]],
            'forbidden' => [['categories.edit', [$category]]],
        ],
        Permission::DeleteCategories => [
            'visible' => [['categories.index', [], 'Hapus kategori']],
            'forbidden' => [],
        ],
        Permission::ViewCustomers => [
            'visible' => [['dashboard', [], $link('customers.index')]],
            'forbidden' => [['customers.index', []], ['customers.show', [$customer]]],
        ],
        Permission::CreateCustomers => [
            'visible' => [['customers.index', [], $link('customers.create')], ['rentals.create', [], 'Pelanggan baru']],
            'forbidden' => [['customers.create', []]],
        ],
        Permission::EditCustomers => [
            'visible' => [['customers.index', [], $link('customers.edit', [$customer])], ['customers.show', [$customer], $link('customers.edit', [$customer])]],
            'forbidden' => [['customers.edit', [$customer]]],
        ],
        Permission::DeleteCustomers => [
            'visible' => [['customers.index', [], 'Hapus pelanggan'], ['customers.show', [$customer], 'Hapus pelanggan']],
            'forbidden' => [],
        ],
        Permission::ViewRentals => [
            'visible' => [['dashboard', [], $link('rentals.index')], ['dashboard', [], $link('rentals.show', [$rental])]],
            'forbidden' => [['rentals.index', []], ['rentals.show', [$rental]]],
        ],
        Permission::CreateRentals => [
            'visible' => [['dashboard', [], $link('rentals.create')]],
            'forbidden' => [['rentals.create', []]],
        ],
        Permission::EditRentals => [
            'visible' => [['rentals.index', [], $link('rentals.edit', [$rental])], ['rentals.show', [$rental], $link('rentals.edit', [$rental])]],
            'forbidden' => [['rentals.edit', [$rental]]],
        ],
        Permission::ReturnRentals => [
            'visible' => [['rentals.show', [$rental], 'Kembalikan']],
            'forbidden' => [],
        ],
        Permission::CancelRentals => (function () use ($link): array {
            $upcoming = Rental::factory()->between(3, 4)->withUnits()->create(); // only rentals not yet started can be cancelled

            return [
                'visible' => [['rentals.show', [$upcoming], $link('rentals.cancel', [$upcoming])]],
                'forbidden' => [['rentals.cancel', [$upcoming]]],
            ];
        })(),
        Permission::GiveDiscounts => [
            'visible' => [['rentals.edit', [$rental], 'Diskon (opsional)'], ['rentals.create', [], 'Diskon (opsional)']],
            'forbidden' => [],
        ],
        Permission::ManageCharges => [
            'visible' => [['rentals.show', [$rental], $link('rentals.charges.create', [$rental])]],
            'forbidden' => [['rentals.charges.create', [$rental]]],
        ],
        Permission::RecordPayments => [
            'visible' => [['rentals.show', [$rental], $link('rentals.payments.create', [$rental])]],
            'forbidden' => [['rentals.payments.create', [$rental]]],
        ],
        Permission::DeletePayments => (function () use ($rental): array {
            RentalPayment::factory()->for($rental)->create();

            return [
                'visible' => [['rentals.show', [$rental], 'Hapus pembayaran']],
                'forbidden' => [],
            ];
        })(),
        Permission::ViewReports => [
            'visible' => [['dashboard', [], $link('reports.revenue')]],
            'forbidden' => [['reports.revenue', []], ['reports.receivables', []], ['reports.rentals', []], ['reports.print', ['revenue']]],
        ],
        Permission::ManageUsers => [
            'visible' => [['dashboard', [], $link('users.index')]],
            'forbidden' => [['users.index', []], ['users.create', []]],
        ],
        Permission::ManageRoles => [
            'visible' => [['dashboard', [], $link('roles.index')]],
            'forbidden' => [['roles.index', []]],
        ],
    };
}

/**
 * A user whose role has every permission except $missing (or every permission when null).
 */
function userWithout(?Permission $missing): User
{
    $role = RoleModel::create(['name' => 'Uji '.($missing->value ?? 'semua'), 'guard_name' => 'web']);
    $role->syncPermissions(collect(Permission::cases())->reject(fn (Permission $p) => $p === $missing)->map->value->all());

    $user = User::factory()->create();
    $user->syncRoles($role);

    return $user;
}

beforeEach(function () {
    $this->rental = Rental::factory()->withUnits()->create(); // out now, so "Kembalikan" can appear
});

test('without a permission its links and buttons are hidden and its pages are forbidden', function (Permission $permission) {
    $this->actingAs(userWithout($permission));
    $gated = gatedBy($permission, $this->rental);

    foreach ($gated['visible'] as [$route, $params, $needle]) {
        if (in_array([$route, $params], array_map(fn ($f) => [$f[0], $f[1]], $gated['forbidden']), true)) {
            continue; // the page itself is forbidden, so there is nothing to look at
        }

        $this->get(route($route, $params))->assertOk()->assertDontSee($needle, false);
    }

    foreach ($gated['forbidden'] as [$route, $params]) {
        $this->get(route($route, $params))->assertForbidden();
    }
})->with(Permission::cases());

test('with the permission the same links and buttons are shown and the pages open', function (Permission $permission) {
    $this->actingAs(userWithout(null));
    $gated = gatedBy($permission, $this->rental);

    foreach ($gated['visible'] as [$route, $params, $needle]) {
        $this->get(route($route, $params))->assertOk()->assertSee($needle, false);
    }

    foreach ($gated['forbidden'] as [$route, $params]) {
        $this->get(route($route, $params))->assertOk();
    }
})->with(Permission::cases());
