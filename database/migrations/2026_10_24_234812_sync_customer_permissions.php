<?php

use App\Enums\Permission;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Add customers.edit and customers.delete.
     *
     * Whoever could add customers may now also fix their details, and whoever could delete
     * categories (the owner, by default) may now delete customers too.
     * The seeder only grants defaults to roles it creates, so these grants are made here.
     */
    public function up(): void
    {
        $customerCreators = $this->rolesWith(Permission::CreateCustomers->value);
        $categoryDeleters = $this->rolesWith(Permission::DeleteCategories->value);

        (new RolesAndPermissionsSeeder)->run();

        foreach ($customerCreators as $role) {
            $role->givePermissionTo(Permission::EditCustomers->value);
        }

        foreach ($categoryDeleters as $role) {
            $role->givePermissionTo([Permission::EditCustomers->value, Permission::DeleteCustomers->value]);
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
