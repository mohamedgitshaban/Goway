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
            'name' => 'Admin',
            'email' => $email,
            // store hashed password in password_hash to match AuthController usage
            'password_hash' => Hash::make('123456789'),
            'password' => Hash::make('123456789'),
            'usertype' => User::ROLE_ADMIN,
            'status' => 'active',
        ]);
    }
}
