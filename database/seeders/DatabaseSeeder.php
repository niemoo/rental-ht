<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Production gets only the built-in roles and the superadmin account; every other environment
     * also gets the demo data (which wipes and recreates rentals, units and customers).
     */
    public function run(): void
    {
        $this->call(SuperadminSeeder::class);

        if (! app()->isProduction()) {
            $this->call(DemoSeeder::class);
        }
    }
}
