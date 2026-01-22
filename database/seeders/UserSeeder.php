<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin (must match AUTHORIZED_ADMIN_EMAIL in env for protection)
        User::create([
            'name' => 'Admin',
            'email' => 'admin@agrotrade.local',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Approved farmer
        User::create([
            'name' => 'Farmer Joe',
            'email' => 'farmer@example.com',
            'password' => Hash::make('password'),
            'role' => 'farmer',
            'status' => 'active',
            'location' => 'Green Valley',
        ]);

        // Pending store owner
        User::create([
            'name' => 'Store Ahmed',
            'email' => 'store@example.com',
            'password' => Hash::make('password'),
            'role' => 'store_owner',
            'status' => 'pending',
            'location' => 'Algiers',
        ]);

        // Client
        User::create([
            'name' => 'Client User',
            'email' => 'client@example.com',
            'password' => Hash::make('password'),
            'role' => 'client',
            'status' => 'active',
        ]);
    }
}
