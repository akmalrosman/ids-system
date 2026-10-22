<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Support\Permission as P;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => Role::SUPER_ADMIN,
                'description' => 'Full access to everything.',
                'permissions' => [], // bypasses all checks
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Manages flights, groups, aircraft and users.',
                'permissions' => array_values(array_diff(P::all(), [P::ROLES_MANAGE])),
            ],
            [
                'name' => 'Engineer',
                'slug' => 'engineer',
                'description' => 'Uploads FDR / BVR files and maintains aircraft.',
                'permissions' => [
                    P::DASHBOARD_VIEW, P::FLIGHTS_VIEW, P::FLIGHTS_MANAGE, P::GROUPS_VIEW, P::GROUPS_MANAGE,
                    P::AIRCRAFT_VIEW, P::AIRCRAFT_MANAGE,
                ],
            ],
            [
                'name' => 'Pilot',
                'slug' => Role::PILOT,
                'description' => 'Views and presents debriefs.',
                'permissions' => [P::DASHBOARD_VIEW, P::FLIGHTS_VIEW, P::GROUPS_VIEW, P::AIRCRAFT_VIEW],
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
