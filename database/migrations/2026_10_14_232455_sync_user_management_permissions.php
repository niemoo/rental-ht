<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Add the users.manage and roles.manage permissions and give them to the owner role.
     */
    public function up(): void
    {
        (new RolesAndPermissionsSeeder)->run();
    }

    /**
     * Nothing to undo: re-running the seeder after rolling back the enum removes them.
     */
    public function down(): void
    {
        //
    }
};
