<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create 1 Super Admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'usertype_id' => 1, // superadmin
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create 20 Customer users
        for ($i = 1; $i <= 20; $i++) {
            User::create([
                'name' => fake()->name(),
                'email' => 'customer'.$i.'@example.com',
                'usertype_id' => 3, // customer
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }

        // Create 20 Job Seeker users
        for ($i = 1; $i <= 20; $i++) {
            User::create([
                'name' => fake()->name(),
                'email' => 'jobseeker'.$i.'@example.com',
                'usertype_id' => 4, // jobseeker
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }
    }
}
