<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Tạo tài khoản quản trị ban đầu. Đặt ADMIN_EMAIL và ADMIN_INITIAL_PASSWORD trong .env (production).
 */
class PpmsSeeder extends Seeder
{
    public function run(): void
    {
        $email = strtolower(trim((string) env('ADMIN_EMAIL', 'admin@va-schools.vn')));
        $password = (string) env('ADMIN_INITIAL_PASSWORD', 'password');

        User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator',
                'password' => $password,
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
    }
}
