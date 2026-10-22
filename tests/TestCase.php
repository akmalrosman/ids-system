<?php

namespace Tests;

use App\Models\Aircraft;
use App\Models\AircraftType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AircraftTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Seeds roles + aircraft types (call from setUp in tests that need them). */
    protected function seedBasics(): void
    {
        $this->seed([RoleSeeder::class, AircraftTypeSeeder::class]);
    }

    protected function userWithRole(string $slug): User
    {
        return User::factory()->create(['role_id' => Role::where('slug', $slug)->value('id')]);
    }

    protected function makeAircraft(string $number = '5201'): Aircraft
    {
        return Aircraft::create([
            'aircraft_type_id' => AircraftType::firstOrFail()->id,
            'number' => $number,
        ]);
    }
}
