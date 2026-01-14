<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create dummy users for testing login
        \App\Models\User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'phone' => '081234567890',
            'user_type' => 'admin',
        ]);

        \App\Models\User::factory()->create([
            'name' => 'Student User',
            'email' => 'student@test.com',
            'phone' => '081234567891',
            'user_type' => 'student',
        ]);

        \App\Models\User::factory()->create([
            'name' => 'Teacher User',
            'email' => 'teacher@test.com',
            'phone' => '081234567892',
            'user_type' => 'teacher',
        ]);

        // Create additional random users
        \App\Models\User::factory(10)->create();

        // Enable 2FA for test users
        $this->call(TwoFactorSeeder::class);
    }
}
