<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan migrate:fresh --seed
     *
     * Production only gets roles, aircraft types and the admin account.
     * Local/dev also gets demo pilots, aircraft and the real DJI sample flights.
     * (Production can add those flights with `php artisan db:seed --class=SampleFlightSeeder --force`.)
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
            AircraftTypeSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call(DemoSeeder::class);
        }
    }
}
