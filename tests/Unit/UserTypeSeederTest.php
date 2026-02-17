<?php

namespace Tests\Unit;

use Database\Seeders\UserTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTypeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_types_seeded(): void
    {
        $this->seed(UserTypeSeeder::class);

        $this->assertDatabaseHas('usertypes', ['name' => 'superadmin']);
        $this->assertDatabaseHas('usertypes', ['name' => 'admin']);
        $this->assertDatabaseHas('usertypes', ['name' => 'customer']);
        $this->assertDatabaseHas('usertypes', ['name' => 'jobseeker']);
    }
}
