<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the roles/permissions, move each user's old `role` column value into a role, then drop the column.
     */
    public function up(): void
    {
        (new RolesAndPermissionsSeeder)->run();

        DB::table('users')->select(['id', 'role'])->orderBy('id')->each(function (object $row): void {
            User::query()->find($row->id)?->assignRole($row->role ?: 'staff');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    /**
     * Restore the `role` column from each user's first role.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('staff')->after('password');
        });

        User::query()->with('roles')->each(function (User $user): void {
            DB::table('users')->where('id', $user->id)->update(['role' => $user->roles->first()->name ?? 'staff']);
        });
    }
};
