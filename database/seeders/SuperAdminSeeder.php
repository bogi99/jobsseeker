<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the superadmin user type
        $superAdminType = UserType::where('name', 'superadmin')->first();

        if (! $superAdminType) {
            $this->command->error('Superadmin user type not found. Please run migrations first.');

            return;
        }

        // Check if superadmin already exists
        $existingSuperAdmin = User::where('email', 'superadmin@jobrat.ca')->first();

        if ($existingSuperAdmin) {
            $this->command->info('Superadmin user already exists.');

            return;
        }

        // Create the superadmin user
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@jobrat.ca',
            'password' => Hash::make('password'),
            'usertype_id' => $superAdminType->id,
            'email_verified_at' => now(),
        ]);

        $this->command->info('Superadmin user created successfully!');
        $this->command->info('Email: superadmin@jobrat.ca');
        $this->command->info('Password: password');
    }
}
