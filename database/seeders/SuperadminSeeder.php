<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Creates the built-in roles and the superadmin account from config('app.superadmin') if it doesn't exist yet.
 * Safe to run any number of times; an existing account's password is never changed.
 *
 * Outside production the password defaults to "password"; in production SUPERADMIN_PASSWORD must be set.
 */
class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $email = config('app.superadmin.email');
        $password = config('app.superadmin.password');

        if (blank($password) && app()->isProduction() && User::query()->where('email', $email)->doesntExist()) {
            throw new RuntimeException('Set SUPERADMIN_PASSWORD in the .env before seeding the superadmin in production.');
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            ['name' => config('app.superadmin.name'), 'password' => $password ?: 'password'],
        );

        $user->syncRoles(Role::Superadmin);
    }
}
