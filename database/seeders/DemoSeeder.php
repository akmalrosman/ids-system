<?php

namespace Database\Seeders;

use App\Models\Aircraft;
use App\Models\AircraftType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Development data: pilot accounts and the Su-30MKM fleet, plus the real DJI
 * flights from SampleFlightSeeder so the presentation works straight after seeding.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPilots();
        $this->seedAircraft();

        $this->call(SampleFlightSeeder::class);
    }

    private function seedPilots(): void
    {
        $roleId = Role::where('slug', Role::PILOT)->value('id');

        foreach (range(1, 10) as $n) {
            User::firstOrCreate(['email' => "pilot{$n}@email.com"], [
                'name' => "Pilot {$n}",
                'password' => 'password',
                'role_id' => $roleId,
            ]);
        }
    }

    private function seedAircraft(): void
    {
        $su30 = AircraftType::where('model', 'Su-30MKM')->firstOrFail();

        foreach (range(5201, 5218) as $i => $number) {
            Aircraft::firstOrCreate(['number' => (string) $number], [
                'aircraft_type_id' => $su30->id,
                'registration' => 'M52-'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'serial' => strtoupper(substr(md5((string) $number), 0, 9)),
            ]);
        }
    }
}
