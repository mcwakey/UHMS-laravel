<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@uhms.local'],
            [
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'email' => 'admin@uhms.local',
                'phone' => '0200000000',
                'password' => Hash::make('password'),
                'gender' => Gender::MALE,
                'status' => UserStatus::ACTIVE,
                'employee_id' => 'EMP-0001',
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('Super Admin');
    }
}
