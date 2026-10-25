<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role as RoleModel;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(RoleModel::class, RolePolicy::class);

        // Superadmins pass every permission check, even for a permission added to the enum before the
        // seeder has synced it. Policies (who may edit/delete whom) are deliberately not bypassed.
        Gate::before(function (User $user, mixed $ability): ?bool {
            return is_string($ability) && Permission::tryFrom($ability) !== null && $user->isSuperadmin() ? true : null;
        });
    }
}
