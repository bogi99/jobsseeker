<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Ensure user types exist before users are created
            UserTypeSeeder::class,
            UserSeeder::class,
            TagSeeder::class,
            PostSeeder::class,
            SuperAdminSeeder::class,
        ]);
    }
}
