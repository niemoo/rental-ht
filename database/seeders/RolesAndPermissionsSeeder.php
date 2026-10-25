<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Makes the permissions table match the App\Enums\Permission enum and makes sure the built-in roles exist.
 *
 * Safe to run any number of times:
 * - permissions no longer in the enum are deleted;
 * - built-in roles get their default permissions only when first created, so changes made on the
 *   Roles screen survive — except the superadmin, who is always re-synced to every permission.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        PermissionModel::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', array_column(Permission::cases(), 'value'))
            ->delete();

        foreach (Role::cases() as $role) {
            $model = RoleModel::query()->where(['name' => $role->value, 'guard_name' => 'web'])->first();

            if ($model === null || $role->hasLockedPermissions()) {
                ($model ?? RoleModel::create(['name' => $role->value, 'guard_name' => 'web']))
                    ->syncPermissions(array_column($role->defaultPermissions(), 'value'));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
