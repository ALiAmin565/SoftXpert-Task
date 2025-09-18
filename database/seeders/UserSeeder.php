<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a manager user
        User::create([
            'name' => 'John Manager',
            'email' => 'manager@softxpert.com',
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'email_verified_at' => now(),
        ]);

        // Create another manager
        User::create([
            'name' => 'Sarah Admin',
            'email' => 'admin@softxpert.com',
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'email_verified_at' => now(),
        ]);

        // Create regular users
        User::create([
            'name' => 'Alice Developer',
            'email' => 'alice@softxpert.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Bob Designer',
            'email' => 'bob@softxpert.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Charlie Tester',
            'email' => 'charlie@softxpert.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Diana DevOps',
            'email' => 'diana@softxpert.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        // Create additional test users
        User::factory(10)->create();
    }
}
