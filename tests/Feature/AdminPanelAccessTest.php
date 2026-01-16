<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_can_access_admin_panel()
    {
        $adminType = UserType::where('name', 'admin')->first();

        $user = User::factory()->create(['usertype_id' => $adminType->id]);

        // Sanity check: ensure factory created an admin user correctly
        $this->assertTrue($user->fresh()->isAdmin());

        $this->actingAs($user)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertStatus(200);
    }

    public function test_superadmin_user_can_access_admin_panel()
    {
        $superType = UserType::where('name', 'superadmin')->first();

        $user = User::factory()->create(['usertype_id' => $superType->id]);

        $this->actingAs($user)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertStatus(200);
    }

    public function test_customer_user_cannot_access_admin_panel()
    {
        $customerType = UserType::where('name', 'customer')->first();

        $user = User::factory()->create(['usertype_id' => $customerType->id]);

        $this->actingAs($user)
            ->get(route('filament.admin.pages.dashboard'))
            ->assertStatus(403);
    }

    public function test_guest_can_access_admin_login_page()
    {
        $this->get(route('filament.admin.auth.login'))
            ->assertStatus(200);
    }
}
