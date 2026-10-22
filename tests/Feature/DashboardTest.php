<?php

namespace Tests\Feature;

use App\Enums\FdrStatus;
use App\Models\Flight;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBasics();
    }

    public function test_fdr_status_counts_are_shown(): void
    {
        $aircraftId = $this->makeAircraft()->id;
        $statuses = [FdrStatus::Ready, FdrStatus::Ready, FdrStatus::Processing, FdrStatus::Failed];

        foreach ($statuses as $i => $status) {
            Flight::create([
                'aircraft_id' => $aircraftId,
                'flight_number' => (string) ($i + 1),
                'flown_at' => now(),
                'fdr_status' => $status,
                'duration_seconds' => 600,
            ]);
        }

        $this->actingAs($this->userWithRole(Role::SUPER_ADMIN))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('2 / 4')
            ->assertSee('2 ready')
            ->assertSee('1 processing')
            ->assertSee('1 failed')
            ->assertSee('Average 10 min 0 sec per flight');
    }

    public function test_dashboard_works_without_any_flights(): void
    {
        $this->actingAs($this->userWithRole(Role::PILOT))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('0 / 0')
            ->assertDontSee('Average');
    }
}
