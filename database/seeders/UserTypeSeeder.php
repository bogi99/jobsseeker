<?php

namespace Database\Seeders;

use App\Models\UserType;
use Illuminate\Database\Seeder;

class UserTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'superadmin', 'description' => 'Super Administrator with full system access'],
            ['name' => 'admin', 'description' => 'Administrator with limited system access'],
            ['name' => 'customer', 'description' => 'Customer user type'],
            ['name' => 'jobseeker', 'description' => 'Job seeker looking for employment'],
        ];

        foreach ($types as $type) {
            UserType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
