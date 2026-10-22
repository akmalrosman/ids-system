<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Local: super@email.com / password
     * Production: set ADMIN_EMAIL and ADMIN_PASSWORD in .env before seeding.
     */
    public function run(): void
    {
        $email = config('ids.admin.email');
        $password = config('ids.admin.password') ?? (app()->isProduction() ? null : 'password');

        if (! $password) {
            throw new RuntimeException('Set ADMIN_PASSWORD in .env before seeding production.');
        }

        User::firstOrCreate(['email' => $email], [
            'name' => 'Super Admin',
            'password' => $password,
            'role_id' => Role::where('slug', Role::SUPER_ADMIN)->value('id'),
        ]);
    }
}
