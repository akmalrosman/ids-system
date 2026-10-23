<?php

namespace Tests\Feature;

use App\Enums\FdrStatus;
use App\Models\Flight;
use App\Models\FlightGroup;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * End-to-end FDR pipeline: upload -> queued job (sync in tests) -> Node.js parser -> telemetry JSON.
 * Requires Node.js on the PATH, like the real app.
 */
class FlightFdrTest extends TestCase
{
    use RefreshDatabase;

    private const SAMPLE = 'database/sample-data/dji/DJIFlightRecord_2025-05-18_[16-00-00]_5201.txt';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBasics();
        Storage::fake(config('ids.disk'));
    }

    public function test_uploading_a_dji_log_produces_telemetry(): void
    {
        $admin = $this->userWithRole(Role::SUPER_ADMIN);
        $aircraft = $this->makeAircraft();

        $response = $this->actingAs($admin)->post('/flights', [
            'aircraft_id' => $aircraft->id,
            'flight_number' => '4001',
            'shift_number' => 1,
            'location' => 'Langkawi',
            'flown_at' => '2025-05-18 16:00:00',
            'fdr' => new UploadedFile(base_path(self::SAMPLE), 'DJIFlightRecord_test.txt', 'text/plain', null, true),
        ]);

        $flight = Flight::firstOrFail();
        $response->assertRedirect("/flights/{$flight->id}");

        $this->assertSame(FdrStatus::Ready, $flight->fdr_status, (string) $flight->fdr_error);
        $this->assertEqualsWithDelta(918.4, $flight->duration_seconds, 1);
        $this->assertSame('2025-05-18T16:00:00.000+08:00', $flight->stats['start_time']);
        Storage::disk(config('ids.disk'))->assertExists($flight->telemetry_path);

        $this->actingAs($admin)->getJson("/flights/{$flight->id}/telemetry")
            ->assertOk()
            ->assertJsonPath('format', 'ids-telemetry')
            ->assertJsonPath('source.adapter', 'dji-csv');

        $this->actingAs($admin)->get("/flights/{$flight->id}")->assertOk()->assertSee('Present Flight');
        $this->actingAs($admin)->get("/flights/{$flight->id}/present")->assertOk()->assertSee('presentation-config', false);
    }

    public function test_unrecognised_file_is_marked_failed_with_reason(): void
    {
        $admin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->actingAs($admin)->post('/flights', [
            'aircraft_id' => $this->makeAircraft()->id,
            'flight_number' => '1',
            'flown_at' => now()->toDateTimeString(),
            'fdr' => UploadedFile::fake()->createWithContent('notes.txt', "hello,world\n1,2\n"),
        ]);

        $flight = Flight::firstOrFail();
        $this->assertSame(FdrStatus::Failed, $flight->fdr_status);
        $this->assertStringContainsString('UNKNOWN_FORMAT', $flight->fdr_error);
        $this->actingAs($admin)->getJson("/flights/{$flight->id}/telemetry")->assertNotFound();
    }

    public function test_wrong_extension_is_rejected(): void
    {
        $admin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->actingAs($admin)->post('/flights', [
            'aircraft_id' => $this->makeAircraft()->id,
            'flight_number' => '1',
            'flown_at' => now()->toDateTimeString(),
            'fdr' => UploadedFile::fake()->create('virus.exe', 10),
        ])->assertSessionHasErrors('fdr');

        $this->assertDatabaseCount('flights', 0);
    }

    public function test_group_with_flights_can_be_created_and_presented(): void
    {
        $admin = $this->userWithRole(Role::SUPER_ADMIN);
        $flight = Flight::create([
            'aircraft_id' => $this->makeAircraft()->id,
            'flight_number' => '7',
            'flown_at' => '2025-05-18 16:00:00',
        ]);

        $this->actingAs($admin)->post('/groups', [
            'title' => 'LIMA 2025',
            'location' => 'Langkawi',
            'flown_at' => '2025-05-18 08:00:00',
            'flight_ids' => [$flight->id],
        ])->assertRedirect();

        $group = FlightGroup::firstOrFail();
        $this->assertTrue($group->flights->contains($flight));

        // No telemetry yet: the page still opens and lists the missing flight.
        $this->actingAs($admin)->get("/groups/{$group->id}/present")->assertOk()->assertSee('Not shown');
    }

    public function test_deleting_a_flight_removes_its_files(): void
    {
        $admin = $this->userWithRole(Role::SUPER_ADMIN);
        $flight = Flight::create(['aircraft_id' => $this->makeAircraft()->id, 'flight_number' => '9', 'flown_at' => now()]);
        Storage::disk(config('ids.disk'))->put("fdr/{$flight->id}/log.txt", 'x');

        $this->actingAs($admin)->delete("/flights/{$flight->id}")->assertRedirect('/flights');

        $this->assertModelMissing($flight);
        Storage::disk(config('ids.disk'))->assertMissing("fdr/{$flight->id}/log.txt");
    }
}
