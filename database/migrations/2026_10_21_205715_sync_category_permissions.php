<?php

use App\Enums\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;

return new class extends Migration
{
    /**
     * Add the categories.manage permission. The seeder only grants defaults to roles it creates,
     * so the existing owner role is given it here.
     *
     * categories.manage was later split into categories.view/create/edit/delete (see
     * split_unit_and_category_permissions), so on a fresh install it no longer exists here.
     */
    public function up(): void
    {
        (new RolesAndPermissionsSeeder)->run();

        if (PermissionModel::query()->where(['name' => 'categories.manage', 'guard_name' => 'web'])->exists()) {
            RoleModel::query()->where(['name' => Role::Owner->value, 'guard_name' => 'web'])->first()
                ?->givePermissionTo('categories.manage');
        }
    }

    /**
     * Nothing to undo: re-running the seeder after rolling back the enum removes it.
     */
    public function down(): void
    {
        //
    }
};
