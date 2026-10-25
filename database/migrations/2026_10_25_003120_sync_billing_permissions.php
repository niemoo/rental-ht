<?php

use App\Enums\Permission;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Add rentals.edit, rentals.cancel, rentals.discount, rentals.charges, payments.record and payments.delete.
     *
     * Whoever could book rentals may now also change them, give discounts, add extra charges and record
     * payments; whoever could delete categories (the owner, by default) may also cancel rentals and delete
     * payments. The seeder only grants defaults to roles it creates, so these grants are made here.
     */
    public function up(): void
    {
        $rentalCreators = $this->rolesWith(Permission::CreateRentals->value);
        $categoryDeleters = $this->rolesWith(Permission::DeleteCategories->value);

        (new RolesAndPermissionsSeeder)->run();

        foreach ($rentalCreators as $role) {
            $role->givePermissionTo([
                Permission::EditRentals->value,
                Permission::GiveDiscounts->value,
                Permission::ManageCharges->value,
                Permission::RecordPayments->value,
            ]);
        }

        foreach ($categoryDeleters as $role) {
            $role->givePermissionTo([Permission::CancelRentals->value, Permission::DeletePayments->value]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Nothing to undo: re-running the seeder after rolling back the enum restores the old set.
     */
    public function down(): void
    {
        //
    }

    /**
     * @return list<RoleModel>
     */
    private function rolesWith(string $permission): array
    {
        return RoleModel::query()
            ->where('guard_name', 'web')
            ->whereHas('permissions', fn ($query) => $query->where('name', $permission))
            ->get()
            ->all();
    }
};
