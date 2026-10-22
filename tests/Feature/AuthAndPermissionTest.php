<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBasics();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/')->assertOk()->assertSee('Integrated');
    }

    public function test_user_can_log_in_and_out(): void
    {
        $user = $this->userWithRole(Role::SUPER_ADMIN);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = $this->userWithRole(Role::PILOT);

        $this->post('/login', ['email' => $user->email, 'password' => 'nope'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_pilot_can_view_but_not_manage(): void
    {
        $pilot = $this->userWithRole(Role::PILOT);

        $this->actingAs($pilot)->get('/dashboard')->assertOk();
        $this->actingAs($pilot)->get('/flights')->assertOk();
        $this->actingAs($pilot)->get('/flights/create')->assertForbidden();
        $this->actingAs($pilot)->get('/users')->assertForbidden();
        $this->actingAs($pilot)->get('/roles')->assertForbidden();
    }

    public function test_super_admin_can_open_every_settings_page(): void
    {
        $admin = $this->userWithRole(Role::SUPER_ADMIN);

        foreach (['/aircrafts', '/aircraft-types', '/users', '/roles', '/profile', '/groups/create', '/flights/create'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_role_in_use_cannot_be_deleted(): void
    {
        $admin = $this->userWithRole(Role::SUPER_ADMIN);
        $pilotRole = Role::where('slug', Role::PILOT)->first();
        $this->userWithRole(Role::PILOT);

        $this->actingAs($admin)->delete("/roles/{$pilotRole->id}")->assertSessionHas('error');
        $this->assertModelExists($pilotRole);
    }
}
