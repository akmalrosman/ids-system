<?php

namespace Tests\Feature;

use App\Enums\FdrStatus;
use App\Models\Flight;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresentationCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBasics();
    }

    public function test_card_without_bvr_has_no_video_toggle_and_starts_in_large_data_layout(): void
    {
        $flight = $this->presentableFlight();

        $this->actingAs($this->userWithRole(Role::SUPER_ADMIN))
            ->get(route('flights.present', $flight))
            ->assertOk()
            ->assertSee('data-video="off"', false)
            ->assertDontSee('data-card-action="video"', false)
            ->assertDontSee('No BVR video');
    }

    public function test_card_with_bvr_has_video_toggle(): void
    {
        $flight = $this->presentableFlight(['bvr_path' => 'bvr/1/cockpit.mp4', 'bvr_original_name' => 'cockpit.mp4']);

        $this->actingAs($this->userWithRole(Role::SUPER_ADMIN))
            ->get(route('flights.present', $flight))
            ->assertOk()
            ->assertSee('data-video="on"', false)
            ->assertSee('data-card-action="video"', false)
            ->assertSee('data-card-action="mute"', false);
    }

    /** A flight whose FDR is already converted, so it appears in the presentation. */
    private function presentableFlight(array $attributes = []): Flight
    {
        return Flight::create([
            'aircraft_id' => $this->makeAircraft()->id,
            'flight_number' => '4001',
            'flown_at' => '2025-05-18 16:00:00',
            'fdr_status' => FdrStatus::Ready,
            'telemetry_path' => 'telemetry/flight-1.json',
            ...$attributes,
        ]);
    }
}
