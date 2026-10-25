<?php

use App\Enums\Permission;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Add reports.view. Reports show revenue, so by default only whoever could delete categories
     * (the owner) gets it. The seeder only grants defaults to roles it creates, so the grant is made here.
     */
    public function up(): void
    {
        $categoryDeleters = RoleModel::query()
            ->where('guard_name', 'web')
            ->whereHas('permissions', fn ($query) => $query->where('name', Permission::DeleteCategories->value))
            ->get();

        (new RolesAndPermissionsSeeder)->run();

        foreach ($categoryDeleters as $role) {
            $role->givePermissionTo(Permission::ViewReports->value);
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
};
