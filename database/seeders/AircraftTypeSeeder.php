<?php

namespace Database\Seeders;

use App\Models\AircraftType;
use Illuminate\Database\Seeder;

class AircraftTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Without an uploaded model both use public/models/su30.glb.
        AircraftType::updateOrCreate(['name' => 'Sukhoi Su-30MKM'], [
            'manufacturer' => 'Sukhoi',
            'model' => 'Su-30MKM',
            'description' => 'Twin-seat multirole fighter.',
            'model_length_m' => 21.9,
            'model_yaw_offset_deg' => -90,
        ]);

        AircraftType::updateOrCreate(['name' => 'MiG MiG-29'], [
            'manufacturer' => 'MiG',
            'model' => 'MiG-29',
            'description' => 'Single-seat air superiority fighter.',
            'model_length_m' => 17.3,
            'model_yaw_offset_deg' => -90,
        ]);
    }
}
