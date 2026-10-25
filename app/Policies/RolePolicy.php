<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * Who may edit, delete and hand out which roles.
 *
 * Anyone with "Kelola role & izin" may edit any role except the superadmin role, which is fixed.
 * Only superadmins may hand out the superadmin role.
 */
class RolePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::ManageRoles);
    }

    public function create(User $actor): bool
    {
        return $actor->can(Permission::ManageRoles);
    }

    public function update(User $actor, RoleModel $role): bool
    {
        return $actor->can(Permission::ManageRoles)
            && ! Role::tryFrom($role->name)?->hasLockedPermissions();
    }

    public function delete(User $actor, RoleModel $role): bool
    {
        return Role::tryFrom($role->name) === null && $this->update($actor, $role);
    }

    /**
     * Whether the actor may give this role to a user.
     */
    public function assign(User $actor, RoleModel $role): bool
    {
        return $actor->isSuperadmin() || $role->name !== Role::Superadmin->value;
    }
}
