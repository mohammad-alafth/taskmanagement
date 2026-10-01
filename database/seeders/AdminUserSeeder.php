<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates/updates the initial admin user from env vars.
 * Idempotent: safe to run on every deploy (`db:seed --force`).
 *
 * Env: ADMIN_EMAIL, ADMIN_PASSWORD, ADMIN_NAME
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@example.com');
        $password = env('ADMIN_PASSWORD', 'password');
        $name = env('ADMIN_NAME', 'Administrator');

        if (empty($email) || empty($password)) {
            return;
        }

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]
        );
    }
}
