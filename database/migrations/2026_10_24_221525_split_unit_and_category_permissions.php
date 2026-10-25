<?php

use App\Enums\Permission;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Split categories.manage into view/create/edit/delete, and add units.edit / units.delete.
     *
     * Existing roles keep what they could do: whoever could manage categories gets all four
     * category permissions, whoever could add units may now also edit and delete them, and
     * whoever could see units may now see categories too.
     * The seeder only grants defaults to roles it creates, so these grants are made here.
     */
    public function up(): void
    {
        $categoryManagers = $this->rolesWith('categories.manage');
        $unitCreators = $this->rolesWith(Permission::CreateUnits->value);
        $unitViewers = $this->rolesWith(Permission::ViewUnits->value);

        // Creates the new permissions and removes categories.manage.
        (new RolesAndPermissionsSeeder)->run();

        foreach ($categoryManagers as $role) {
            $role->givePermissionTo([
                Permission::ViewCategories->value,
                Permission::CreateCategories->value,
                Permission::EditCategories->value,
                Permission::DeleteCategories->value,
            ]);
        }

        foreach ($unitCreators as $role) {
            $role->givePermissionTo([Permission::EditUnits->value, Permission::DeleteUnits->value]);
        }

        foreach ($unitViewers as $role) {
            $role->givePermissionTo(Permission::ViewCategories->value);
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
