<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the superadmin account (superadmin@gmail.com / password) if it doesn't exist yet.
 * Safe to run any number of times; an existing account's password is never changed.
 *
 * Change this password straight after the first login on any real server.
 */
class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $user = User::query()->firstOrCreate(
            ['email' => 'superadmin@gmail.com'],
            ['name' => 'Superadmin', 'password' => 'password'],
        );

        $user->syncRoles(Role::Superadmin);
    }
}
