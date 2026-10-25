<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

/**
 * Who may manage which user accounts.
 *
 * Anyone with "Kelola pengguna" may manage every account except superadmin accounts,
 * which only superadmins may touch.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::ManageUsers);
    }

    public function create(User $actor): bool
    {
        return $actor->can(Permission::ManageUsers);
    }

    public function update(User $actor, User $target): bool
    {
        if (! $actor->can(Permission::ManageUsers)) {
            return false;
        }

        return $actor->isSuperadmin() || $actor->is($target) || ! $target->isSuperadmin();
    }

    public function delete(User $actor, User $target): bool
    {
        return ! $actor->is($target) && $this->update($actor, $target);
    }
}
