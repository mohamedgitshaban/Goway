<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = 'admin@example.com';
        if (User::where('email', $email)->exists()) {
            return;
        }

        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => $email,
            'phone' => '01234567891',
            'password' => Hash::make('123456789'),
            'usertype' => User::ROLE_ADMIN,
            'status' => 'active',
        ]);
    }
}
