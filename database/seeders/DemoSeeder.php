<?php

namespace Database\Seeders;

use App\Enums\FdrStatus;
use App\Jobs\ProcessFdrFile;
use App\Models\Aircraft;
use App\Models\AircraftType;
use App\Models\Flight;
use App\Models\FlightGroup;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Demo data for development: pilots, aircraft, and flights that use the
 * dummy DJI logs in /database/sample-data/dji (regenerate them with
 * `npm run fdr:dummy`). The FDR files are converted right away, so the
 * presentation works straight after seeding.
 */
class DemoSeeder extends Seeder
{
    private const SAMPLE_DIR = 'database/sample-data/dji';

    public function run(): void
    {
        $pilots = $this->seedPilots();
        $this->seedAircraft();

        $admin = User::where('email', config('ids.admin.email'))->first();

        $lima = [
            ['5201', '4001', 3, 1, 'DJIFlightRecord_2025-05-18_[16-00-00]_5201.txt'],
            ['5205', '4002', 6, 5, 'DJIFlightRecord_2025-05-18_[16-00-00]_5205.txt'],
            ['5207', '4003', 8, 7, 'DJIFlightRecord_2025-05-18_[16-00-00]_5207.txt'],
            ['5210', '4004', 4, 9, 'DJIFlightRecord_2025-05-18_[16-00-00]_5210.txt'],
            ['5211', '4005', 3, 10, 'DJIFlightRecord_2025-05-18_[16-00-00]_5211.txt'],
            ['5216', '4006', 2, 1, 'DJIFlightRecord_2025-05-18_[16-00-00]_5216.txt'],
        ];

        $limaFlights = collect($lima)->map(fn (array $row) => $this->seedFlight([
            'aircraft' => $row[0],
            'flight_number' => $row[1],
            'pilot' => $pilots[$row[2]],
            'wso' => $pilots[$row[3]],
            'shift_number' => 1,
            'location' => 'Langkawi',
            'flown_at' => '2025-05-18 16:00:00',
            'fdr' => $row[4],
        ], $admin));

        $subang = $this->seedFlight([
            'aircraft' => '5203', 'flight_number' => '2', 'pilot' => $pilots[2], 'wso' => $pilots[5],
            'shift_number' => 3, 'location' => 'Subang', 'flown_at' => '2025-09-23 10:00:00',
            'fdr' => 'DJIFlightRecord_2025-09-23_[10-00-00]_5203.txt',
        ], $admin);

        $gongKedak = $this->seedFlight([
            'aircraft' => '5210', 'flight_number' => '3', 'pilot' => $pilots[2], 'wso' => $pilots[7],
            'shift_number' => 3, 'location' => 'Gong Kedak', 'flown_at' => '2025-11-25 08:00:00',
            'fdr' => 'DJIFlightRecord_2025-11-25_[08-00-00]_5210.txt',
        ], $admin);

        $this->seedGroup('LIMA 2025', 'Langkawi', '2025-05-18 08:00:00', 'Seeded formation for LIMA 2025 exercise', $limaFlights->all(), $admin);
        $this->seedGroup('Formation 2', 'Subang', '2024-11-29 08:00:00', 'Orbit and figure-eight practice', [$subang], $admin);
        $this->seedGroup('Formation 1', 'Gong Kedak', '2024-09-23 08:00:00', 'Area survey pattern', [$gongKedak], $admin);
    }

    /** @return array<int, User> keyed by pilot number */
    private function seedPilots(): array
    {
        $roleId = Role::where('slug', Role::PILOT)->value('id');
        $pilots = [];

        foreach (range(1, 10) as $n) {
            $pilots[$n] = User::firstOrCreate(['email' => "pilot{$n}@email.com"], [
                'name' => "Pilot {$n}",
                'password' => 'password',
                'role_id' => $roleId,
            ]);
        }

        return $pilots;
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

    private function seedFlight(array $data, ?User $admin): Flight
    {
        $flight = Flight::create([
            'aircraft_id' => Aircraft::where('number', $data['aircraft'])->value('id'),
            'pilot_id' => $data['pilot']->id,
            'wso_id' => $data['wso']->id,
            'flight_number' => $data['flight_number'],
            'shift_number' => $data['shift_number'],
            'location' => $data['location'],
            'flown_at' => $data['flown_at'],
            'created_by' => $admin?->id,
        ]);

        $source = base_path(self::SAMPLE_DIR.'/'.$data['fdr']);

        if (! File::exists($source)) {
            $this->command?->warn("Missing {$data['fdr']} — run `npm run fdr:dummy` then seed again.");

            return $flight;
        }

        $path = "fdr/{$flight->id}/".$data['fdr'];
        Storage::disk(config('ids.disk'))->put($path, File::get($source));
        $flight->update([
            'fdr_path' => $path,
            'fdr_original_name' => $data['fdr'],
            'fdr_status' => FdrStatus::Pending,
        ]);

        // Convert now (not on the queue) so the demo is ready immediately.
        ProcessFdrFile::dispatchSync($flight, $path);
        $flight->refresh();

        if ($flight->fdr_status === FdrStatus::Failed) {
            $this->command?->error("Flight {$flight->flight_number}: {$flight->fdr_error}");
        }

        return $flight;
    }

    /** @param  Flight[]  $flights */
    private function seedGroup(string $title, string $location, string $date, string $description, array $flights, ?User $admin): void
    {
        $group = FlightGroup::create([
            'title' => $title,
            'location' => $location,
            'flown_at' => $date,
            'description' => $description,
            'created_by' => $admin?->id,
        ]);

        $group->flights()->attach(collect($flights)->pluck('id'));
    }
}
